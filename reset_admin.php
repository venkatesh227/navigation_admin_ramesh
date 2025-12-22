<?php
include 'db/connection.php';

$new_pass = '123456';
$hashed = password_hash($new_pass, PASSWORD_BCRYPT);
$username = 'navigation@gmail.com';

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_name = ?");
$stmt->bind_param("ss", $hashed, $username);
if ($stmt->execute()) {
    echo "Password for $username reset to $new_pass";
} else {
    echo "Error: " . $stmt->error;
}
?>
