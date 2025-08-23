<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_GET['user_id'];
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($result);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $avatar     = $_POST['avatar'] ?? '';
    $password   = $_POST['password'];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ?, password = ? WHERE id = ?");
        $success = $stmt->execute([$first_name, $last_name, $avatar, $hashed, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?");
        $success = $stmt->execute([$first_name, $last_name, $avatar, $id]);
    }

    echo json_encode(["message" => $success ? "Profile updated successfully." : "Error updating profile."]);
}
?>
