<?php
include 'db/connection.php';

// Check if we have any employees and members to link to
$emp = $conn->query("SELECT id FROM employees LIMIT 1")->fetch_assoc();
$mem = $conn->query("SELECT id FROM members LIMIT 1")->fetch_assoc();
$route = $conn->query("SELECT id FROM assign_routes LIMIT 1")->fetch_assoc();

if ($emp && $mem) {
    $emp_id = $emp['id']; // note: created_by in assigned_members might be user_id or emp_id depending on logic.
    // In member_detail.php we used $_SESSION['employee_id']. 
    // Let's check constraints.
    
    $mem_id = $mem['id'];
    $r_id = $route ? $route['id'] : 0; // assigned_route_id

    // Insert dummy record with Vizag coordinates (approx 17.6868, 83.2185)
    $sql = "INSERT INTO assigned_members (assigned_route_id, member_id, description, created_by, created_at, latitude, longitude)
            VALUES ($r_id, $mem_id, 'Test Visit Visualization', $emp_id, NOW(), 17.6868, 83.2185)";

    if ($conn->query($sql)) {
        echo "Inserted test record with coordinates (17.6868, 83.2185).\n";
    } else {
        echo "Error inserting: " . $conn->error . "\n";
    }
} else {
    echo "Cannot insert test data: Need at least 1 employee and 1 member in DB.\n";
}
?>
