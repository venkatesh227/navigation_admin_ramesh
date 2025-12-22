<?php
include 'db/connection.php';
$tables = ['assigned_members', 'member_reports'];
foreach($tables as $t){
    echo "Table: $t\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()){
        if(in_array($row['Field'], ['latitude', 'longitude'])) {
            echo $row['Field'] . " => " . $row['Type'] . "\n";
        }
    }
    echo "\n";
}
?>
