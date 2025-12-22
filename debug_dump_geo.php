<?php
include 'db/connection.php';

echo "=== assigned_members ===\n";
$res = $conn->query("SELECT id, latitude, longitude FROM assigned_members WHERE latitude IS NOT NULL LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== member_reports ===\n";
$res = $conn->query("SELECT id, latitude, longitude FROM member_reports WHERE latitude IS NOT NULL LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
