<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// احسبي مسار الجذر ثم db.php
$root = realpath(__DIR__ . '/..');        // ../ = الخروج من backend إلى جذر المشروع
require_once $root . '/PHP/db.php';       // ==> /matchymatchy/PHP/db.php

// تحقّق وقائي: لازم يكون $pdo موجود و PDO
if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('db.php did not create $pdo. Check the path and variable name.');
}
// بدّل هذا:
// function currentUserId(): ?int { ... }

// إلى هذا (متوافق مع PHP 7.0):
/** @return int|null */
function currentUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}


/** بصمة جهاز ثابتة عبر كوكي، ثم SHA-256 */
function getDeviceHash(): string {
    if (empty($_COOKIE['mm_device'])) {
        $token = bin2hex(random_bytes(16));
        setcookie('mm_device', $token, time()+60*60*24*365*2, '/', '', false, true);
        $_COOKIE['mm_device'] = $token;
    } else {
        $token = (string)$_COOKIE['mm_device'];
    }
    return hash('sha256', $token);
}
