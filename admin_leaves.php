<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$success = "";
$error = "";

// Handle Actions
if (isset($_POST['action']) && isset($_POST['leave_id'])) {
    $action = $_POST['action'];
    $leave_id = intval($_POST['leave_id']);
    $remark = trim($_POST['remark']);
    
    $status = ($action == 'approve') ? 'Approved' : 'Rejected';
    
    $stmt = $conn->prepare("UPDATE leaves SET status = ?, admin_remark = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $remark, $leave_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Leave Request $status!";
    } else {
        $_SESSION['error'] = "Action failed.";
    }
    redirect("admin_leaves.php");
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Fetch Pending
$sqlPending = "
    SELECT l.*, e.name 
    FROM leaves l 
    JOIN employees e ON l.employee_id = e.id 
    WHERE l.status = 'Pending' 
    ORDER BY l.created_at ASC
";
$resPending = $conn->query($sqlPending);

// Fetch History
$sqlHistory = "
    SELECT l.*, e.name 
    FROM leaves l 
    JOIN employees e ON l.employee_id = e.id 
    WHERE l.status != 'Pending' 
    ORDER BY l.created_at DESC 
    LIMIT 50
";
$resHistory = $conn->query($sqlHistory);

include 'header.php';
?>

<div class="nk-content">
   <div class="container-fluid">
      <div class="nk-content-inner">
         <div class="nk-content-body">
            
            <div class="nk-block-head nk-block-head-sm">
                <div class="nk-block-between">
                    <div class="nk-block-head-content">
                        <h3 class="nk-block-title page-title">Leave Management</h3>
                    </div>
                </div>
            </div>

            <!-- PENDING REQUESTS -->
            <div class="nk-block">
                <h6 class="title nk-block-title">Pending Requests</h6>
                <div class="card card-bordered">
                    <?php if ($resPending && $resPending->num_rows > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $resPending->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><span class="badge badge-warning"><?= htmlspecialchars($r['leave_type']) ?></span></td>
                                <td>
                                    <?= date('d M', strtotime($r['start_date'])) ?> - <?= date('d M', strtotime($r['end_date'])) ?>
                                    <div class="small text-muted"><?= (strtotime($r['end_date']) - strtotime($r['start_date'])) / 86400 + 1 ?> Days</div>
                                </td>
                                <td><?= htmlspecialchars($r['reason']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="openActionModal(<?= $r['id'] ?>, 'approve')"><em class="icon ni ni-check"></em></button>
                                    <button class="btn btn-sm btn-danger" onclick="openActionModal(<?= $r['id'] ?>, 'reject')"><em class="icon ni ni-cross"></em></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">No pending requests</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- HISTORY -->
            <div class="nk-block mt-4">
                <h6 class="title nk-block-title">Leave History</h6>
                <div class="card card-bordered">
                     <table class="datatable-init nk-tb-list nk-tb-ulist table table-striped" data-auto-responsive="false">
                        <thead>
                            <tr class="nk-tb-item nk-tb-head">
                                <th class="nk-tb-col">Employee</th>
                                <th class="nk-tb-col">Type</th>
                                <th class="nk-tb-col">Dates</th>
                                <th class="nk-tb-col">Status</th>
                                <th class="nk-tb-col">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($h = $resHistory->fetch_assoc()): ?>
                            <tr class="nk-tb-item">
                                <td class="nk-tb-col"><?= htmlspecialchars($h['name']) ?></td>
                                <td class="nk-tb-col"><?= htmlspecialchars($h['leave_type']) ?></td>
                                <td class="nk-tb-col">
                                    <?= date('d M', strtotime($h['start_date'])) ?> - <?= date('d M', strtotime($h['end_date'])) ?>
                                </td>
                                <td class="nk-tb-col">
                                    <?php 
                                        $badge = 'badge-light';
                                        if($h['status'] == 'Approved') $badge = 'badge-success';
                                        if($h['status'] == 'Rejected') $badge = 'badge-danger';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $h['status'] ?></span>
                                </td>
                                <td class="nk-tb-col sm"><?= htmlspecialchars($h['admin_remark']) ?></td>
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

<!-- ACTION MODAL -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="leave_id" id="leave_id">
                    <input type="hidden" name="action" id="action_type">
                    
                    <p id="confirmMsg">Are you sure?</p>
                    
                    <div class="form-group">
                        <label>Admin Remark</label>
                        <textarea name="remark" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openActionModal(id, type) {
    document.getElementById('leave_id').value = id;
    document.getElementById('action_type').value = type;
    
    document.getElementById('modalTitle').innerText = (type === 'approve' ? 'Approve Leave' : 'Reject Leave');
    document.getElementById('confirmMsg').innerText = "Are you sure you want to " + type + " this leave request?";
    
    $('#actionModal').modal('show');
}

<?php if (!empty($success)): ?>
    Swal.fire({ icon: 'success', title: '<?= $success ?>', showConfirmButton: false, timer: 1500 });
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
