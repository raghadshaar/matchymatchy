<?php
// C:\xampp\htdocs\your-app\config.php
require __DIR__ . '/vendor/autoload.php';

session_start();

const GOOGLE_CLIENT_ID     = 'PUT_YOUR_CLIENT_ID_HERE';
const GOOGLE_CLIENT_SECRET = 'PUT_YOUR_CLIENT_SECRET_HERE';
const GOOGLE_REDIRECT_URI  = 'http://localhost/your-app/google-callback.php';

// إنشاء Client مُشترك
function makeGoogleClient(): Google_Client {
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->setAccessType('offline');         // للحصول على refresh_token أول مرة
    $client->setPrompt('consent');             // يضمن ظهور شاشة الموافقة أول مرة
    $client->setIncludeGrantedScopes(true);
    $client->addScope([
        Google_Service_Oauth2::USERINFO_EMAIL,
        Google_Service_Oauth2::USERINFO_PROFILE
    ]);
    return $client;
}
