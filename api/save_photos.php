<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$photos  = $body['photos']      ?? [];
$layout  = $body['layout']      ?? 'strip';
$filter  = $body['filter_name'] ?? 'natural';
$user_id = $_SESSION['user_id'] ?? null;

if (empty($photos)) {
    echo json_encode(['success'=>false,'message'=>'No photos received.']); exit;
}

// Generate unique session code
$code      = 'PPB-' . strtoupper(substr(md5(uniqid(rand(),true)),0,8));
$uploadDir = __DIR__ . '/../uploads/photos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Save each photo
$savedFiles = [];
foreach ($photos as $i => $b64) {
    $data    = preg_replace('/^data:image\/\w+;base64,/','',$b64);
    $decoded = base64_decode($data);
    if (!$decoded) continue;
    $filename = $code . '_photo' . ($i+1) . '.png';
    file_put_contents($uploadDir . $filename, $decoded);
    $savedFiles[] = $filename;
}

if (empty($savedFiles)) {
    echo json_encode(['success'=>false,'message'=>'Failed to save photos.']); exit;
}

// Generate QR code via free API
$viewUrl    = 'http://localhost/PixelPodWeb/public/photobooth/view.php?code=' . $code;
$qrApiUrl   = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($viewUrl);
$qrFilename = $code . '_qr.png';
$qrData     = @file_get_contents($qrApiUrl);
if ($qrData) file_put_contents($uploadDir . $qrFilename, $qrData);

// Save to database
try {
    getDB()->prepare(
        "INSERT INTO photo_sessions (session_code,user_id,layout,filter_name,photos,qr_code) VALUES (?,?,?,?,?,?)"
    )->execute([$code, $user_id, $layout, $filter, json_encode($savedFiles), $qrFilename]);

    echo json_encode([
        'success'      => true,
        'session_code' => $code,
        'view_url'     => $viewUrl,
        'qr_url'       => 'http://localhost/PixelPodWeb/uploads/photos/' . $qrFilename,
        'photos'       => array_map(fn($f)=>'http://localhost/PixelPodWeb/uploads/photos/'.$f, $savedFiles)
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
