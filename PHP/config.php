<?php


require __DIR__ . '/../vendor/autoload.php';

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* DB */
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'matchy_matchy';

function db(): mysqli
{
    static $conn;
    if (!$conn) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* === SMTP (Gmail) === */
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'webpublicacc2025@gmail.com';            // <-- your Gmail
const SMTP_PASS = 'brgvsdhtustcokhz';              // 16-char App Password (see below)
const SMTP_FROM = 'webpublicacc2025@gmail.com';            // <-- same as SMTP_USER
const SMTP_FROM_NAME = 'Matchy Matchy';

function sendEmail(string $to, string $subject, string $html, string $text=''): void {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($to);
    // optional: $mail->addReplyTo('support@yourdomain.com', 'Support');
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = $text ?: strip_tags($html);
    $mail->send();
}

/* Google OAuth */
const GOOGLE_CLIENT_ID = '459602107559-r69c59aqo43tfddqc4vlindkssocpres.apps.googleusercontent.com';
const GOOGLE_CLIENT_SECRET = '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';
const GOOGLE_REDIRECT_URI = 'http://localhost/matchymatchy/PHP/google-callback.php';

function makeGoogleClient(): Google_Client
{
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setIncludeGrantedScopes(true);
    $client->addScope('openid'); // ضروري للـ ID token
    $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
    $client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);
    return $client;
}