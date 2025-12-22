<?php
include 'db/connection.php';

echo "Table: users\n";
$res = $conn->query("DESCRIBE users");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nTable: admins\n";
$res = $conn->query("DESCRIBE admins");
if($res){
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Admins table not found or error: " . $conn->error;
}
?>
