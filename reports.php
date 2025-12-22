<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
include 'header.php';

// Fetch all employees for filters
$empSql = "SELECT id, name FROM employees ORDER BY name ASC";
$empRes = $conn->query($empSql);
$employees = [];
if ($empRes) {
    while ($row = $empRes->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Reports Center</h3>
                            <div class="nk-block-des text-soft">
                                <p>Generate attendance and activity reports.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nk-block">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#tab-status">Live Status</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tab-attendance">Attendance History</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#tab-visits">Visit Logs</a>
                                </li>
                            </ul>
                            <div class="tab-content mt-3">
                                
                                <!-- ================== TAB 1: LIVE STATUS ================== -->
                                <div class="tab-pane active" id="tab-status">
                                    <div class="d-flex justify-content-between mb-3 align-items-center">
                                        <h6 class="title">Today's Employee Status</h6>
                                        <button class="btn btn-dim btn-outline-primary btn-sm" onclick="location.reload()"><em class="icon ni ni-reload"></em> Refresh</button>
                                    </div>
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Employee</th>
                                                <th>Phone</th>
                                                <th>Current Route</th>
                                                <th>Check-In</th>
                                                <th>Check-Out</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $sql = "SELECT e.id, e.user_id, e.name, e.phone_no,
                                                (SELECT r.name FROM assign_routes ar LEFT JOIN routes r ON r.id = ar.route_id 
                                                 WHERE ar.employee_id = e.id AND CURDATE() BETWEEN ar.start_date AND ar.end_date LIMIT 1) AS route_name,
                                                (SELECT a.clock_in FROM attendance a WHERE a.user_id = e.user_id AND a.date = CURDATE() ORDER BY a.clock_in ASC LIMIT 1) AS clock_in,
                                                (SELECT a.clock_out FROM attendance a WHERE a.user_id = e.user_id AND a.date = CURDATE() ORDER BY a.clock_out DESC LIMIT 1) AS clock_out
                                                FROM employees e ORDER BY e.name ASC";
                                        $res = mysqli_query($conn, $sql);
                                        while ($row = mysqli_fetch_assoc($res)) {
                                            $ci = $row['clock_in'] ? date("h:i A", strtotime($row['clock_in'])) : '-';
                                            $co = $row['clock_out'] ? date("h:i A", strtotime($row['clock_out'])) : '-';
                                            if ($row['clock_in'] && !$row['clock_out']) $st = '<span class="badge badge-success">On Duty</span>';
                                            elseif ($row['clock_in'] && $row['clock_out']) $st = '<span class="badge badge-gray">Completed</span>';
                                            else $st = '<span class="badge badge-danger">Absent</span>';
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="user-card">
                                                        <div class="user-avatar bg-primary-dim d-none d-sm-flex">
                                                            <span><?php echo strtoupper(substr($row['name'],0,2)); ?></span>
                                                        </div>
                                                        <div class="user-info">
                                                            <span class="tb-lead"><?php echo htmlspecialchars($row['name']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $row['phone_no']; ?></td>
                                                <td><?php echo $row['route_name'] ?: '-'; ?></td>
                                                <td><?php echo $ci; ?></td>
                                                <td><?php echo $co; ?></td>
                                                <td><?php echo $st; ?></td>
                                                <td><a href="employee-details.php?id=<?php echo $row['user_id']; ?>" class="btn btn-xs btn-primary">Details</a></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- ================== TAB 2: ATTENDANCE HISTORY ================== -->
                                <div class="tab-pane" id="tab-attendance">
                                    <form id="attFilterForm" class="row gy-3 gx-3 align-items-end mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="from" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Employee</label>
                                            <select name="emp_id" class="form-select js-select2" data-search="on">
                                                <option value="all">All Employees</option>
                                                <?php foreach ($employees as $emp) echo "<option value='{$emp['user_id']}'>{$emp['name']}</option>"; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <button type="button" class="btn btn-primary" onclick="loadReport('attendance')">Filter</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="exportReport('attendance')">Export CSV</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="attResults">
                                        <div class="text-center p-4 text-soft">Select filters and click load.</div>
                                    </div>
                                </div>

                                <!-- ================== TAB 3: VISITS HISTORY ================== -->
                                <div class="tab-pane" id="tab-visits">
                                    <form id="visitFilterForm" class="row gy-3 gx-3 align-items-end mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" name="from" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">End Date</label>
                                            <input type="date" name="to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Employee</label>
                                            <select name="emp_id" class="form-select js-select2" data-search="on">
                                                <option value="all">All Employees</option>
                                                <?php foreach ($employees as $emp) echo "<option value='{$emp['id']}'>{$emp['name']}</option>"; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <button type="button" class="btn btn-primary" onclick="loadReport('visits')">Filter</button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="exportReport('visits')">Export CSV</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="visitResults">
                                        <div class="text-center p-4 text-soft">Select filters and click load.</div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    $(document).ready(function() {
        $('.js-select2').select2();
    });

    function loadReport(type) {
        const formId = (type === 'attendance') ? '#attFilterForm' : '#visitFilterForm';
        const resultDiv = (type === 'attendance') ? '#attResults' : '#visitResults';
        const formData = $(formId).serialize() + '&type=' + type + '&action=view';

        $(resultDiv).html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');

        $.post('ajax_reports.php', formData, function(data) {
            $(resultDiv).html(data);
        }).fail(function() {
            $(resultDiv).html('<div class="alert alert-danger">Failed to load data.</div>');
        });
    }

    function exportReport(type) {
        const formId = (type === 'attendance') ? '#attFilterForm' : '#visitFilterForm';
        const query = $(formId).serialize() + '&type=' + type + '&action=export';
        window.location.href = 'ajax_reports.php?' + query;
    }
</script>