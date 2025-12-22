<?php
include 'db/connection.php';
$res = $conn->query("DESCRIBE member_reports");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
?>
