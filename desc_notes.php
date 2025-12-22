<?php
include 'db/connection.php';
$res = $conn->query("DESCRIBE employee_note");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
