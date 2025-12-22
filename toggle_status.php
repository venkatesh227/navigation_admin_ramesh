<?php
include_once('db/connection.php');
session_start();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$current_status = isset($_POST['status']) ? $_POST['status'] : '';
$table = isset($_POST['table']) ? $_POST['table'] : 'routes';
$user_id = $_SESSION['user_id'] ?? 1;

// whitelist allowed tables to avoid SQL injection
$allowed = ['routes','groups','members','users'];
if (!in_array($table, $allowed)) {
    $table = 'routes';
}

// determine new status numeric value (assume DB uses 1 = Active, 0 = Inactive)
$new_status = ($current_status == 'Active' || $current_status === '1' || $current_status === 1) ? 0 : 1;

$sql = sprintf("UPDATE %s SET status='%d', updated_at=NOW(), updated_by='%d' WHERE id=%d", $table, $new_status, $user_id, $id);
$update = mysqli_query($conn, $sql);

if ($update) {
    echo json_encode(['status' => 'success', 'new_status' => ($new_status == 1 ? 'Active' : 'Inactive'), 'new_status_code' => $new_status]);
} else {
    echo json_encode(['status' => 'error', 'error' => mysqli_error($conn)]);
}
?>
