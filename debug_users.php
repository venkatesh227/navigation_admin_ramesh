<?php
include 'db/connection.php';

echo "Users Listing:\n";
$res = $conn->query("SELECT id, user_name, role_id, status FROM users");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Username: " . $row['user_name'] . " | Role: " . $row['role_id'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
