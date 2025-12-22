<?php
session_start();
header("Content-Type: application/json");

// ---------------------------------------
// 1️⃣ SESSION VALIDATION
// ---------------------------------------
if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session Expired. Please login again.'
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];   // ALWAYS users.id 🔥

// ---------------------------------------
// 2️⃣ DB CONNECTION
// ---------------------------------------
include_once('../db/connection.php');
if (!isset($conn) || !$conn instanceof mysqli) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed!'
    ]);
    exit;
}

// ---------------------------------------
// 3️⃣ INPUTS
// ---------------------------------------
$action  = $_POST['action'] ?? '';
$lat     = $_POST['latitude']  ?? "0";
$long    = $_POST['longitude'] ?? "0";

$today = date("Y-m-d");

// ---------------------------------------
// 4️⃣ CHECK-IN
// ---------------------------------------
if ($action === 'checkin' || $action === 'clock_in') {

    // Prevent double check-in
    $check = $conn->prepare("SELECT id FROM attendance WHERE user_id=? AND date=? LIMIT 1");
    $check->bind_param("is", $userId, $today);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Already Checked-In Today'
        ]);
        exit;
    }

    // Insert Check-In
    $stmt = $conn->prepare("
        INSERT INTO attendance (
            user_id, clock_in, date,
            check_in_latitude, check_in_longitude,
            created_at, created_by
        ) VALUES (?, NOW(), ?, ?, ?, NOW(), ?)
    ");

    $stmt->bind_param("issss", $userId, $today, $lat, $long, $userId);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Checked In Successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Check-In Failed'
        ]);
    }
    exit;
}

// ---------------------------------------
// 5️⃣ CHECK-OUT
// ---------------------------------------
if ($action === 'checkout' || $action === 'clock_out') {

    // Find today's check-in record without check-out
    $sel = $conn->prepare("
        SELECT id FROM attendance 
        WHERE user_id=? AND date=? AND clock_out IS NULL 
        ORDER BY id DESC LIMIT 1
    ");
    $sel->bind_param("is", $userId, $today);
    $sel->execute();
    $res = $sel->get_result();

    if ($res->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No Active Check-In Found'
        ]);
        exit;
    }

    $row = $res->fetch_assoc();
    $att_id = (int)$row['id'];

    // Update Check-Out
    $upd = $conn->prepare("
        UPDATE attendance SET 
            clock_out = NOW(),
            check_out_latitude = ?,
            check_out_longitude = ?,
            updated_at = NOW(),
            updated_by = ?
        WHERE id=?
    ");
    $upd->bind_param("ssii", $lat, $long, $userId, $att_id);

    if ($upd->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Checked Out Successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Check-Out Failed'
        ]);
    }
    exit;
}

// ---------------------------------------
// 6️⃣ RESUME WORK
// ---------------------------------------
if ($action === 'resume') {
    // Find today's record that is checked out
    $sel = $conn->prepare("SELECT id FROM attendance WHERE user_id=? AND date=? AND clock_out IS NOT NULL ORDER BY id DESC LIMIT 1");
    $sel->bind_param("is", $userId, $today);
    $sel->execute();
    $res = $sel->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['status'=>'error', 'message'=>'No completed session to resume.']);
        exit;
    }
    $att_id = $res->fetch_assoc()['id'];

    // Reset clock_out
    $upd = $conn->prepare("UPDATE attendance SET clock_out = NULL WHERE id = ?");
    $upd->bind_param("i", $att_id);
    
    if ($upd->execute()) {
        echo json_encode(['status'=>'success', 'message'=>'Session Resumed']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Resume Failed']);
    }
    exit;
}

// ---------------------------------------
// 6️⃣ INVALID ACTION
// ---------------------------------------
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid Action'
]);
exit;

?>
