<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

include_once('../db/connection.php');

$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
    echo json_encode([]);
    exit;
}

// Use start_date as assign_date (safe, no error)
$sql = "
SELECT 
    r.name AS route_name,
    t.start_date AS assign_date
FROM assign_routes t
JOIN routes r ON r.id = t.route_id
WHERE 
    t.employee_id = ?
    AND CURDATE() BETWEEN t.start_date AND t.end_date
GROUP BY t.route_id
ORDER BY r.name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $employee_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
