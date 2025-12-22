<?php
include 'db/connection.php';
$user_id = 1; // Assuming default admin is 1
$res = $conn->query("SELECT * FROM admins WHERE user_id = $user_id");
if ($res->num_rows > 0) {
    print_r($res->fetch_assoc());
} else {
    echo "No admin record found for user_id $user_id. Inserting one...\n";
    $conn->query("INSERT INTO admins (user_id, name, designation, phone_no, created_at) VALUES (1, 'Administrator', 'Super Admin', '1234567890', NOW())");
    echo "Inserted default admin record.";
}
?>
