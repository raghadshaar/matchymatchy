<?php
/**
 * Admin Reviews update: hide/unhide/flag/unflag
 * PATCH/POST JSON: { id, action:'hide'|'unhide'|'flag'|'unflag', reason? }
 */
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../PHP/db.php'; // <-- correct relative path
require_once __DIR__ . '/../util.php';      // <-- correct relative path

function json_ok(array $d=[]){ echo json_encode(['ok'=>true]+$d, JSON_UNESCAPED_UNICODE); exit; }
function json_err(string $m,int $s=200){ http_response_code($s); echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE); exit; }

try{
    $pdo = function_exists('pdo') ? pdo() : ((isset($pdo) && $pdo instanceof PDO) ? $pdo : null);
    if (!$pdo) throw new RuntimeException('PDO not initialized (check PHP/db.php include)');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $adminId = function_exists('require_admin') ? (int)require_admin()
        : (function_exists('currentUserId') ? (int)currentUserId() : 0);

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method!=='PATCH' && $method!=='POST') json_err('Method not allowed',405);

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw ?: 'null', true);
    if (!is_array($body) || !$body) $body = $_POST ?? [];

    $id     = (int)($body['id'] ?? 0);
    $action = trim((string)($body['action'] ?? ''));
    $reason = trim((string)($body['reason'] ?? ''));
    if ($id<=0 || $action==='') json_err('Missing or invalid id/action');

    $st = $pdo->prepare("SELECT id FROM product_reviews WHERE id=:id");
    $st->execute([':id'=>$id]);
    if (!$st->fetch()) json_err('Review not found',404);

    switch ($action){
        case 'hide':
            $pdo->prepare("UPDATE product_reviews
                           SET hidden=1, hidden_by=:a, hidden_at=NOW(), updated_at=NOW()
                           WHERE id=:id")->execute([':id'=>$id, ':a'=>$adminId ?: null]);
            break;
        case 'unhide':
            $pdo->prepare("UPDATE product_reviews
                           SET hidden=0, hidden_by=NULL, hidden_at=NULL, updated_at=NOW()
                           WHERE id=:id")->execute([':id'=>$id]);
            break;
        case 'flag':
            $pdo->prepare("UPDATE product_reviews
                           SET flagged=1, flag_reason=:r, flagged_by=:a, flagged_at=NOW(), updated_at=NOW()
                           WHERE id=:id")->execute([':id'=>$id, ':a'=>$adminId ?: null, ':r'=>($reason!==''?$reason:null)]);
            break;
        case 'unflag':
            $pdo->prepare("UPDATE product_reviews
                           SET flagged=0, flag_reason=NULL, flagged_by=NULL, flagged_at=NULL, updated_at=NOW()
                           WHERE id=:id")->execute([':id'=>$id]);
            break;
        default:
            json_err('Unsupported action: '.$action);
    }

    $r = $pdo->prepare("SELECT hidden, flagged, flag_reason, hidden_by, hidden_at, flagged_by, flagged_at
                        FROM product_reviews WHERE id=:id");
    $r->execute([':id'=>$id]);
    $row = $r->fetch() ?: [];

    json_ok([
        'id'          => $id,
        'hidden'      => !empty($row['hidden']),
        'flagged'     => !empty($row['flagged']),
        'flag_reason' => (string)($row['flag_reason'] ?? ''),
        'hidden_by'   => isset($row['hidden_by'])  ? (int)$row['hidden_by']  : null,
        'hidden_at'   => (string)($row['hidden_at'] ?? ''),
        'flagged_by'  => isset($row['flagged_by']) ? (int)$row['flagged_by'] : null,
        'flagged_at'  => (string)($row['flagged_at'] ?? ''),
    ]);
} catch(Throwable $e){
    json_err($e->getMessage());
}
