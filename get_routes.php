<?php
include_once('db/connection.php');

$result = mysqli_query($conn, "SELECT * FROM routes ORDER BY id DESC");
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'status' => $row['status'] == 1 ? 'Active' : 'Inactive'
    ];
}

echo json_encode($data);
?>
