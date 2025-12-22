<?php
session_start();
include_once("db/connection.php");

$employee_id = $_POST['employee_id'];
$route_id = $_POST['route_id'];
$group_id = $_POST['group_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$options = json_decode($_POST['options'], true); // clinic_names[]




// Insert into assign_routes
mysqli_query($conn, "
    INSERT INTO assign_routes (employee_id, route_id, group_id, start_date, end_date, selected_options, created_at, created_by)
    VALUES ('$employee_id', '$route_id', '$group_id', '$start_date', '$end_date', '".json_encode($options)."', NOW(), '".$_SESSION['user_id']."')
");

$assigned_route_id = mysqli_insert_id($conn); // LAST INSERT ID

// Save each clinic as assigned member
foreach ($options as $clinic_name) {

    // Find member_id from members table
    $q = mysqli_query($conn, "
        SELECT id FROM members 
        WHERE clinic_name = '$clinic_name'
          AND group_id = '$group_id'
          AND route_id = '$route_id'
          AND status = 1
        LIMIT 1
    ");

    if (mysqli_num_rows($q) > 0) {
        $m = mysqli_fetch_assoc($q);
        $member_id = $m['id'];

        // Insert into assigned_members
        mysqli_query($conn, "
            INSERT INTO assigned_members (assigned_route_id, member_id, created_at, created_by)
            VALUES ('$assigned_route_id', '$member_id', NOW(), '".$_SESSION['user_id']."')
        ");
    }
}

echo "success";
?>
