<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$employee_id = $note = "";
$errors = ['employee_id' => '', 'note' => ''];
$show_modal = false;
$success = "";
$edit_note = null;
$current_user_id = $_SESSION['user_id'];

// Handle Flash Messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errors['global'] = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle Delete (Soft Delete)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $deleted_at = date('Y-m-d H:i:s');
    $query = "UPDATE employee_note SET deleted_at='$deleted_at', deleted_by='$current_user_id' WHERE id=$delete_id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Note Deleted Successfully!";
    } else {
        $_SESSION['error'] = "Failed to Delete Note.";
    }
    redirect('admin_notes.php');
}

// Handle Edit Request
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
if ($edit_id) {
    $res = mysqli_query($conn, "SELECT * FROM employee_note WHERE id = $edit_id AND deleted_at IS NULL LIMIT 1");
    if ($res && mysqli_num_rows($res) == 1) {
        $edit_note = mysqli_fetch_assoc($res);
        $employee_id = $edit_note['employee_id'];
        $note = $edit_note['note'];
        $show_modal = true;
    } else {
        $_SESSION['error'] = "Note Not Found.";
        redirect('admin_notes.php');
    }
}

// Handle Save/Update Note
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['save_note']) || isset($_POST['update_note'])) {
        $employee_id = intval($_POST['employee_id']);
        $note = trim($_POST['note']);
        $show_modal = false;

        // Validation
        if (empty($employee_id)) {
            $errors['employee_id'] = "Please select an employee";
            $show_modal = true;
        }
        if (empty($note) && $note !== '0') { // Allow '0' as note? probably not, but good check
            $errors['note'] = "Note content is required";
            $show_modal = true;
        }

        if (!$show_modal) {
            if (isset($_POST['update_note']) && $edit_id) {
                // Update
                $query = "UPDATE employee_note SET employee_id='$employee_id', note='$note' WHERE id=$edit_id";
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = "Note Updated Successfully!";
                    redirect('admin_notes.php');
                } else {
                    $errors['global'] = "Database Error: " . mysqli_error($conn);
                    $show_modal = true; // Show error
                }
            } else {
                // Insert
                $created_at = date('Y-m-d H:i:s');
                $query = "INSERT INTO employee_note (employee_id, note, created_at, created_by) 
                          VALUES ('$employee_id', '$note', '$created_at', '$current_user_id')";
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = "Note Added Successfully!";
                    redirect('admin_notes.php');
                } else {
                    $errors['global'] = "Database Error: " . mysqli_error($conn);
                    $show_modal = true;
                }
            }
        }
    }
}

// Fetch Notes with Employee Names
$sql = "SELECT n.*, e.name as employee_name 
        FROM employee_note n 
        LEFT JOIN employees e ON n.employee_id = e.id 
        WHERE n.deleted_at IS NULL 
        ORDER BY n.created_at DESC";
$notes_result = mysqli_query($conn, $sql);

// Fetch Employees for Dropdown
$emp_sql = "SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name ASC";
// If status column doesn't exist (remember dashboard check?), just select all
// The user previously removed 'status' check from dashboard because 'status' col didn't exist.
// So I should just select all or check if status exists.
// Based on previous interaction, employees table has NO status column.
$emp_sql = "SELECT id, name FROM employees ORDER BY name ASC";
$emp_result = mysqli_query($conn, $emp_sql);
$employees = [];
if ($emp_result) {
    while ($r = mysqli_fetch_assoc($emp_result)) {
        $employees[] = $r;
    }
}

include 'header.php';
?>

