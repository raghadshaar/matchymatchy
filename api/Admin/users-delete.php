<?php
// api/admin/users-delete.php
require __DIR__ . '/../config.php';
require_method('POST');

$input = read_json();
$id = (int)($input['id'] ?? 0);
if ($id <= 0) send_json(['error'=>'Invalid id'], 422);

$conn = db();
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected === 0) send_json(['error'=>'Not found'], 404);
send_json(['deleted'=>true, 'id'=>$id], 200);
