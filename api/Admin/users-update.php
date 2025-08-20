<?php
// api/admin/users-update.php
require __DIR__ . '/../config.php';
require_method('POST'); // You can switch to PUT if you prefer

$input = read_json();
$id = (int)($input['id'] ?? 0);
if ($id <= 0) send_json(['error'=>'Invalid id'], 422);

$first = v_string($input['first_name'] ?? '', 50);
$last  = v_string($input['last_name']  ?? '', 50);
$email = v_email($input['email'] ?? '');
$username = v_string($input['username'] ?? '', 50) ?: null;
$phone  = v_string($input['phone']  ?? '', 30) ?: null;
$avatar = v_string($input['avatar'] ?? '', 255) ?: null;
$notes  = v_string($input['notes']  ?? '', 10000) ?: null;
$role   = v_role($input['role']   ?? 'Customer');
$status = v_status($input['status'] ?? 'Active');

$conn = db();

/* Ensure exists */
$s = $conn->prepare("SELECT 1 FROM users WHERE id=?");
$s->bind_param('i', $id);
$s->execute();
if (!$s->get_result()->fetch_row()) { $s->close(); send_json(['error'=>'Not found'], 404); }
$s->close();

/* Uniqueness (exclude self) */
if ($username) {
    $s = $conn->prepare("SELECT 1 FROM users WHERE username=? AND id<>? LIMIT 1");
    $s->bind_param('si', $username, $id);
    $s->execute();
    if ($s->get_result()->fetch_row()) { $s->close(); send_json(['error'=>'Username already exists'], 409); }
    $s->close();
}
$s = $conn->prepare("SELECT 1 FROM users WHERE email=? AND id<>? LIMIT 1");
$s->bind_param('si', $email, $id);
$s->execute();
if ($s->get_result()->fetch_row()) { $s->close(); send_json(['error'=>'Email already exists'], 409); }
$s->close();

/* Update */
$sql = "UPDATE users
        SET first_name=?, last_name=?, username=?, email=?, phone=?, avatar=?, notes=?, role=?, status=?
        WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssssssi', $first,$last,$username,$email,$phone,$avatar,$notes,$role,$status,$id);
$stmt->execute();
$stmt->close();

/* Return fresh row */
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at FROM users WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

send_json(['data'=>user_row_to_dto($row)], 200);
