<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
// Initialize variables
$name = $location = $address = "";
$errors = ['name' => '', 'location' => '', 'address' => ''];
$show_modal = false;
$success = "";
$edit_branch = null;

// Flash success message
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Handle Toggle Status
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);
    $res = mysqli_query($conn, "SELECT status FROM branches WHERE id=$toggle_id LIMIT 1");
    if ($res && mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);
        $new_status = $row['status'] == 1 ? 0 : 1;
        mysqli_query($conn, "UPDATE branches SET status=$new_status WHERE id=$toggle_id");
        $_SESSION['success'] = "Branch Status Updated!";
    }
    redirect('branch.php');
}

// Handle Edit request
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
if ($edit_id) {
    $res = mysqli_query($conn, "SELECT * FROM branches WHERE id = $edit_id LIMIT 1");
    if ($res && mysqli_num_rows($res) == 1) {
        $edit_branch = mysqli_fetch_assoc($res);
        $name = $edit_branch['name'];
        $location = $edit_branch['location'];
        $address = $edit_branch['address'];
        $show_modal = true;
    } else {
        $_SESSION['success'] = "Branch Not Found.";
        redirect('branch.php');
    }
}

// Handle Add Branch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_branch'])) {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $address = trim($_POST['address']);
    $show_modal = false;

    // Validation
    if (empty($name)) {
        $errors['name'] = "Branch name is required";
        $show_modal = true;
    }
    if (empty($location)) {
        $errors['location'] = "Branch location is required";
        $show_modal = true;
    }
    if (empty($address)) {
        $errors['address'] = "Address is required";
        $show_modal = true;
    }

    //  Duplicate check (same name + same location not allowed)
    $dup_check = mysqli_query($conn, "SELECT id FROM branches WHERE name='$name' AND location='$location' LIMIT 1");
    if (mysqli_num_rows($dup_check) > 0) {
        $errors['name'] = "Branch Name already exists.";
        $errors['location'] = "Branch Location already exists.";

        $show_modal = true;
    }

    if (!$show_modal) {
        $created_at = date('Y-m-d H:i:s');
        $created_by = 1;
        $status = 1;
        $query = "INSERT INTO branches (name, location, address, status, created_at, created_by) 
                     VALUES ('$name', '$location', '$address', '$status', '$created_at', '$created_by')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Branch Added Successfully!";
            redirect('branch.php');
        } else {
            $success = "Database Error: " . mysqli_error($conn);
        }
    }
}

// Handle Update Branch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_branch'])) {
    $branch_id = intval($_POST['branch_id']);
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $address = trim($_POST['address']);
    $show_modal = false;
    $errors = ['name' => '', 'location' => '', 'address' => ''];

    // Validation
    if (empty($name)) {
        $errors['name'] = "Branch name is required";
        $show_modal = true;
    }
    if (empty($location)) {
        $errors['location'] = "Branch location is required";
        $show_modal = true;
    }
    if (empty($address)) {
        $errors['address'] = "Address is required";
        $show_modal = true;
    }

    //  Duplicate check (exclude current record)
    $dup_check = mysqli_query($conn, "SELECT id FROM branches WHERE name='$name' AND location='$location' AND id != $branch_id LIMIT 1");
    if (mysqli_num_rows($dup_check) > 0) {
        $errors['name'] = " Branch Name  already exists.";
        $errors['location'] = "Branch Location already exists.";

        $show_modal = true;
    }

    if (!$show_modal) {
        //  updated_by field = 1 when updated
        $query = "UPDATE branches 
                     SET name='$name', location='$location', address='$address', updated_at=NOW(), updated_by=1 
                     WHERE id=$branch_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Branch Updated Successfully!";
            redirect('branch.php');
        } else {
            $success = "Database Error: " . mysqli_error($conn);
        }
    }
}

$result = mysqli_query($conn, "SELECT * FROM branches ORDER BY id DESC");

