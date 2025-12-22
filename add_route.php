<?php   
include_once('db/connection.php');
session_start();

// Assume current user stored in session (optional fallback to 1)
$user_id = $_SESSION['user_id'] ?? 1;
$route_name = trim($_POST['route_name']);

// Empty validation
if (empty($route_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a route name.']);
    exit;
}

// Duplicate validation
$check = mysqli_query($conn, "SELECT * FROM routes WHERE name = '$route_name'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['status' => 'duplicate', 'message' => 'This route already exists.']);
    exit;
}

// Insert
$insert = mysqli_query($conn, "INSERT INTO routes (name, status, created_at, created_by) VALUES ('$route_name', 1, NOW(), '$user_id')");

if ($insert) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>
