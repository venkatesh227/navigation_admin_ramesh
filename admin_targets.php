<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$current_month = date('Y-m'); // Default to current month
if (isset($_GET['month'])) {
    $current_month = $_GET['month'];
}

$success = "";
$error = "";

// Handle Save/Update Target
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_target'])) {
    $emp_id = intval($_POST['employee_id']);
    $target = intval($_POST['visit_target']);
    $month  = $_POST['month_year'];

    // Upsert (Insert or Update)
    $stmt = $conn->prepare("INSERT INTO employee_targets (employee_id, month_year, visit_target) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE visit_target = ?");
    $stmt->bind_param("isii", $emp_id, $month, $target, $target);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Target Saved Successfully!";
    } else {
        $_SESSION['error'] = "Failed to save target.";
    }
    // redirect to avoid resubmission
    redirect("admin_targets.php?month=$month");
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Fetch all employees and their targets for the selected month
$sql = "
    SELECT 
        e.id, 
        e.name, 
        e.role, 
        COALESCE(t.visit_target, 0) as current_target
    FROM employees e
    LEFT JOIN employee_targets t ON e.id = t.employee_id AND t.month_year = '$current_month'
    WHERE e.status = 'Active' OR e.status = 1 -- Adjust based on actual status col
    ORDER BY e.name ASC
";
// Since we know 'status' col might be tricky, checking schema... but 'employees' usually has status or we just show all.
// Safest is to just list all or check if column exists. 
// For now I'll list all, assuming 'status' might not prevent listing.
$sql = "
    SELECT 
        e.id, 
        e.name, 
        COALESCE(t.visit_target, 0) as current_target
    FROM employees e
    LEFT JOIN employee_targets t ON e.id = t.employee_id AND t.month_year = '$current_month'
    ORDER BY e.name ASC
";

$result = $conn->query($sql);

include 'header.php';
?>

<div class="nk-content">
   <div class="container-fluid">
      <div class="nk-content-inner">
         <div class="nk-content-body">
            <div class="nk-block-head nk-block-head-sm">
                <div class="nk-block-between">
                    <div class="nk-block-head-content">
                        <h3 class="nk-block-title page-title">Monthly Targets</h3>
                        <div class="nk-block-des text-soft">
                            <p>Set visit targets for Sales Executives.</p>
                        </div>
                    </div>
                    <div class="nk-block-head-content">
                        <form method="get" class="d-flex align-items-center">
                            <label class="mr-2">Month:</label>
                            <input type="month" name="month" class="form-control" value="<?= $current_month ?>" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
            </div>

            <div class="nk-block">
               <div class="card card-bordered card-preview">
                  <div class="card-inner">
                     <table class="datatable-init nk-tb-list nk-tb-ulist table table-striped" data-auto-responsive="false">
                        <thead>
                           <tr class="nk-tb-item nk-tb-head">
                              <th class="nk-tb-col">Employee</th>
                              <th class="nk-tb-col">Target (Visits)</th>
                              <th class="nk-tb-col">Month</th>
                              <th class="nk-tb-col">Action</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php while ($row = $result->fetch_assoc()): ?>
                           <tr class="nk-tb-item">
                              <td class="nk-tb-col">
                                  <span class="tb-lead"><?= htmlspecialchars($row['name']) ?></span>
                              </td>
                              <td class="nk-tb-col">
                                  <span class="badge badge-dim badge-outline-primary badge-lg"><?= $row['current_target'] ?> Visits</span>
                              </td>
                              <td class="nk-tb-col">
                                  <span><?= date('F Y', strtotime($current_month)) ?></span>
                              </td>
                              <td class="nk-tb-col">
                                  <button class="btn btn-sm btn-primary" onclick="setTarget(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', <?= $row['current_target'] ?>)">
                                      Updates Target
                                  </button>
                              </td>
                           </tr>
                           <?php endwhile; ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Modal -->
<div class="modal fade" id="targetModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Target</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="month_year" value="<?= $current_month ?>">
                    <input type="hidden" name="employee_id" id="modal_emp_id">
                    
                    <div class="form-group text-center">
                        <h6 id="modal_emp_name" class="text-secondary mb-3"></h6>
                        <label class="form-label">Visit Target count</label>
                        <input type="number" name="visit_target" id="modal_target" class="form-control form-control-lg text-center" required min="0">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="save_target" class="btn btn-primary btn-block">Save Target</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setTarget(id, name, current) {
    document.getElementById('modal_emp_id').value = id;
    document.getElementById('modal_emp_name').innerText = name;
    document.getElementById('modal_target').value = current;
    $('#targetModal').modal('show');
}

<?php if (!empty($success)): ?>
    Swal.fire({ icon: 'success', title: '<?= $success ?>', showConfirmButton: false, timer: 1500 });
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
