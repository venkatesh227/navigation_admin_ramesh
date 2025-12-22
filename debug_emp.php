<?php
include 'db/connection.php';
$res = $conn->query("DESCRIBE employees");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
