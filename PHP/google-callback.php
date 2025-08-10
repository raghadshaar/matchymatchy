<?php
// C:\xampp\htdocs\your-app\google-callback.php
require __DIR__ . '/config.php';

// اتصال قاعدة البيانات
$conn = new mysqli("localhost", "root", "", "your_database");
if ($conn->connect_error) { die("DB failed: ".$conn->connect_error); }

$client = makeGoogleClient();

if (!isset($_GET['code'])) {
    // فشل أو إلغاء
    header('Location: sign-in.php');
    exit;
}

try {
    // تبادل الـ code إلى access_token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) { throw new Exception($token['error_description'] ?? 'Token error'); }

    $client->setAccessToken($token);

    // اجلب بيانات المستخدم
    $oauth2 = new Google_Service_Oauth2($client);
    $googleUser = $oauth2->userinfo->get();

    $googleId = $googleUser->id;
    $email    = $googleUser->email;
    $name     = $googleUser->name;
    $avatar   = $googleUser->picture;

    // خزّن/حدّث المستخدم في DB
    // مبدئيًا: users(id, username, email, password, google_id, avatar, provider, created_at)
    $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ? OR email = ?");
    $stmt->bind_param("ss", $googleId, $email);
    $stmt->execute();
    $stmt->bind_result($uid);
    $exists = $stmt->fetch();
    $stmt->close();

    if ($exists) {
        // موجود: حدّث بيانات بسيطة
        $stmt = $conn->prepare("UPDATE users SET google_id=?, avatar=?, provider='google' WHERE id=?");
        $stmt->bind_param("ssi", $googleId, $avatar, $uid);
        $stmt->execute();
        $stmt->close();
        $userId = $uid;
    } else {
        // جديد: أضف مستخدم
        $username = explode('@', $email)[0];
        $null = null;
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, google_id, avatar, provider, created_at) VALUES (?, ?, ?, ?, ?, 'google', NOW())");
        $stmt->bind_param("sssss", $username, $email, $null, $googleId, $avatar);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();
    }

    // سجّل الجلسة
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $name ?: $email;
    $_SESSION['email'] = $email;
    $_SESSION['avatar'] = $avatar;
    $_SESSION['provider'] = 'google';

    // توجّه للداشبورد
    header('Location: dashboard.php');
    exit;

} catch (Throwable $e) {
    error_log('Google login error: ' . $e->getMessage());
    header('Location: sign-in.php?error=google_login_failed');
    exit;
}
