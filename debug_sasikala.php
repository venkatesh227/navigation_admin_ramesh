<?php
include 'db/connection.php';

// Removed updated_at, relying on created_at
$sql = "SELECT m.clinic_name, m.name as member_name, am.latitude, am.longitude, am.created_at
        FROM assigned_members am
        JOIN members m ON am.member_id = m.id
        WHERE m.clinic_name LIKE '%sasikala%'
        ORDER BY am.created_at DESC
        LIMIT 5";

$res = $conn->query($sql);
echo "=== Checking assigned_members for 'sasikala' ===\n";
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query 1 failed: " . $conn->error . "\n";
}

$sql2 = "SELECT clinic_name, latitude, longitude, created_at 
         FROM member_reports 
         WHERE clinic_name LIKE '%sasikala%'
         ORDER BY created_at DESC LIMIT 5";
$res2 = $conn->query($sql2);
echo "\n=== Checking member_reports for 'sasikala' ===\n";
if ($res2) {
    while($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
     echo "Query 2 failed: " . $conn->error . "\n";
}
?>
