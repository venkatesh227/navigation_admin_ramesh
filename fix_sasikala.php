<?php
include 'db/connection.php';

// Find Member ID
$mRes = $conn->query("SELECT id FROM members WHERE clinic_name LIKE '%sasikala%' LIMIT 1");
if ($mRes && $row = $mRes->fetch_assoc()) {
    $mid = $row['id'];
    // Update
    $sql = "UPDATE assigned_members SET latitude = 16.424739, longitude = 80.579429 WHERE member_id = $mid";
    if ($conn->query($sql)) {
        echo "Updated Sasikala coordinates successfully.\n";
    } else {
        echo "Error creating record: " . $conn->error . "\n";
    }
} else {
    echo "Member not found.\n";
}
?>
