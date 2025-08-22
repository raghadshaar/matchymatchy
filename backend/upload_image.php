<?php
// /matchymatchy/backend/upload_image.php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

$targetRoot = realpath(__DIR__ . '/../uploads');
if ($targetRoot === false) {
    $targetRoot = __DIR__ . '/../uploads';
    @mkdir($targetRoot, 0775, true);
}
$dir = $targetRoot . '/products';
@mkdir($dir, 0775, true);

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'No file']); exit;
}

$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Upload error']); exit;
}

$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($f['tmp_name']) ?: 'application/octet-stream';
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Only JPG/PNG/WEBP allowed']); exit;
}

$ext   = $allowed[$mime];
$name  = preg_replace('~[^a-z0-9_-]+~i','-', pathinfo($f['name'], PATHINFO_FILENAME));
$name  = trim($name, '-');
$rand  = bin2hex(random_bytes(4));
$file  = sprintf('%s-%s.%s', $name ?: 'image', $rand, $ext);
$path  = $dir . '/' . $file;

if (!move_uploaded_file($f['tmp_name'], $path)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Failed to save']); exit;
}

/* Public URL relative to your site root.
   If your site root is /matchymatchy/, adjust the prefix accordingly: */
$url = "/matchymatchy/uploads/products/$file";

echo json_encode(['ok'=>true, 'url'=>$url, 'filename'=>$file]);
