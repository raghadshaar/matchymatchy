<?php
// /matchymatchy/backend/settings.php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* ===== CORS (اختياري للتطوير). لو موقعك same-origin، إحذفي هذا القسم. ===== */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost',
    'http://127.0.0.1',
    // أضيفي دومينك الفعلي هنا عند النشر
    //'https://your-domain.com',
];
if ($origin && in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
/* ===== نهاية CORS ===== */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'pdo_init_failed']);
    exit;
}

/* ===== Require login (SESSION only) ===== */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            echo json_encode($row);
        } else {
            echo json_encode(['ok' => false, 'error' => 'not_found']);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = trim((string)($_POST['first_name'] ?? ''));
        $last_name  = trim((string)($_POST['last_name'] ?? ''));
        $avatarUrl  = trim((string)($_POST['avatar'] ?? '')); // رابط اختياري
        $password   = (string)($_POST['password'] ?? '');

        if ($first_name === '') {
            echo json_encode(['ok' => false, 'message' => 'First name is required']);
            exit;
        }
        if ($last_name === '') {
            echo json_encode(['ok' => false, 'message' => 'Last name is required']);
            exit;
        }

        // احضر الصورة الحالية
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentAvatar = (string)($stmt->fetchColumn() ?: '');

        $finalAvatar = $currentAvatar;

        // رفع صورة جديدة إن وُجدت
        if (!empty($_FILES['avatar_file']['name'])) {
            $targetDir = __DIR__ . "/uploads/";
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
                echo json_encode(['ok' => false, 'message' => 'Failed to create upload directory']);
                exit;
            }

            $allowedExt = ['jpg','jpeg','png','webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $fname = (string)$_FILES['avatar_file']['name'];
            $size  = (int)$_FILES['avatar_file']['size'];
            $tmp   = (string)$_FILES['avatar_file']['tmp_name'];
            $ext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                echo json_encode(['ok' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed']);
                exit;
            }
            if ($size > $maxSize) {
                echo json_encode(['ok' => false, 'message' => 'Image too large (max 2MB)']);
                exit;
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($fname, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeBase . '.' . $ext;
            $targetFile = $targetDir . $fileName;

            if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $targetFile)) {
                echo json_encode(['ok' => false, 'message' => 'Upload failed']);
                exit;
            }

            $finalAvatar = "uploads/" . $fileName;

            // حذف القديمة إذا كانت ضمن uploads/
            $oldPath = $currentAvatar && str_starts_with($currentAvatar, 'uploads/') ? (__DIR__ . '/' . $currentAvatar) : '';
            if ($oldPath && is_file($oldPath) && $currentAvatar !== $finalAvatar) {
                @unlink($oldPath);
            }
        } elseif ($avatarUrl !== '') {
            // لو تم تزويد رابط صورة مباشرة
            $finalAvatar = $avatarUrl;
        }

        // تحديث البيانات
        if ($password !== '') {
            if (strlen($password) < 6) {
                echo json_encode(['ok' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ?, password = ? WHERE id = ?");
            $ok = $stmt->execute([$first_name, $last_name, $finalAvatar, $hashed, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, avatar = ? WHERE id = ?");
            $ok = $stmt->execute([$first_name, $last_name, $finalAvatar, $userId]);
        }

        echo json_encode($ok
            ? ['ok' => true,  'message' => 'Profile updated successfully.', 'avatar' => $finalAvatar]
            : ['ok' => false, 'message' => 'Error updating profile.']
        );
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_error', 'message' => $e->getMessage()]);
}
