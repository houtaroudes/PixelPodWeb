<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isAdmin()) { echo json_encode(['success'=>false,'message'=>'Unauthorized.']); exit; }
if ($_SERVER['REQUEST_METHOD']!=='POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed.']); exit; }

$id      = (int)($_POST['booking_id'] ?? 0);
$status  = sanitize($_POST['status'] ?? '');
$allowed = ['approved','rejected','cancelled','completed','pending'];

if (!$id || !in_array($status,$allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid data.']); exit; }

try {
    getDB()->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$status,$id]);
    echo json_encode(['success'=>true,'message'=>"Booking $status successfully."]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Database error.']);
}
