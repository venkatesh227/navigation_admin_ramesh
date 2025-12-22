 <?php
include_once('db/connection.php');
session_start();

$id = $_POST['id'];
$route_name = trim($_POST['route_name']);
$user_id = $_SESSION['user_id'] ?? 1;


$check = mysqli_query($conn, "SELECT * FROM routes WHERE name='$route_name' AND id!='$id'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['status' => 'duplicate']);
    exit;
}

$update = mysqli_query($conn, "UPDATE routes SET name='$route_name', updated_at=NOW(), updated_by='$user_id' WHERE id=$id");

if ($update) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
?> 
