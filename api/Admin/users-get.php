<?php
// api/admin/users-get.php
require __DIR__ . '/../config.php';
require_method('GET');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) send_json(['error'=>'Invalid id'], 422);

$conn = db();
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at FROM users WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) send_json(['error'=>'Not found'], 404);
send_json(['data'=>user_row_to_dto($row)], 200);

