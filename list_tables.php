<?php
include 'db/connection.php';
$res = $conn->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_row()) {
        echo $row[0] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
