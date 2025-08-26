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
    // PDO settings
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Helps avoid edge cases with boolean-mode placeholders
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // ---------- inputs ----------
    $q_raw = trim((string)($_GET['q'] ?? ''));
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(60, max(1, (int)($_GET['limit'] ?? 24)));
    $mode  = strtolower((string)($_GET['mode'] ?? '')); // '', 'semantic'

    if ($q_raw === '') {
        echo json_encode(['success'=>false, 'error'=>'Empty query']);
        exit;
    }

    // Common WHERE (active products only)
    $where = "p.status IN ('active','auto')";

    // Utility: build BOOLEAN query tokens for FULLTEXT
    $tokens   = preg_split('/\s+/', $q_raw, -1, PREG_SPLIT_NO_EMPTY);
    $booleanQ = implode(' ', array_map(function($w){
        $w = preg_replace('/[^\p{L}\p{N}\*]+/u', '', $w);
        return $w . '*';
    }, $tokens));

    // ===================== SEMANTIC (HYBRID) MODE =====================
    if ($mode === 'semantic') {
        // 1) FULLTEXT candidates — TWO positional placeholders (? in SELECT and ? in WHERE)
        $cand = [];
        try {
            $sql = "
              SELECT p.id, p.name, p.slug, p.price, p.image_main_url,
                     pe.embedding_json,
                     MATCH(p.name, p.description, p.sku) AGAINST(? IN BOOLEAN MODE) AS ft_score
              FROM products p
              JOIN product_embeddings pe ON pe.product_id = p.id
              WHERE $where
                AND MATCH(p.name, p.description, p.sku) AGAINST(? IN BOOLEAN MODE)
              ORDER BY ft_score DESC
              LIMIT 200
            ";
            $stmt = $pdo->prepare($sql);
            // bind the SAME value twice, in order
            $stmt->execute([$booleanQ, $booleanQ]);
            $cand = $stmt->fetchAll();
            if (!$cand) { throw new Exception('no-ft-hits'); }
        } catch (Throwable $e) {
            // fallback: all active (cap 500)
            $stmt = $pdo->query("
              SELECT p.id, p.name, p.slug, p.price, p.image_main_url,
                     pe.embedding_json, 0 AS ft_score
              FROM products p
              JOIN product_embeddings pe ON pe.product_id = p.id
              WHERE $where
              LIMIT 500
            ");
            $cand = $stmt->fetchAll();
        }

        // 2) Embed query once
        $q_vec = mm_embed_text($q_raw, 'text-embedding-3-small');

        // 3) cosine + hybrid score (0.70 semantic, 0.30 keyword)
        $semMax = 0.0; $ftMax = 0.0; $scored = [];
        foreach ($cand as $r) {
            $emb = json_decode($r['embedding_json'], true);
            if (!is_array($emb)) continue;
            $sem = mm_cosine($q_vec, $emb);
            $ft  = (float)$r['ft_score'];
            $semMax = max($semMax, $sem);
            $ftMax  = max($ftMax,  $ft);
            $r['__sem'] = $sem;
            $r['__ft']  = $ft;
            $scored[] = $r;
        }

        foreach ($scored as &$r) {
            $semN = $semMax > 0 ? ($r['__sem'] / $semMax) : 0.0;
            $ftN  = $ftMax  > 0 ? ($r['__ft']  / $ftMax)  : 0.0;
            $r['__score'] = 0.70 * $semN + 0.30 * $ftN;
        }
        unset($r);

        // 4) dynamic threshold + Top-K + pagination
        usort($scored, fn($a,$b)=> $b['__score'] <=> $a['__score']);
        $topScore = isset($scored[0]) ? (float)$scored[0]['__score'] : 0.0;
        $keep = array_filter($scored, fn($r)=> $topScore>0 ? $r['__score'] >= 0.5 * $topScore : true);
        $keep = array_slice(array_values($keep), 0, 50);

        $total  = count($keep);
        $pages  = (int)ceil(($total ?: 0) / $limit);
        $offset = ($page - 1) * $limit;
        $slice  = array_slice($keep, $offset, $limit);

        $products = array_map(function($r){
            return [
                'id'            => (int)$r['id'],
                'name'          => $r['name'],
                'slug'          => $r['slug'],
                'price'         => $r['price'],
                'image_main_url'=> $r['image_main_url'] ?? null,
                'score'         => round((float)$r['__score'], 4),
            ];
        }, $slice);

        echo json_encode([
            'success'=>true,
            'query'=>$q_raw,
            'meta'=>[
                'page'=>$page, 'limit'=>$limit, 'total'=>$total, 'pages'=>$pages, 'mode'=>'semantic-hybrid'
            ],
            'products'=>$products,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ===================== KEYWORD MODE (FULLTEXT with LIKE fallback) =====================
    $offset = ($page - 1) * $limit;

    $total = 0; $items = []; $modeUsed = 'fulltext';

    try {
        // COUNT
        $stmt = $pdo->prepare("
          SELECT COUNT(*) FROM products p
          WHERE $where AND MATCH(p.name, p.description, p.sku) AGAINST(:q IN BOOLEAN MODE)
        ");
        $stmt->execute([':q'=>$booleanQ]);
        $total = (int)$stmt->fetchColumn();

        // DATA
        $limitI = (int)$limit; $offsetI = (int)$offset;
        $sql = "
          SELECT p.id, p.name, p.slug, p.price, p.image_main_url,
                 MATCH(p.name, p.description, p.sku) AGAINST(:q IN BOOLEAN MODE) AS score
          FROM products p
          WHERE $where AND MATCH(p.name, p.description, p.sku) AGAINST(:q IN BOOLEAN MODE)
          ORDER BY score DESC
          LIMIT $limitI OFFSET $offsetI
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q'=>$booleanQ]);
        $items = $stmt->fetchAll();
        $modeUsed = 'fulltext';
    } catch (Throwable $e) {
        // LIKE fallback
        $like = '%' . $q_raw . '%';
        $stmt = $pdo->prepare("
          SELECT COUNT(*) FROM products p
          WHERE $where AND (p.name LIKE :q OR p.slug LIKE :q OR p.sku LIKE :q OR p.description LIKE :q)
        ");
        $stmt->execute([':q'=>$like]);
        $total = (int)$stmt->fetchColumn();

        $limitI = (int)$limit; $offsetI = (int)$offset;
        $sql = "
          SELECT p.id, p.name, p.slug, p.price, p.image_main_url
          FROM products p
          WHERE $where AND (p.name LIKE :q OR p.slug LIKE :q OR p.sku LIKE :q OR p.description LIKE :q)
          ORDER BY p.name ASC
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
                'id'            => (int)$r['id'],
                'name'          => $r['name'],
                'slug'          => $r['slug'],
                'price'         => $r['price'],
                'image_main_url'=> $r['image_main_url'] ?? null,
            ];
        }, $items),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
