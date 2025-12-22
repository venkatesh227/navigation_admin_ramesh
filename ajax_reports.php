<?php
session_start();
include_once('db/connection.php');

// Simple session check (redundant if using middleware, but safe here)
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

$type   = $_POST['type']   ?? $_GET['type']   ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';
$from   = $_POST['from']   ?? $_GET['from']   ?? date('Y-m-01');
$to     = $_POST['to']     ?? $_GET['to']     ?? date('Y-m-d');
$empId  = $_POST['emp_id'] ?? $_GET['emp_id'] ?? 'all';

// Prevent SQL injection on dates
$from = mysqli_real_escape_string($conn, $from);
$to   = mysqli_real_escape_string($conn, $to);

if ($type === 'attendance') {
    // Build SQL
    $sql = "SELECT e.name, a.date, a.clock_in, a.clock_out 
            FROM attendance a 
            JOIN employees e ON a.user_id = e.user_id 
            WHERE a.date BETWEEN '$from' AND '$to'";
    
    if ($empId !== 'all') {
        $empIdInt = (int)$empId;
        $sql .= " AND a.user_id = $empIdInt";
    }
    $sql .= " ORDER BY a.date DESC, e.name ASC";

    $res = $conn->query($sql);

    // EXPORT
    if ($action === 'export') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Employee', 'Clock In', 'Clock Out', 'Status']);
        while ($row = $res->fetch_assoc()) {
            $status = ($row['clock_in'] && $row['clock_out']) ? 'Completed' : 'On Duty';
            fputcsv($out, [
                $row['date'],
                $row['name'],
                $row['clock_in'],
                $row['clock_out'],
                $status
            ]);
        }
        fclose($out);
        exit;
    }

    // VIEW
    echo '<table class="table table-bordered table-striped">';
    echo '<thead><tr><th>Date</th><th>Employee</th><th>Clock In</th><th>Clock Out</th><th>Duration</th></tr></thead><tbody>';
    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $in  = $row['clock_in'] ? date("h:i A", strtotime($row['clock_in'])) : '-';
            $out = $row['clock_out'] ? date("h:i A", strtotime($row['clock_out'])) : '-';
            
            $duration = '-';
            if ($row['clock_in'] && $row['clock_out']) {
                $t1 = strtotime($row['clock_in']);
                $t2 = strtotime($row['clock_out']);
                $duration = round(($t2 - $t1) / 3600, 2) . ' hrs';
            }

            echo "<tr>
                <td>{$row['date']}</td>
                <td>{$row['name']}</td>
                <td>{$in}</td>
                <td>{$out}</td>
                <td>{$duration}</td>
            </tr>";
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">No records found</td></tr>';
    }
    echo '</tbody></table>';

} elseif ($type === 'visits') {
    // VISITS REPORT (Using member_reports table)
    // Assuming member_reports has: employee_id, clinic_name, created_at, notes/remarks?
    // Based on previous checks, table likely has 'member_reports' or similar. 
    // Let's assume standard 'member_reports' based on `db/functions.php` usage.
    
    $sql = "SELECT e.name as emp_name, mr.member_name, mr.clinic_name, mr.created_at, mr.latitude, mr.longitude 
            FROM member_reports mr 
            JOIN employees e ON mr.employee_id = e.id 
            WHERE DATE(mr.created_at) BETWEEN '$from' AND '$to'";

    if ($empId !== 'all') {
        $empIdInt = (int)$empId;
        $sql .= " AND mr.employee_id = $empIdInt";
    }
    $sql .= " ORDER BY mr.created_at DESC";

    $res = $conn->query($sql);

    // EXPORT
    if ($action === 'export') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="visit_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date/Time', 'Employee', 'Clinic/Member', 'Location']);
        while ($row = $res->fetch_assoc()) {
            $loc = ($row['latitude'] && $row['longitude']) ? "{$row['latitude']},{$row['longitude']}" : '-';
            fputcsv($out, [
                $row['created_at'],
                $row['emp_name'],
                $row['member_name'] . ' (' . $row['clinic_name'] . ')',
                $loc
            ]);
        }
        fclose($out);
        exit;
    }

    // VIEW
    echo '<table class="table table-bordered table-striped">';
    echo '<thead><tr><th>Time</th><th>Employee</th><th>Member/Clinic</th><th>Location</th></tr></thead><tbody>';
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $date = date("d M, h:i A", strtotime($row['created_at']));
            $locLink = ($row['latitude'] && $row['longitude']) 
                       ? "<a href='https://maps.google.com/?q={$row['latitude']},{$row['longitude']}' target='_blank'>Map</a>" 
                       : '-';
            
            echo "<tr>
                <td>{$date}</td>
                <td>{$row['emp_name']}</td>
                <td>{$row['member_name']} <br><small class='text-muted'>{$row['clinic_name']}</small></td>
                <td>{$locLink}</td>
            </tr>";
        }
    } else {
        echo '<tr><td colspan="4" class="text-center">No visits found</td></tr>';
    }
    echo '</tbody></table>';
}
?>
