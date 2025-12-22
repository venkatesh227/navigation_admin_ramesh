<?php
session_start();
include_once('../db/connection.php');

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password too short']);
        exit;
    }

    // Hash securely
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // Update USERS table (where auth happens)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = (SELECT user_id FROM employees WHERE id = ?)");
    if(!$stmt){
        // fallback if subquery fails or user not linked
        echo json_encode(['status' => 'error', 'message' => 'User link not found']);
        exit;
    }
    
    $stmt->bind_param("si", $hashed, $employee_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    $stmt->close();
}
?>