include 'header.php';
?>
<div class="nk-content">
   <div class="container-fluid">
      <div class="nk-content-inner">
         <div class="nk-content-body">
            <div class="nk-block">
               <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                     <h5 class="title">Branches</h5>
                     <button class="btn btn-primary" data-toggle="modal" data-target="#addBranchModal">
                     <em class="icon ni ni-plus"></em> Add Branch
                     </button>
                  </div>
                  <div class="card-body">
                     <table class="datatable-init nowrap nk-tb-list nk-tb-ulist table table-striped" data-auto-responsive="false">
                        <thead>
                           <tr class="nk-tb-item nk-tb-head">
                              <th class="nk-tb-col"><span class="sub-text">S.No</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Branch Name</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Location</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Address</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Status</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Actions</span></th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php
                              if (mysqli_num_rows($result) > 0) {
                                  $i = $offset + 1;
                                  while ($row = mysqli_fetch_assoc($result)) {
                                      $statusText = $row['status'] == 1 ? 'Active' : 'Inactive';
                                      $badgeClass = $row['status'] == 1 ? 'badge-success' : 'badge-danger';
                              ?>
                           <tr class="nk-tb-item">
                              <td class="nk-tb-col">                
                                 <?php echo $i++; ?>
                              </td>
                              <td class="nk-tb-col"><?php echo htmlspecialchars($row['name']); ?></td>
                              <td class="nk-tb-col"><?php echo htmlspecialchars($row['location']); ?></td>
                              <td class="nk-tb-col"><?php echo htmlspecialchars($row['address']); ?></td>
                              <td class="nk-tb-col"><span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span></td>
                              <td class="nk-tb-col">
                                 <div class="dropdown">
                                    <a href="#" class="btn btn-sm btn-icon btn-trigger dropdown-toggle" data-toggle="dropdown">
                                    <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                       <!-- <a class="dropdown-item" href="#" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">
                                       <em class="icon ni ni-eye"></em><span> View</span>
                                       </a> -->
                                       <a class="dropdown-item" href="branch.php?edit_id=<?php echo $row['id']; ?>">
                                       <em class="icon ni ni-edit"></em><span> Edit</span>
                                       </a>
                                       <a class="dropdown-item" href="branch.php?toggle_id=<?php echo $row['id']; ?>">
                                       <em class="icon ni ni-power"></em><span> Change Status</span>
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
                                       <h5 class="modal-title">Branch Details</h5>
                                       <a href="branch.php" class="close" aria-label="Close"><em class="icon ni ni-cross"></em></a>
                                    </div>
                                    <div class="modal-body">
                                       <p><strong>Branch Name:</strong> <?php echo htmlspecialchars($row['name']); ?></p>
                                       <p><strong>Branch Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                                       <p><strong>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                       <p><strong>Status:</strong> <?php echo $row['status']==1 ? 'Active' : 'Inactive'; ?></p>
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
                              <td colspan="6"class='text-center text-muted p-3'>No Branches Found</td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                           </tr>
                           <?php }
                              ?>
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Add/Edit Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo $edit_branch ? 'Edit Branch' : 'Add New Branch'; ?></h5>
                <a href="branch.php" class="close" aria-label="Close"><em class="icon ni ni-cross"></em></a>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php if ($edit_branch): ?>
                        <input type="hidden" name="branch_id" value="<?php echo $edit_branch['id']; ?>">
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Branch Name</label><span class="text-danger"> * </span>
                                <input type="text" name="name" class="form-control <?php echo $errors['name'] ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter Branch Name">
                                <small class="text-danger"><?php echo $errors['name']; ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Branch Location</label><span class="text-danger"> * </span>
                                <input type="text" name="location" class="form-control <?php echo $errors['location'] ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($location); ?>" placeholder="Enter Location">
                                <small class="text-danger"><?php echo $errors['location']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label><span class="text-danger"> * </span>
                        <textarea name="address" class="form-control <?php echo $errors['address'] ? 'is-invalid' : ''; ?>" placeholder="Enter Full Address"><?php echo htmlspecialchars($address); ?></textarea>
                        <small class="text-danger"><?php echo $errors['address']; ?></small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="window.location='branch.php'">Cancel</button>
                        <button type="submit" name="<?php echo $edit_branch ? 'update_branch' : 'save_branch'; ?>" class="btn btn-primary">
                            <?php echo $edit_branch ? 'Update Branch' : 'Save Branch'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php if ($show_modal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#addBranchModal').modal('show');
        });
    </script>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '<?php echo $success; ?>',
                showConfirmButton: false,
                timer: 1500
            });
        });
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>