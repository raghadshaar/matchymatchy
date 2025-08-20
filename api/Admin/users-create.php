<?php
// api/admin/users-create.php
require __DIR__ . '/../config.php';
require_method('POST');

$input = read_json();

$first = v_string($input['first_name'] ?? '', 50);
$last  = v_string($input['last_name']  ?? '', 50);
$email = v_email($input['email'] ?? '');
$username = v_string($input['username'] ?? '', 50) ?: null;

$phone  = v_string($input['phone']  ?? '', 30) ?: null;
$avatar = v_string($input['avatar'] ?? '', 255) ?: null;
$notes  = v_string($input['notes']  ?? '', 10000) ?: null;

$role   = v_role($input['role']   ?? 'Customer');
$status = v_status($input['status'] ?? 'Active');

if ($first === '' || $last === '') {
    send_json(['error'=>'First and last name are required'], 422);
}

$conn = db();

/* Uniqueness checks */
if ($username) {
    $s = $conn->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
    $s->bind_param('s', $username);
    $s->execute();
    if ($s->get_result()->fetch_row()) { $s->close(); send_json(['error'=>'Username already exists'], 409); }
    $s->close();
}
$s = $conn->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
$s->bind_param('s', $email);
$s->execute();
if ($s->get_result()->fetch_row()) { $s->close(); send_json(['error'=>'Email already exists'], 409); }
$s->close();

/* Insert */
$sql = "INSERT INTO users (first_name,last_name,username,email,password,avatar,provider,google_id,email_verified_at,phone,notes,role,status)
        VALUES (?,?,?,?,NULL,?,'local',NULL,NULL,?,?,?,?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssss', $first,$last,$username,$email,$avatar,$phone,$notes,$role,$status);
$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

/* Return the created row */
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, avatar, role, status, notes, provider, created_at FROM users WHERE id=?");
$stmt->bind_param('i', $newId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

send_json(['data'=>user_row_to_dto($row)], 201); // 201 Created
