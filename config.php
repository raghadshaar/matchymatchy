<?php
// C:\xampp\htdocs\your-app\config.php
require __DIR__ . '/vendor/autoload.php';

session_start();

const GOOGLE_CLIENT_ID     = '459602107559-r69c59aqo43tfddqc4vlindkssocpres.apps.googleusercontent.com';
const GOOGLE_CLIENT_SECRET = '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';
const GOOGLE_REDIRECT_URI  = 'http://localhost/matchymatchy/google-callback.php';

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
