<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php'); // Assuming this handles admin login check

// Mark all pending expenses as seen by admin
$conn->query("UPDATE expenses SET admin_seen = 1 WHERE admin_seen = 0");

// -------------------- STATUS UPDATE --------------------
if (isset($_GET['action']) && isset($_GET['id'])) {
    $expense_id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = '';
    
    if ($action === 'approve') $new_status = 'Approved';
    if ($action === 'reject') $new_status = 'Rejected';
    
    if ($new_status) {
        $stmt = $conn->prepare("UPDATE expenses SET status = ?, notification_seen = 0 WHERE id = ?");
        $stmt->bind_param("si", $new_status, $expense_id);
        if ($stmt->execute()) {
             $_SESSION['success'] = "Expense marked as $new_status";
        } else {
             $_SESSION['error'] = "Failed to update status";
        }
        $stmt->close();
    }
    header("Location: admin_expenses.php");
    exit;
}

// -------------------- FILTERS --------------------
$where = " WHERE 1=1 ";
$params = [];
$types = "";

if (!empty($_GET['employee_id'])) {
    $where .= " AND e.id = ? ";
    $params[] = $_GET['employee_id'];
    $types .= "i";
}

if (!empty($_GET['status'])) {
    $where .= " AND ex.status = ? ";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (!empty($_GET['from_date'])) {
    $where .= " AND ex.date >= ? ";
    $params[] = $_GET['from_date'];
    $types .= "s";
}

if (!empty($_GET['to_date'])) {
    $where .= " AND ex.date <= ? ";
    $params[] = $_GET['to_date'];
    $types .= "s";
}

// -------------------- FETCH EXPENSES --------------------
$sql = "SELECT ex.*, e.name AS emp_name, e.employee_id AS emp_code
        FROM expenses ex
        JOIN employees e ON ex.employee_id = e.id
        $where
        ORDER BY ex.date DESC, ex.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}
$stmt->close();

// Fetch Employees for Filter
$empRes = $conn->query("SELECT id, name, employee_id FROM employees ORDER BY name ASC");
$allEmployees = [];
while ($r = $empRes->fetch_assoc()) $allEmployees[] = $r;


include 'header.php';
?>

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Expense Claims</h3>
                            <div class="nk-block-des text-soft">
                                <p>Manage employee expense submissions.</p>
                            </div>
                        </div>
                    </div>
                </div><!-- .nk-block-head -->

                <!-- FILTERS -->
                <div class="nk-block">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Employee</label>
                                        <div class="form-control-wrap">
                                            <select name="employee_id" class="form-select form-control" data-search="on">
                                                <option value="">All Employees</option>
                                                <?php foreach ($allEmployees as $emp): ?>
                                                    <option value="<?= $emp['id'] ?>" <?= (isset($_GET['employee_id']) && $_GET['employee_id'] == $emp['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($emp['name']) ?> (<?= $emp['employee_id'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="form-label">Status</label>
                                        <div class="form-control-wrap">
                                            <select name="status" class="form-select form-control">
                                                <option value="">All Status</option>
                                                <option value="Pending" <?= (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                <option value="Approved" <?= (isset($_GET['status']) && $_GET['status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                                                <option value="Rejected" <?= (isset($_GET['status']) && $_GET['status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                     <div class="form-group">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="from_date" class="form-control" value="<?= $_GET['from_date'] ?? '' ?>">
                                     </div>
                                </div>
                                <div class="col-md-2">
                                     <div class="form-group">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $_GET['to_date'] ?? '' ?>">
                                     </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-control-wrap">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="admin_expenses.php" class="btn btn-outline-light ml-2">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="nk-block">
                    <div class="card card-bordered card-stretch">
                        <div class="card-inner-group">
                            <div class="card-inner p-0">
                                <div class="nk-tb-list nk-tb-ulist">
                                    <div class="nk-tb-item nk-tb-head">
                                        <div class="nk-tb-col"><span class="sub-text">Employee</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Date</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Type</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Desc</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Amount</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Receipt</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Status</span></div>
                                        <div class="nk-tb-col nk-tb-col-tools text-right"></div>
                                    </div><!-- .nk-tb-item -->

                                    <?php if (empty($expenses)): ?>
                                        <div class="p-4 text-center text-muted">No expenses found matching filters.</div>
                                    <?php else: ?>
                                        <?php foreach ($expenses as $ex): 
                                            $statusBadge = 'badge-warning';
                                            if($ex['status'] == 'Approved') $statusBadge = 'badge-success';
                                            if($ex['status'] == 'Rejected') $statusBadge = 'badge-danger';
                                        ?>
                                        <div class="nk-tb-item">
                                            <div class="nk-tb-col">
                                                <div class="user-card">
                                                    <div class="user-info">
                                                        <span class="tb-lead"><?= htmlspecialchars($ex['emp_name']) ?> <span class="dot dot-success d-md-none ms-1"></span></span>
                                                        <span><?= $ex['emp_code'] ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span><?= date('d M, Y', strtotime($ex['date'])) ?></span>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span class="badge badge-dim badge-outline-primary"><?= $ex['expense_type'] ?></span>
                                            </div>
                                             <div class="nk-tb-col">
                                                <span class="small text-muted"><?= htmlspecialchars(substr($ex['description'], 0, 30)) ?>...</span>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span class="amount">₹ <?= number_format($ex['amount'], 2) ?></span>
                                            </div>
                                            <div class="nk-tb-col">
                                                <?php if($ex['photo']): ?>
                                                    <a href="<?= htmlspecialchars($ex['photo']) ?>" target="_blank" class="btn btn-sm btn-icon btn-white btn-dim btn-outline-primary"><em class="icon ni ni-img"></em></a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span class="badge <?= $statusBadge ?>"><?= $ex['status'] ?></span>
                                            </div>
                                            <div class="nk-tb-col nk-tb-col-tools">
                                                <ul class="nk-tb-actions gx-1">
                                                    <?php if($ex['status'] === 'Pending'): ?>
                                                    <li>
                                                        <a href="admin_expenses.php?action=approve&id=<?= $ex['id'] ?>" class="btn btn-trigger btn-icon text-success" data-toggle="tooltip" data-placement="top" title="Approve">
                                                            <em class="icon ni ni-check-circle-fill" style="font-size: 20px;"></em>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="admin_expenses.php?action=reject&id=<?= $ex['id'] ?>" class="btn btn-trigger btn-icon text-danger" data-toggle="tooltip" data-placement="top" title="Reject">
                                                            <em class="icon ni ni-cross-circle-fill" style="font-size: 20px;"></em>
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div><!-- .nk-tb-item -->
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                </div><!-- .nk-tb-list -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
if(isset($_SESSION['success'])) {
    echo "<script>Swal.fire({icon: 'success', title: 'Success', text: '{$_SESSION['success']}', timer: 2000, showConfirmButton: false});</script>";
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])) {
    echo "<script>Swal.fire({icon: 'error', title: 'Error', text: '{$_SESSION['error']}'});</script>";
    unset($_SESSION['error']);
}
include 'footer.php'; 
?>
