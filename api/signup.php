<?php
// api/signup.php
require __DIR__ . '/../PHP/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$password  = (string)($_POST['password'] ?? '');
$terms     = $_POST['terms_accepted'] ?? '';

$errors = [];
if ($firstName === '' || mb_strlen($firstName) > 50) $errors['first_name'] = 'First name is required (≤ 50 chars).';
if ($lastName  === '' || mb_strlen($lastName)  > 50) $errors['last_name']  = 'Last name is required (≤ 50 chars).';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors['email']      = 'Invalid email.';
if (!(strlen($password) >= 8 && preg_match('/[A-Z]/',$password) && preg_match('/[a-z]/',$password) && preg_match('/\d/',$password))) {
    $errors['password'] = 'Password must be 8+ chars with upper, lower, and a number.';
}
if (!in_array($terms, ['1','on','true',1,true,'yes','YES'], true)) {
    $errors['terms_accepted'] = 'You must accept the Terms.';
}

header('Content-Type: application/json; charset=utf-8');

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'errors'=>$errors]);
    exit;
}

$conn = db();

// Duplicate email?
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>'Email already in use.']);
    exit;
}
$stmt->close();

// Hash & insert (provider defaults to 'local')
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('
    INSERT INTO users (first_name, last_name, email, password, created_at)
    VALUES (?, ?, ?, ?, NOW())
');
$stmt->bind_param('ssss', $firstName, $lastName, $email, $hash);
$stmt->execute();
$userId = $stmt->insert_id;
$stmt->close();

// Auto-login
$_SESSION['user_id']  = $userId;
$_SESSION['email']    = $email;
$_SESSION['provider'] = 'local';

http_response_code(201);
echo json_encode(['ok'=>true,'message'=>'Account created','user_id'=>$userId]);

