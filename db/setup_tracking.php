<?php
include 'connection.php';

$sql = "CREATE TABLE IF NOT EXISTS live_tracking (
    user_id INT(11) NOT NULL,
    latitude VARCHAR(50) NOT NULL,
    longitude VARCHAR(50) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table live_tracking created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