<div class="nk-content">
   <div class="container-fluid">
      <div class="nk-content-inner">
         <div class="nk-content-body">
            <div class="nk-block">
               <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                     <h5 class="title">Employee Notes</h5>
                     <button class="btn btn-primary" data-toggle="modal" data-target="#addNoteModal">
                     <em class="icon ni ni-plus"></em> Add Note
                     </button>
                  </div>
                  <div class="card-body">
                     <?php if (isset($errors['global'])) echo "<div class='alert alert-danger'>".$errors['global']."</div>"; ?>
                     <table class="datatable-init nk-tb-list nk-tb-ulist table table-striped" data-auto-responsive="false">
                        <thead>
                           <tr class="nk-tb-item nk-tb-head">
                              <th class="nk-tb-col"><span class="sub-text">#</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Employee</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Note</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Date</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Actions</span></th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php
                              if ($notes_result && mysqli_num_rows($notes_result) > 0) {
                                  $i = 1;
                                  while ($row = mysqli_fetch_assoc($notes_result)) {
                              ?>
                           <tr class="nk-tb-item">
                              <td class="nk-tb-col"><?php echo $i++; ?></td>
                              <td class="nk-tb-col">
                                  <span class="tb-lead"><?php echo htmlspecialchars($row['employee_name'] ?? 'Unknown'); ?></span>
                              </td>
                              <td class="nk-tb-col">
                                  <?php 
                                    $noteText = htmlspecialchars($row['note']);
                                    echo (strlen($noteText) > 50) ? substr($noteText, 0, 50) . '...' : $noteText; 
                                  ?>
                              </td>
                              <td class="nk-tb-col"><?php echo date('d M Y h:i A', strtotime($row['created_at'])); ?></td>
                              <td class="nk-tb-col">
                                 <div class="dropdown">
                                    <a href="#" class="btn btn-sm btn-icon btn-trigger dropdown-toggle" data-toggle="dropdown">
                                    <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                       <a class="dropdown-item" href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">
                                       <em class="icon ni ni-eye"></em><span> View</span>
                                       </a>
                                       <a class="dropdown-item" href="admin_notes.php?edit_id=<?php echo $row['id']; ?>">
                                       <em class="icon ni ni-edit"></em><span> Edit</span>
                                       </a>
                                       <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                       <em class="icon ni ni-trash"></em><span> Delete</span>
                                       </a>
                                    </div>
                                 </div>
                              </td>
                           </tr>

                           <!-- View Modal -->
                           <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1">
                              <div class="modal-dialog modal-dialog-centered">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h5 class="modal-title">Note Details</h5>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                           <em class="icon ni ni-cross"></em>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><strong>Employee:</strong> <?php echo htmlspecialchars($row['employee_name']); ?></p>
                                       <p><strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($row['created_at'])); ?></p>
                                       <hr>
                                       <p><strong>Note:</strong><br><?php echo nl2br(htmlspecialchars($row['note'])); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                       <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <?php
                                  }
                              } else { ?>
                           <tr>
                              <td colspan="5" class='text-center text-muted p-3'>No Notes Found</td>
                           </tr>
                           <?php } ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $edit_note ? 'Edit Note' : 'Add New Note'; ?></h5>
                <a href="admin_notes.php" class="close" aria-label="Close"><em class="icon ni ni-cross"></em></a>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php if ($edit_note): ?>
                        <!-- preserve edit_id in URL or hidden field? The form action defaults to current URL. -->
                        <!-- If I am in ?edit_id=X, posting will keep it. But for safety I can use hidden input not strictly needed if URL param exists, but standard practice. -->
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Select Employee</label><span class="text-danger"> * </span>
                        <div class="form-control-wrap">
                            <select class="form-select <?php echo $errors['employee_id'] ? 'is-invalid' : ''; ?>" name="employee_id" data-search="on">
                                <option value="">-- Select Employee --</option>
                                <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo ($employee_id == $emp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if($errors['employee_id']): ?><small class="text-danger"><?php echo $errors['employee_id']; ?></small><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Note</label><span class="text-danger"> * </span>
                        <div class="form-control-wrap">
                            <textarea class="form-control <?php echo $errors['note'] ? 'is-invalid' : ''; ?>" name="note" rows="5" placeholder="Enter note details..."><?php echo htmlspecialchars($note); ?></textarea>
                            <?php if($errors['note']): ?><small class="text-danger"><?php echo $errors['note']; ?></small><?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="window.location='admin_notes.php'">Cancel</button>
                        <button type="submit" name="<?php echo $edit_note ? 'update_note' : 'save_note'; ?>" class="btn btn-primary">
                            <?php echo $edit_note ? 'Update Note' : 'Save Note'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'admin_notes.php?delete_id=' + id;
            }
        });
    }

    <?php if ($show_modal): ?>
    document.addEventListener('DOMContentLoaded', function() {
        $('#addNoteModal').modal('show');
    });
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: '<?php echo $success; ?>',
            showConfirmButton: false,
            timer: 1500
        });
    });
    <?php endif; ?>
</script>

<?php include 'footer.php'; ?>
