<?php
session_start();
include_once('../db/connection.php'); // $conn (mysqli)
include_once('../db/functions.php'); // $conn (mysqli)
$included = false;
foreach ($paths as $p) {
    if (file_exists($p)) { include_once($p); $included = true; break; }
}
if (!$included) { echo "Missing db/connection.php (checked multiple paths)\n"; exit; }

$db = $conn ?? $con ?? null;
if (!$db) {
    echo "DB connection variable (\$conn or \$con) not found in connection.php\n";
    exit;
}

$res = $db->query("SELECT DATABASE() AS dbname");
$dbname = $res ? $res->fetch_assoc()['dbname'] : 'unknown';
header('Content-Type: text/plain; charset=utf-8');
echo "Connected DB: $dbname\n\nTables:\n";

$q = $db->query("SHOW TABLES");
if (!$q) { echo "SHOW TABLES failed: " . ($db->error ?? 'unknown'); exit; }
while ($r = $q->fetch_row()) {
    echo $r[0] . "\n";
}
