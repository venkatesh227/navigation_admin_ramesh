<?php
session_start();
include_once('db/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? null;
    $lat    = $_POST['lat'] ?? null;
    $lng    = $_POST['lng'] ?? null;

    if ($userId && $lat && $lng) {
        // Insert or Update
        $stmt = $conn->prepare("INSERT INTO live_tracking (user_id, latitude, longitude, last_updated) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE latitude=?, longitude=?, last_updated=NOW()");
        $stmt->bind_param("issss", $userId, $lat, $lng, $lat, $lng);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success']);
        exit;
    }
}
echo json_encode(['status' => 'error']);
?>
