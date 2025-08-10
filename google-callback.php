=<?php
require __DIR__ . '/config.php';

// اتصال قاعدة البيانات (عدّلي اسم DB لو مختلف)
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'matchymatchy';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_errno) {
    die('DB failed: ' . $conn->connect_error);
}

$client = makeGoogleClient();

if (!isset($_GET['code'])) {
    header('Location: sign-in.php');
    exit;
}

try {
    // تبادل الكود بـ access_token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception($token['error_description'] ?? 'Token error');
    }
    $client->setAccessToken($token);

    // بيانات المستخدم من جوجل
    $oauth2 = new Google_Service_Oauth2($client);
    $googleUser = $oauth2->userinfo->get();

    $googleId = $googleUser->id;
    $email    = $googleUser->email;
    $name     = $googleUser->name;
    $avatar   = $googleUser->picture;

    // هل المستخدم موجود؟
    $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ? OR email = ?");
    $stmt->bind_param("ss", $googleId, $email);
    $stmt->execute();
    $stmt->bind_result($uid);
    $exists = $stmt->fetch();
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE users SET google_id=?, avatar=?, provider='google' WHERE id=?");
        $stmt->bind_param("ssi", $googleId, $avatar, $uid);
        $stmt->execute();
        $stmt->close();
        $userId = $uid;
    } else {
        $username = explode('@', $email)[0];
        $null = null;
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, google_id, avatar, provider, created_at)
           VALUES (?,?,?,?,?,'google',NOW())"
        );
        $stmt->bind_param("sssss", $username, $email, $null, $googleId, $avatar);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();
    }

    // جلسة المستخدم
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $name ?: $email;
    $_SESSION['email'] = $email;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['provider'] = 'google';

    header('Location: dashboard.php');
    exit;

} catch (Throwable $e) {
    error_log('Google login error: ' . $e->getMessage());
    header('Location: sign-in.php?error=google_login_failed');
    exit;
}
