<?php
include 'db/connection.php';
echo "Table: members\n";
$res = $conn->query("DESCRIBE members");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";

echo "\nTable: assigned_members\n";
$res2 = $conn->query("DESCRIBE assigned_members");
while($row = $res2->fetch_assoc()) echo $row['Field'] . "\n";
?>
