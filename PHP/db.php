<?php
// PHP/db.php
declare(strict_types=1);

/*
  ملاحظة مهمة:
  - إذا كانت قاعدتك اسمها "matchy_matchy" (بشرطة سفلية) غيّري السطر التالي accordingly.
  - بالصورة ملف SQL اسمه "matchymatchy.sql" (بدون شرطة سفلية)، لذا افتراضيًا استخدمت matchymatchy.
*/
$DB_HOST    = 'localhost';
$DB_NAME    = 'matchy_matchy';   // إن كانت قاعدتك "matchy_matchy" غيّريها هنا
$DB_USER    = 'root';
$DB_PASS    = '';               // في XAMPP عادةً فارغة
$DB_CHARSET = 'utf8';           // لأنكِ اخترت utf8_general_ci

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // إظهار الأخطاء كاستثناءات
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // جلب الصفوف كمصفوفات اسمية
    PDO::ATTR_EMULATE_PREPARES   => false,                    // استخدام prepared الحقيقي
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);  // ← مهم: تعريف $pdo
} catch (PDOException $e) {
    // استجابة JSON مفيدة أثناء التطوير
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'DB connection failed', 'detail' => $e->getMessage()]);
    exit;
}
