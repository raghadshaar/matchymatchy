<?php
// api/admin/users-list.php
require __DIR__ . '/../config.php';
require_method('GET');

$conn = db();

$q       = isset($_GET['q']) ? v_string($_GET['q'], 100) : '';
$role    = isset($_GET['role']) && $_GET['role'] !== '' ? v_role($_GET['role']) : null;
$status  = isset($_GET['status']) && $_GET['status'] !== '' ? v_status($_GET['status']) : null;

$pageSize = max(1, min(50, (int)($_GET['page_size'] ?? 8))); // UI uses 8
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $pageSize;

$where = [];
$params = [];
$types  = '';

if ($q !== '') {
    $where[] = "(CONCAT(first_name,' ',last_name) LIKE ? OR email LIKE ? OR username LIKE ?)";
    $like = "%{$q}%";
    $params[] = $like;  $params[] = $like;  $params[] = $like;
    $types   .= 'sss';
}
if ($role)   { $where[] = "role = ?";   $params[] = $role;   $types .= 's'; }
if ($status) { $where[] = "status = ?"; $params[] = $status; $types .= 's'; }

$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* Count */
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM users {$sqlWhere}");
if ($types) { $stmt->bind_param($types, ...ref_values($params)); }
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$pages = max(1, (int)ceil($total / $pageSize));
if ($page > $pages) { $page = $pages; $offset = ($page - 1) * $pageSize; }

/* Data */
$sql = "SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at
        FROM users {$sqlWhere}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

$params2 = $params;
$types2  = $types . 'ii';
$params2[] = $pageSize;
$params2[] = $offset;

$stmt->bind_param($types2, ...ref_values($params2));
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) { $data[] = user_row_to_dto($row); }
$stmt->close();

send_json([
    'data' => $data,
    'meta' => [
        'page'      => $page,
        'page_size' => $pageSize,
        'total'     => $total,
        'pages'     => $pages
    ]
], 200);
