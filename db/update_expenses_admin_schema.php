<?php
include 'db/connection.php';

// Check if column exists
$colExists = false;
$res = $conn->query("SHOW COLUMNS FROM expenses LIKE 'admin_seen'");
if ($res && $res->num_rows > 0) {
    $colExists = true;
}

if (!$colExists) {
    echo "Adding admin_seen column...\n";
    if ($conn->query("ALTER TABLE expenses ADD COLUMN admin_seen TINYINT DEFAULT 0")) {
        echo "Column added successfully.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column admin_seen already exists.\n";
}

// Ensure all existing expenses have admin_seen based on status
// If Status is Pending, admin_seen should be 0 (unseen)
// If Status is Approved/Rejected, it implies admin has seen it (1)
$conn->query("UPDATE expenses SET admin_seen = 1 WHERE status IN ('Approved', 'Rejected')");
echo "Updated existing records.\n";
?>
