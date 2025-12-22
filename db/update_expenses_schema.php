<?php
include 'db/connection.php';

// Check if column exists
$colExists = false;
$res = $conn->query("SHOW COLUMNS FROM expenses LIKE 'notification_seen'");
if ($res && $res->num_rows > 0) {
    $colExists = true;
}

if (!$colExists) {
    echo "Adding notification_seen column...\n";
    if ($conn->query("ALTER TABLE expenses ADD COLUMN notification_seen TINYINT DEFAULT 1")) {
        echo "Column addedsuccessfully.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column notification_seen already exists.\n";
}
?>
