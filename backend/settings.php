<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';
require_once __DIR__ . '/util.php';
header('Content-Type: application/json; charset=utf-8');

// تمكين التعامل مع طلبات CORS إذا لزم الأمر
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'PDO not initialized (check PHP/db.php include)']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['ok' => false, 'error' => 'missing user_id']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode($result);
        } else {
            echo json_encode(['ok' => false, 'error' => 'User not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $avatar     = trim($_POST['avatar'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$id) {
        echo json_encode(['ok' => false, 'message' => 'Missing id']);
        exit;
    }

    // التحقق من صحة المدخلات
    if (empty($first_name) ){
        echo json_encode(['ok' => false, 'message' => 'First name is required']);
        exit;
    }

    if (empty($last_name)) {
        echo json_encode(['ok' => false, 'message' => 'Last name is required']);
        exit;
    }

    try {
        // 1) احضر البيانات الحالية من قاعدة البيانات
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentAvatar = $row['avatar'] ?? '';

        // 2) معالجة رفع ملف صورة (إذا تم رفع ملف)
        $finalAvatar = $currentAvatar;

        if (!empty($_FILES['avatar_file']['name'])) {
            $targetDir = __DIR__ . "/uploads/";
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    echo json_encode(['ok' => false, 'message' => 'Failed to create upload directory']);
                    exit;
                }
            }

            // تحقق من النوع والحجم
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $fname = $_FILES['avatar_file']['name'];
            $size  = $_FILES['avatar_file']['size'];
            $tmp   = $_FILES['avatar_file']['tmp_name'];

            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                echo json_encode(['ok' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed']);
                exit;
            }

            if ($size > $maxSize) {
                echo json_encode(['ok' => false, 'message' => 'Image too large (max 2MB)']);
                exit;
            }

            // اسم ملف آمن وفريد
            $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($fname, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeBase . '.' . $ext;
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($tmp, $targetFile)) {
                $finalAvatar = "uploads/" . $fileName;

                // حذف الصورة القديمة إذا كانت موجودة وليست الصورة الافتراضية
                if (!empty($currentAvatar) && $currentAvatar !== $finalAvatar &&
                    strpos($currentAvatar, 'uploads/') === 0 &&
                    file_exists(__DIR__ . '/' . $currentAvatar)) {
                    unlink(__DIR__ . '/' . $currentAvatar);
                }
            } else {
                echo json_encode(['ok' => false, 'message' => 'Upload failed']);
                exit;
            }
        } elseif (!empty($avatar)) {
            // إذا تم تقديم رابط صورة مباشرة
            $finalAvatar = $avatar;
        }

        // 3) تحديث البيانات في قاعدة البيانات
        if (!empty($password)) {
            // التحقق من قوة كلمة المرور إذا تم تقديمها
            if (strlen($password) < 6) {
                echo json_encode(['ok' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ?, password = ? WHERE id = ?");
            $success = $stmt->execute([$first_name, $last_name, $finalAvatar, $hashed, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?");
            $success = $stmt->execute([$first_name, $last_name, $finalAvatar, $id]);
        }

        if ($success) {
            echo json_encode([
                "ok" => true,
                "message" => "Profile updated successfully.",
                "avatar"  => $finalAvatar
            ]);
        } else {
            echo json_encode([
                "ok" => false,
                "message" => "Error updating profile."
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);