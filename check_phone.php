<?php
// check_phone.php
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once('db/connection.php'); // ensure $conn is mysqli

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
if ($phone === '') {
    echo json_encode(['error'=>'phone required']);
    exit;
}

// normalize digits only
$phone = preg_replace('/\D+/', '', $phone);

if (!preg_match('/^\d{10}$/', $phone)) {
    echo json_encode(['exists'=>false,'invalid_format'=>true]);
    exit;
}

$stmt = $conn->prepare('SELECT id FROM admins WHERE phone_no = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['error'=>'Prepare failed: '.$conn->error]);
    exit;
}
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

echo json_encode(['exists' => $exists]);
exit;
