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
    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) { echo json_encode(['ok'=>false,'error'=>'missing user_id']); exit; }

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode($result);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $avatar     = trim($_POST['avatar'] ?? ''); // رابط مكتوب (اختياري)
    $password   = $_POST['password'] ?? '';

    if (!$id) { echo json_encode(['message'=>'Missing id']); exit; }

    // 1) احضر الرابط الحالي من قاعدة البيانات (عشان لو ما تغير، ما نكتب فراغ)
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentAvatar = $row['avatar'] ?? '';

    // 2) معالجة رفع ملف صورة (اختياري)
    if (!empty($_FILES['avatar_file']['name'])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // تحقق من النوع والحجم
        $allowedExt = ['jpg','jpeg','png','webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fname = $_FILES['avatar_file']['name'];
        $size  = $_FILES['avatar_file']['size'];
        $tmp   = $_FILES['avatar_file']['tmp_name'];

        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            echo json_encode(['message'=>'Only JPG/PNG/WebP allowed']); exit;
        }
        if ($size > $maxSize) {
            echo json_encode(['message'=>'Image too large (max 2MB)']); exit;
        }

        // اسم ملف آمن وفريد
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/','_', pathinfo($fname, PATHINFO_FILENAME));
        $fileName = time() . '_' . $safeBase . '.' . $ext;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($tmp, $targetFile)) {
            // نخزن مسارًا نسبيًا من مجلد backend (مثلاً backend/uploads/xx.jpg)
            $avatar = "uploads/" . $fileName;
        } else {
            echo json_encode(['message'=>'Upload failed']); exit;
        }
    }

    // 3) حدد قيمة الصورة النهائية:
    // - لو رفعنا صورة: $avatar صار فيه "uploads/...".
    // - لو كتب المستخدم رابط يدوي في input: $avatar فيه الرابط.
    // - لو لا هذا ولا ذاك: خليها على الحالية.
    $finalAvatar = $avatar !== '' ? $avatar : $currentAvatar;

    // 4) تحديث بقية الحقول
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ?, password = ? WHERE id = ?");
        $success = $stmt->execute([$first_name, $last_name, $finalAvatar, $hashed, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?");
        $success = $stmt->execute([$first_name, $last_name, $finalAvatar, $id]);
    }

    echo json_encode([
        "message" => $success ? "Profile updated successfully." : "Error updating profile.",
        "avatar"  => $finalAvatar
    ]);
}
