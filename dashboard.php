<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
include 'header.php';
$dashboardStats = getDashboardStats($conn);

// Check if there are any unseen expenses for Admin
$unseenExp = false;
$resUnseen = $conn->query("SELECT COUNT(*) as c FROM expenses WHERE admin_seen = 0");
if ($resUnseen) {
    $rowUnseen = $resUnseen->fetch_assoc();
    if ($rowUnseen['c'] > 0) {
        $unseenExp = true;
    }
}

// ---------------------
// FETCH DASHBOARD WIDGET DATA
// ---------------------
// 1. Total Employees
$resEmp = $conn->query("SELECT COUNT(*) as c FROM employees");
$totalEmployees = ($resEmp) ? $resEmp->fetch_assoc()['c'] : 0;

// 2. Active Routes Today
$todayDate = date('Y-m-d');
$resRoutes = $conn->query("SELECT COUNT(*) as c FROM assign_routes WHERE '$todayDate' BETWEEN start_date AND end_date");
$activeRoutes = ($resRoutes) ? $resRoutes->fetch_assoc()['c'] : 0;

// 3. Today's Visits (Assigned + Adhoc)
$resVisits1 = $conn->query("SELECT COUNT(*) as c FROM member_reports WHERE DATE(created_at) = '$todayDate'");
$visits1 = ($resVisits1) ? $resVisits1->fetch_assoc()['c'] : 0;

$resVisits2 = $conn->query("SELECT COUNT(*) as c FROM assigned_members WHERE DATE(created_at) = '$todayDate'");
$visits2 = ($resVisits2) ? $resVisits2->fetch_assoc()['c'] : 0;
$todayVisits = $visits1 + $visits2;

// 4. Pending Expenses
$resExp = $conn->query("SELECT COUNT(*) as c FROM expenses WHERE status = 'Pending'");
$pendingExpenses = ($resExp) ? $resExp->fetch_assoc()['c'] : 0;

// 5. Pending Leaves
$resLeaves = $conn->query("SELECT COUNT(*) as c FROM leaves WHERE status = 'Pending'");
$pendingLeaves = ($resLeaves) ? $resLeaves->fetch_assoc()['c'] : 0;

