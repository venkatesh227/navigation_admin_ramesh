<?php
include 'db/connection.php';

$sql = "SELECT id, latitude, longitude, created_at FROM assigned_members WHERE latitude IS NOT NULL LIMIT 5";
$res = $conn->query($sql);

echo "Checking assigned_members for coordinates:\n";
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Lat: " . $row['latitude'] . " | Lng: " . $row['longitude'] . "\n";
    }
} else {
    echo "No records found with coordinates.\n";
}
?>
