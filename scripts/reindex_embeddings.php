<?php
// /matchymatchy/scripts/reindex_embeddings.php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';           // your PDO $pdo
require_once __DIR__ . '/../ai/embeddings.php';    // helper

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/** Optional: get category path text per product (Baby > Baby Boy, etc.) */
function category_path_for_product(PDO $pdo, int $productId): string {
    $sql = "
      SELECT c.id, c.name, c.parent_id
      FROM product_categories pc 
      JOIN categories c ON c.id = pc.category_id
      WHERE pc.product_id = ?
      ORDER BY c.parent_id IS NULL DESC, c.parent_id ASC, c.id ASC
    ";
    $rows = $pdo->prepare($sql);
    $rows->execute([$productId]);
    $rows = $rows->fetchAll();
    if (!$rows) return '';
    // very light path: parent names first, then children
    $names = array_map(fn($r)=>$r['name'], $rows);
    return implode(' > ', $names);
}

echo "Reindexing product embeddings...\n";

$products = $pdo->query("SELECT id, name, sku, description FROM products WHERE status IN ('active','auto')")->fetchAll();

$upsert = $pdo->prepare("
  INSERT INTO product_embeddings (product_id, model, embedding_json, updated_at)
  VALUES (:id, :model, :emb, NOW())
  ON DUPLICATE KEY UPDATE model=VALUES(model), embedding_json=VALUES(embedding_json), updated_at=NOW()
");

$ok=0; $fail=0;
foreach ($products as $p) {
    try {
        $catPath = category_path_for_product($pdo, (int)$p['id']);
        $text = mm_build_product_index_text($p, $catPath);
        if ($text === '') continue;

        $vec = mm_embed_text($text, 'text-embedding-3-small'); // multilingual, cost-effective
        $embJson = json_encode($vec);

        $upsert->execute([
            ':id'    => (int)$p['id'],
            ':model' => 'text-embedding-3-small',
            ':emb'   => $embJson,
        ]);
        $ok++;
        echo "OK id={$p['id']}\n";
    } catch (Throwable $e) {
        $fail++;
        fwrite(STDERR, "FAIL id={$p['id']} :: ".$e->getMessage()."\n");
    }
}

echo "Done. success={$ok}, failed={$fail}\n";