?>
<!-- content @s -->
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Dashboard</h3>
                        </div><!-- .nk-block-head-content -->
                        <div class="nk-block-head-content">
                            <div class="toggle-wrap nk-block-tools-toggle">
                                <a href="#" class="btn btn-icon btn-trigger toggle-expand mr-n1"
                                   data-target="pageMenu"><em class="icon ni ni-more-v"></em></a>
                                <div class="toggle-expand-content" data-content="pageMenu">
                                    <ul class="nk-block-tools g-3">
                                        <li>
                                            <div class="dropdown">
                                                <a href="#"
                                                   class="dropdown-toggle btn btn-white btn-dim btn-outline-light"
                                                   data-toggle="dropdown">
                                                    <em class="d-none d-sm-inline icon ni ni-calender-date"></em>
                                                    <span><span class="d-none d-md-inline">Last</span> 30 Days</span>
                                                    <em class="dd-indc icon ni ni-chevron-right"></em>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <ul class="link-list-opt no-bdr">
                                                        <li><a href="#"><span>Last 30 Days</span></a></li>
                                                        <li><a href="#"><span>Last 6 Months</span></a></li>
                                                        <li><a href="#"><span>Last 1 Years</span></a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="nk-block-tools-opt">
                                            <a href="reports.php" class="btn btn-primary">
                                                <em class="icon ni ni-reports"></em><span>Reports</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div><!-- .nk-block-head-content -->
                    </div><!-- .nk-block-between -->
                </div><!-- .nk-block-head -->

                <div class="nk-block">
                    <!-- WIDGETS ROW -->
                    <div class="row g-gs mb-3">
                        
                        <!-- Total Employees (Purple) -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-bordered card-full bg-purple-dim">
                                <div class="card-inner">
                                    <div class="card-title-group align-start mb-0">
                                        <div class="card-title">
                                            <h6 class="title text-purple">Total Employees</h6>
                                        </div>
                                        <div class="card-tools">
                                            <em class="card-hint icon ni ni-users text-purple" style="font-size: 28px; opacity: 0.8;"></em>
                                        </div>
                                    </div>
                                    <div class="card-amount mt-1">
                                        <span class="amount text-purple"> <?= $totalEmployees ?> </span>
                                        <span class="change text-purple" style="opacity: 0.7;"><small>Registered</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Routes (Info/Blue) -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-bordered card-full bg-info-dim">
                                <div class="card-inner">
                                    <div class="card-title-group align-start mb-0">
                                        <div class="card-title">
                                            <h6 class="title text-info">Active Routes</h6>
                                        </div>
                                        <div class="card-tools">
                                            <em class="card-hint icon ni ni-map text-info" style="font-size: 28px; opacity: 0.8;"></em>
                                        </div>
                                    </div>
                                    <div class="card-amount mt-1">
                                        <span class="amount text-info"> <?= $activeRoutes ?> </span>
                                        <span class="change text-info" style="opacity: 0.7;"><small>Today</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Visits (Success/Green) -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-bordered card-full bg-success-dim">
                                <div class="card-inner">
                                    <div class="card-title-group align-start mb-0">
                                        <div class="card-title">
                                            <h6 class="title text-success">Visits Today</h6>
                                        </div>
                                        <div class="card-tools">
                                            <em class="card-hint icon ni ni-check-circle-cut text-success" style="font-size: 28px; opacity: 0.8;"></em>
                                        </div>
                                    </div>
                                    <div class="card-amount mt-1">
                                        <span class="amount text-success"> <?= $todayVisits ?> </span>
                                        <span class="change text-success" style="opacity: 0.7;"><small>Completed</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Expenses (Warning/Orange) -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-bordered card-full bg-warning-dim">
                                <div class="card-inner">
                                    <div class="card-title-group align-start mb-0">
                                        <div class="card-title">
                                            <h6 class="title text-warning">Pending Expenses</h6>
                                        </div>
                                        <div class="card-tools">
                                            <em class="card-hint icon ni ni-money text-warning" style="font-size: 28px; opacity: 0.8;"></em>
                                        </div>
                                    </div>
                                    <div class="card-amount mt-1">
                                        <span class="amount text-warning"> <?= $pendingExpenses ?> </span>
                                        <span class="change text-warning" style="opacity: 0.7;"><small>To Review</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Leaves (Pink) -->
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-bordered card-full bg-pink-dim">
                                <div class="card-inner">
                                    <div class="card-title-group align-start mb-0">
                                        <div class="card-title">
                                            <h6 class="title text-pink">Leave Requests</h6>
                                        </div>
                                        <div class="card-tools">
                                            <em class="card-hint icon ni ni-calendar-check text-pink" style="font-size: 28px; opacity: 0.8;"></em>
                                        </div>
                                    </div>
                                    <div class="card-amount mt-1">
                                        <span class="amount text-pink"> <?= $pendingLeaves ?> </span>
                                        <span class="change text-pink" style="opacity: 0.7;"><small>Pending</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <!-- END WIDGETS -->
                    <div class="row g-gs">
                        <div class="col-xxl-6">
                            <div class="row g-gs">
                                <!-- ===================== EMPLOYEE CHECK-IN / CHECK-OUT ===================== -->

                                <div class="col-lg-6 col-xxl-12">
                                    <div class="card card-bordered">
                                        <div class="card-inner">
                                            <div class="card-title-group align-start mb-2">
                                                <div class="card-title">
                                                    <h6 class="title">Employee Check-in/Check-out Times</h6>
                                                </div>
                                                <div class="card-tools">
                                                    <em class="card-hint icon ni ni-help-fill"
                                                        data-toggle="tooltip" data-placement="left"
                                                        title="Latest checkin/checkout timings"></em>
                                                </div>
                                            </div>
                                            <div class="employee-attendance">
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Employee</th>
                                                                <th>Check-in</th>
                                                                <th>Check-out</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            // today's date
                                                            $today = date("Y-m-d");

                                                            // collect employee IDs who have attendance today
                                                            $todayEmployees = [];

                                                            $sql = "
                                                                SELECT 
                                                                    e.id,
                                                                    e.name,
                                                                    a.clock_in,
                                                                    a.clock_out
                                                                FROM employees e
                                                                JOIN attendance a ON a.user_id = e.user_id
                                                                WHERE a.date = '$today'
                                                                ORDER BY e.name ASC
                                                            ";

                                                            $res = $conn->query($sql);

                                                            if ($res && $res->num_rows > 0) {
                                                                while ($row = $res->fetch_assoc()) {
                                                                    $todayEmployees[] = (int)$row['id'];
                                                                    $in  = $row['clock_in']  ? date("h:i A", strtotime($row['clock_in'])) : "-";
                                                                    $out = $row['clock_out'] ? date("h:i A", strtotime($row['clock_out'])) : "-";
                                                                    echo "
                                                                        <tr>
                                                                            <td>{$row['name']}</td>
                                                                            <td class='text-success'>$in</td>
                                                                            <td class='text-danger'>$out</td>
                                                                        </tr>
                                                                    ";
                                                                }
                                                            } else {
                                                                echo "<tr><td colspan='3' class='text-center'>No check-ins today</td></tr>";
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    

                                    
                                </div><!-- .col -->


                                <!-- ===================== VISITS CHART (col-lg-6) ===================== -->
                                <div class="col-lg-6 ">
                                    <div class="card card-bordered h-100">
                                        <div class="card-inner">
                                            <div class="card-title-group mb-2">
                                                <div class="card-title">
                                                    <h6 class="title">Visits Overview (Last 7 Days)</h6>
                                                </div>
                                            </div>
                                            <div class="nk-chart-analytics-group align-end">
                                               <canvas id="visitsChart" height="150"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ===================== EMPLOYEE COUNT TIME (col-lg-6) ===================== -->
                                <div class="col-lg-6 mt-3">
                                    <div class="card card-bordered h-100">
                                        <div class="card-inner">
                                            <div class="card-title-group align-start gx-3 mb-3">
                                                <div class="card-title">
                                                    <h6 class="title">Employee Count Time</h6>
                                                    <p>Today's counting performance</p>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee</th>
                                                            <th>Count</th>
                                                            <th>Time</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (count($dashboardStats['employee_counts']) > 0) {
                                                            foreach ($dashboardStats['employee_counts'] as $emp) {
                                                        ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                                            <td><?php echo $emp['count']; ?></td>
                                                            <td><?php echo $emp['time']; ?></td>
                                                            <td>
                                                                <div class="dropdown">
                                                                    <a href="#" class="btn btn-sm btn-icon btn-trigger"
                                                                       data-toggle="dropdown" aria-expanded="false">
                                                                        <em class="icon ni ni-more-h"></em>
                                                                    </a>
                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                        <ul class="link-list-opt no-bdr">
                                                                            <li><a href="#"><em class="icon ni ni-eye"></em><span>View</span></a></li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php } 
                                                        } else { ?>
                                                            <tr><td colspan="4" class="text-center">No visits recorded today</td></tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 mt-3">
                                    <div class="card card-bordered">
                                        <div class="card-inner">
                                            <div class="card-title-group align-start mb-2">
                                                <div class="card-title">
                                                    <h6 class="title">Assigned Routes</h6>
                                                </div>
                                                <div class="card-tools">
                                                    <em class="card-hint icon ni ni-help-fill"
                                                        data-toggle="tooltip" data-placement="left"
                                                        title="Employee route assignments"></em>
                                                </div>
                                            </div>
                                            <div class="employee-routes">
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Location</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $assignedRes = null;

                                                            if (!empty($todayEmployees)) {
                                                                // unique IDs just in case
                                                                $todayEmployees = array_unique($todayEmployees);
                                                                $ids = implode(',', $todayEmployees);

                                                                // routes.name is the location name (vizag, Guntur, etc.)
                                                                $routeSql = "
                                                                    SELECT 
                                                                        e.name,
                                                                        r.name AS location
                                                                    FROM assign_routes ar
                                                                    JOIN employees e ON e.id = ar.employee_id
                                                                    JOIN routes r ON r.id = ar.route_id
                                                                    WHERE ar.employee_id IN ($ids)
                                                                      AND '$today' BETWEEN ar.start_date AND ar.end_date
                                                                    ORDER BY e.name ASC
                                                                ";

                                                                $assignedRes = $conn->query($routeSql);
                                                            }

                                                            if ($assignedRes && $assignedRes->num_rows > 0) {
                                                                while ($r = $assignedRes->fetch_assoc()) {
                                                                    echo "
                                                                        <tr>
                                                                            <td>{$r['name']}</td>
                                                                            <td class='text-primary'>{$r['location']}</td>
                                                                        </tr>
                                                                    ";
                                                                }
                                                            } else {
                                                                echo "<tr><td colspan='2' class='text-center'>No assigned routes for today's employees</td></tr>";
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- .row -->
                            




                        <!-- ===================== RIGHT SIDE STATIC CARDS ===================== -->
                        <div class="col-lg-6 col-xxl-4">
                            <div class="card card-bordered h-100">
                                <div class="card-inner border-bottom">
                                    <div class="card-title-group">
                                        <div class="card-title">
                                            <h6 class="title">Employee Attendance</h6>
                                        </div>
                                        <div class="card-tools">
                                            <a href="#" class="link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <ul class="nk-support">
                                    <?php if (count($dashboardStats['attendance_today']) > 0) {
                                        foreach ($dashboardStats['attendance_today'] as $att) {
                                            $initials = strtoupper(substr($att['name'], 0, 2));
                                            $status = $att['clock_in'] ? 'present' : 'absent';
                                            $badgeClass = $att['clock_in'] ? 'badge-success' : 'badge-danger';
                                            // Simple duration calculation if clock_out exists
                                            $duration = '';
                                            if ($att['clock_in'] && $att['clock_out']) {
                                                $t1 = strtotime($att['clock_in']);
                                                $t2 = strtotime($att['clock_out']);
                                                $hours = round(($t2 - $t1) / 3600, 1);
                                                $duration = "Working: $hours hours";
                                            } else if ($att['clock_in']) {
                                                $duration = "Currently Working";
                                            }
                                    ?>
                                    <li class="nk-support-item">
                                        <div class="user-avatar bg-purple-dim">
                                            <span><?php echo $initials; ?></span>
                                        </div>
                                        <div class="nk-support-content">
                                            <div class="title">
                                                <span><?php echo htmlspecialchars($att['name']); ?></span>
                                                <span class="badge badge-dot badge-dot-xs <?php echo $badgeClass; ?> ml-1"><?php echo $status; ?></span>
                                            </div>
                                            <p>Checked in at <?php echo $att['clock_in'] ? date("h:i A", strtotime($att['clock_in'])) : '-'; ?></p>
                                            <span class="time"><?php echo $duration; ?></span>
                                        </div>
                                    </li>
                                    <?php }
                                    } else { ?>
                                        <li class="nk-support-item"><div class="nk-support-content"><p>No attendance records today.</p></div></li>
                                    <?php } ?>
                                </ul>
                            </div><!-- .card -->
                        </div><!-- .col -->

                        <div class="col-lg-6 col-xxl-4">
                            <div class="card card-bordered h-100">
                                <div class="card-inner border-bottom">
                                    <div class="card-title-group">
                                        <div class="card-title">
                                            <h6 class="title">Employee Tasks</h6>
                                        </div>
                                        <div class="card-tools">
                                            <a href="#" class="link">View All</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-inner">
                                    <div class="timeline">
                                        <h6 class="timeline-head">Today, <?php echo date("Y"); ?></h6>
                                        <ul class="timeline-list">
                                            <?php if (count($dashboardStats['timeline']) > 0) {
                                                foreach ($dashboardStats['timeline'] as $item) {
                                            ?>
                                            <li class="timeline-item">
                                                <div class="timeline-status bg-<?php echo $item['status']; ?> is-outline"></div>
                                                <div class="timeline-date">
                                                    <?php echo date("h:i A", strtotime($item['time'])); ?> <em class="icon ni ni-clock"></em>
                                                </div>
                                                <div class="timeline-data">
                                                    <h6 class="timeline-title"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <div class="timeline-des">
                                                        <p><?php echo htmlspecialchars($item['desc']); ?></p>
                                                    </div>
                                                </div>
                                            </li>
                                            <?php }
                                            } else { ?>
                                                <li class="timeline-item">
                                                    <div class="timeline-data">
                                                        <div class="timeline-des"><p>No activities recorded today.</p></div>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            </div><!-- .card -->
                        </div><!-- .col -->
                    </div><!-- .row -->
                </div><!-- .nk-block -->
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){
    
    // FETCH DATA
    <?php
    // Get last 7 days visits (union of member_reports and assigned_members)
    $dates = [];
    $counts = [];
    
    for($i=6; $i>=0; $i--){
        $d = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d M', strtotime($d));
        
        $c = 0;
        // Count from member_reports
        $sql1 = "SELECT COUNT(*) as c FROM member_reports WHERE DATE(created_at) = '$d'";
        $r1 = $conn->query($sql1);
        if($r1) $c += $r1->fetch_assoc()['c'];
        
        // Count from assigned_members
        $sql2 = "SELECT COUNT(*) as c FROM assigned_members WHERE DATE(created_at) = '$d'";
        $r2 = $conn->query($sql2);
        if($r2) $c += $r2->fetch_assoc()['c'];
        
        $counts[] = $c;
    }
    ?>

    const ctx = document.getElementById('visitsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Total Visits',
                data: <?= json_encode($counts) ?>,
                borderColor: '#6576ff',
                backgroundColor: 'rgba(101, 118, 255, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6576ff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#1c2b46',
                    padding: 10,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#f3f4f6' },
                    ticks: { precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    <?php if ($unseenExp): ?>
    Swal.fire({
        title: 'New Expense Claim!',
        text: 'Some employees have submitted new expense claims.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'View Now',
        cancelButtonText: 'Later'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'admin_expenses.php';
        }
    });
    <?php endif; ?>
});
</script>
