<?php
// /matchymatchy/api/search.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/../ai/embeddings.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB not initialized']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $q_raw = trim((string)($_GET['q'] ?? ''));
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(60, max(1, (int)($_GET['limit'] ?? 24)));
    $mode  = strtolower((string)($_GET['mode'] ?? '')); // '', 'semantic'

    if ($q_raw === '') {
        echo json_encode(['success'=>false, 'error'=>'Empty query']);
        exit;
    }

    // ============ SEMANTIC MODE ============
    if ($mode === 'semantic') {
        // 1) Embed the query
        $q_vec = mm_embed_text($q_raw, 'text-embedding-3-small');

        // 2) Get candidate products + embeddings (filter by active status only)
        $rows = $pdo->query("
          SELECT p.id, p.name, p.slug, p.price, p.image_main_url, pe.embedding_json
          FROM products p
          JOIN product_embeddings pe ON pe.product_id = p.id
          WHERE p.status IN ('active','auto')
        ")->fetchAll();

        // 3) Compute cosine & rank (in-memory)
        $scored = [];
        foreach ($rows as $r) {
            $emb = json_decode($r['embedding_json'], true);
            if (!is_array($emb)) continue;
            $score = mm_cosine($q_vec, $emb);
            // light score threshold (optional): skip ultra-low matches
            if ($score < 0.10) continue;
            $r['__score'] = $score;
            $scored[] = $r;
        }
        usort($scored, fn($a,$b)=> $b['__score'] <=> $a['__score']);

        $total = count($scored);
        $pages = (int)ceil(($total ?: 0) / $limit);
        $offset= ($page-1)*$limit;
        $slice = array_slice($scored, $offset, $limit);

        $products = array_map(function($r){
            return [
                'id'   => (int)$r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'price'=> $r['price'],
                'image_main_url' => $r['image_main_url'] ?? null,
                'score'=> round((float)$r['__score'], 4),
            ];
        }, $slice);

        echo json_encode([
            'success'=>true,
            'query'=>$q_raw,
            'meta'=>[
                'page'=>$page, 'limit'=>$limit, 'total'=>$total, 'pages'=>$pages, 'mode'=>'semantic'
            ],
            'products'=>$products,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============ KEYWORD MODE (existing) ============
    $page  = max(1, (int)$page);
    $limit = min(60, max(1, (int)$limit));
    $offset= ($page - 1) * $limit;

    // boolean tokens
    $tokens   = preg_split('/\s+/', $q_raw, -1, PREG_SPLIT_NO_EMPTY);
    $booleanQ = implode(' ', array_map(function($w){
        $w = preg_replace('/[^\p{L}\p{N}\*]+/u', '', $w);
        return $w . '*';
    }, $tokens));

    $total = 0;
    $items = [];
    $modeUsed = 'fulltext';

    try {
        // FULLTEXT COUNT
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE MATCH(name, description, sku) AGAINST(:q IN BOOLEAN MODE)");
        $stmt->execute([':q' => $booleanQ]);
        $total = (int)$stmt->fetchColumn();

        // DATA
        $limitI  = (int)$limit; $offsetI = (int)$offset;
        $sql = "
          SELECT id, name, slug, price, image_main_url,
                 MATCH(name, description, sku) AGAINST(:q IN BOOLEAN MODE) AS score
          FROM products
          WHERE MATCH(name, description, sku) AGAINST(:q IN BOOLEAN MODE)
          ORDER BY score DESC
          LIMIT $limitI OFFSET $offsetI
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q' => $booleanQ]);
        $items = $stmt->fetchAll();
        $modeUsed = 'fulltext';
    } catch (Throwable $e) {
        // LIKE fallback
        $like = '%' . $q_raw . '%';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name LIKE :q OR slug LIKE :q OR sku LIKE :q OR description LIKE :q");
        $stmt->execute([':q'=>$like]);
        $total = (int)$stmt->fetchColumn();

        $limitI  = (int)$limit; $offsetI = (int)$offset;
        $sql = "
          SELECT id, name, slug, price, image_main_url
          FROM products
          WHERE name LIKE :q OR slug LIKE :q OR sku LIKE :q OR description LIKE :q
          ORDER BY name ASC
          LIMIT $limitI OFFSET $offsetI
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q'=>$like]);
        $items = $stmt->fetchAll();
        $modeUsed = 'like';
    }

    $pages = (int)ceil(($total ?: 0) / $limit);

    echo json_encode([
        'success'=>true,
        'query'=>$q_raw,
        'meta'=>[
            'page'=>$page, 'limit'=>$limit, 'total'=>$total, 'pages'=>$pages, 'mode'=>$modeUsed
        ],
        'products'=>array_map(function($r){
            return [
                'id'=>(int)$r['id'],
                'name'=>$r['name'],
                'slug'=>$r['slug'],
                'price'=>$r['price'],
                'image_main_url'=>$r['image_main_url'] ?? null,
            ];
        }, $items),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
