<?php
include 'db/connection.php';
$sql = "ALTER TABLE employee_note ADD COLUMN is_seen TINYINT(1) DEFAULT 0 AFTER note";
if ($conn->query($sql)) {
    echo "Column added successfully";
} else {
    echo "Error: " . $conn->error;
}
?>
