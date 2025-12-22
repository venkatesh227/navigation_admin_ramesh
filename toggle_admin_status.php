<?php
// toggle_admin_status.php
header('Content-Type: application/json; charset=utf-8');
session_start();
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    include_once('db/connection.php');
    if (!isset($conn) || !($conn instanceof mysqli)) throw new Exception('DB connection not found');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit;
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $current = trim((string)($_POST['current_status'] ?? ''));
    if ($user_id <= 0 || $current === '') {
        echo json_encode(['status'=>'error','message'=>'Invalid parameters']); exit;
    }

    // Normalize current: 'Active' or '1' = 1, else = 0
    $current_int = ($current === 'Active' || $current === '1') ? 1 : 0;
    $new_int = ($current_int == 1) ? 0 : 1;
    $new_label = ($new_int == 1) ? 'Active' : 'Inactive';

    $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('ii', $new_int, $user_id);
    $stmt->execute();
    if ($stmt->affected_rows >= 0) {
        echo json_encode(['status'=>'success','new_status'=>$new_label]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Update failed']);
    }
    $stmt->close();
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
