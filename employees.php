<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

// -------------------- INIT --------------------
$emp_name = $emp_designation = $emp_phone = "";
$emp_title = $emp_dob = $emp_email = "";
$emp_sales_manager = "";   // <--- keep selected SM

// Error array – all keys in one place
$errors = [
    'Employee No'         => '',
    'name'          => '',
    'designation'   => '',
    'phone'         => '',
    'dob'           => '',
    'email'         => '',
    'sales_manager' => '',
];

$show_modal = false;
$success = "";

// Flash success message
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// -------------------- TOGGLE STATUS --------------------
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);
    $res = mysqli_query($conn, "SELECT status FROM users WHERE id=$toggle_id LIMIT 1");
    if ($res && mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);
        $new_status = $row['status'] == 1 ? 0 : 1;
        mysqli_query($conn, "UPDATE users SET status=$new_status WHERE id=$toggle_id");
        $_SESSION['success'] = "Sales Executive Status Updated!";
    }
    redirect('employees.php');
}

// -------------------- EDIT (LOAD DATA INTO MODAL) --------------------
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_employee = null;

if ($edit_id) {
    $res = mysqli_query($conn, "SELECT e.*, u.user_name, u.id AS user_id 
                                FROM employees e 
                                LEFT JOIN users u ON e.user_id = u.id 
                                WHERE e.id = $edit_id LIMIT 1");
    if ($res && mysqli_num_rows($res) == 1) {
        $edit_employee      = mysqli_fetch_assoc($res);
        $emp_title          = $edit_employee['title'] ?? '';              // avoid NULL
        $emp_name           = $edit_employee['name'];
        $emp_designation    = $edit_employee['designation'];
        $emp_phone          = $edit_employee['phone_no'];
        $emp_dob            = $edit_employee['dob'] ? $edit_employee['dob'] : '';   // for input[type=date]
        $emp_email          = $edit_employee['email'] ?? '';
        $emp_sales_manager  = $edit_employee['sales_manager_id'] ?? '';   // <--- SM from DB
        $show_modal = true;
    } else {
        $_SESSION['success'] = "Sales Executive Not Found.";
        redirect('employees.php');
    }
}

// -------------------- ADD / UPDATE EMPLOYEE --------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_employee'])) {

    $emp_id          = isset($_POST['emp_id']) ? intval($_POST['emp_id']) : 0;
    $emp_title       = trim($_POST['emp_title'] ?? '');
    $emp_name        = trim($_POST['emp_name'] ?? '');
    $emp_designation = trim($_POST['emp_designation'] ?? '');
    $emp_phone       = trim($_POST['emp_phone'] ?? '');
    $emp_dob         = trim($_POST['emp_dob'] ?? '');
    $emp_email       = trim($_POST['emp_email'] ?? '');
    $emp_sales_manager = trim($_POST['sales_manager_id'] ?? ''); // <--- SM from form

    $show_modal = false;

    // // ---- Validation ----
    // if (empty($emp_title)) {
    //     $errors['title'] = "Title is required";
    //     $show_modal = true;
    // }

    if (empty($emp_name)) {
        $errors['name'] = "Full name is required";
        $show_modal = true;
    }

    if (empty($emp_designation)) {
        $errors['designation'] = "Designation is required";
        $show_modal = true;
    }

    if (empty($emp_phone)) {
        $errors['phone'] = "Phone number is required";
        $show_modal = true;
    } elseif (!preg_match('/^\d{10}$/', $emp_phone)) {
        $errors['phone'] = "Enter valid 10-digit phone number";
        $show_modal = true;
    }

    // DOB required
    // DOB validation
if (empty($emp_dob)) {
    $errors['dob'] = "Date of Birth is required";
    $show_modal = true;
} else {
    // Cannot select future date
    $today = date('Y-m-d');
    if ($emp_dob > $today) {
        $errors['dob'] = "Date of Birth cannot be in the future.";
        $show_modal = true;
    }

    // Optional: Minimum age check (uncomment if needed)
    // $minAge = date('Y-m-d', strtotime('-18 years'));
    // if ($emp_dob > $minAge) {
    //     $errors['dob'] = "Employee must be at least 18 years old.";
    //     $show_modal = true;
    // }
}


    // Email required + format
    if (empty($emp_email)) {
        $errors['email'] = "Email is required";
        $show_modal = true;
    } elseif (!filter_var($emp_email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid Email Address";
        $show_modal = true;
    }

    // Sales Manager required
    if (empty($emp_sales_manager)) {
        $errors['sales_manager'] = "Sales Manager is required";
        $show_modal = true;
    }

    // Email duplicate
    if (!$show_modal && !empty($emp_email)) {
        $email_esc = mysqli_real_escape_string($conn, $emp_email);
        $exclude = $emp_id > 0 ? " AND id != $emp_id " : "";
        $dup_mail = mysqli_query(
            $conn,
            "SELECT id FROM employees WHERE email='$email_esc' $exclude LIMIT 1"
        );
        if ($dup_mail && mysqli_num_rows($dup_mail) > 0) {
            $errors['email'] = "Email already exists!";
            $show_modal = true;
        }
    }

    // Phone duplicate
    if (!$show_modal) {
        $phone_esc = mysqli_real_escape_string($conn, $emp_phone);
        $exclude = $emp_id > 0 ? " AND id != $emp_id " : "";
        $dup_check = mysqli_query(
            $conn,
            "SELECT id FROM employees 
             WHERE phone_no='$phone_esc' $exclude
             LIMIT 1"
        );
        if ($dup_check && mysqli_num_rows($dup_check) > 0) {
            $errors['phone'] = "Phone number already exists.";
            $show_modal = true;
        }
    }

    // If validation OK -> insert/update
    if (!$show_modal) {

        $created_at      = date('Y-m-d H:i:s');
        $current_user_id = intval($_SESSION['user_id'] ?? 1);

        // Prepare escaped values
        $e_title_esc   = mysqli_real_escape_string($conn, $emp_title);
        $e_name_esc    = mysqli_real_escape_string($conn, $emp_name);
        $e_design_esc  = mysqli_real_escape_string($conn, $emp_designation);
        $e_phone_esc   = mysqli_real_escape_string($conn, $emp_phone);
        $e_email_esc   = mysqli_real_escape_string($conn, $emp_email);
        $e_dob_esc     = mysqli_real_escape_string($conn, $emp_dob);
        $e_sales_manager = intval($emp_sales_manager);

        // SQL-ready values for nullable fields
        $dob_sql   = ($emp_dob   !== '') ? "'$e_dob_esc'"   : "NULL";
        $email_sql = ($emp_email !== '') ? "'$e_email_esc'" : "NULL";

        // ---------- UPDATE ----------
        if ($emp_id > 0) {

            $updated_at = date('Y-m-d H:i:s');
            $updated_by = $current_user_id;

            $update_sql = "
                UPDATE employees SET
                   title            = " . ($emp_title !== '' ? "'$e_title_esc'" : "'-'") . ",

                    name             = '$e_name_esc',
                    designation      = '$e_design_esc',
                    phone_no         = '$e_phone_esc',
                    dob              = $dob_sql,
                    email            = $email_sql,
                    sales_manager_id = $e_sales_manager,
                    updated_at       = '$updated_at',
                    updated_by       = $updated_by
                WHERE id = $emp_id
            ";

            if (mysqli_query($conn, $update_sql)) {

                // update related user (login) record
                $u_res = mysqli_query($conn, "SELECT user_id FROM employees WHERE id = $emp_id LIMIT 1");
                if ($u_res && mysqli_num_rows($u_res) == 1) {
                    $u_row = mysqli_fetch_assoc($u_res);
                    $user_id = intval($u_row['user_id']);

                    $u_name_esc = mysqli_real_escape_string($conn, $e_phone_esc);
                    $upd_user_sql = "
                        UPDATE users SET
                            user_name  = '$u_name_esc',
                            updated_at = '$updated_at',
                            updated_by = $updated_by
                        WHERE id = $user_id
                    ";
                    mysqli_query($conn, $upd_user_sql);
                }

                $_SESSION['success'] = "Sales Executive Updated Successfully!";
                redirect('employees.php');
            } else {
                $success = "Database Error (update employees): " . mysqli_error($conn);
                $show_modal = true;
            }
        } else {
            // ---------- INSERT ----------

            $status        = 1;
            $username      = $emp_phone;
            $password_hash = password_hash($emp_phone, PASSWORD_BCRYPT);
            $role_id       = 3;

            $u_name_esc = mysqli_real_escape_string($conn, $username);
            $u_pass_esc = mysqli_real_escape_string($conn, $password_hash);

            $query1 = "INSERT INTO users 
                        (user_name, password, email, status, role_id, created_at, created_by)
                       VALUES 
                        ('$u_name_esc', '$u_pass_esc', NULL, $status, $role_id, '$created_at', $current_user_id)";

            $newEmployeeID = "";
            // Generate employee ID like EMP_0001
            $getLast = mysqli_query($conn, "SELECT employee_id FROM employees ORDER BY id DESC LIMIT 1");

            if ($getLast && mysqli_num_rows($getLast) > 0) {
                $last = mysqli_fetch_assoc($getLast);
                $lastCode = $last['employee_id']; // example: EMP_0007

                $num = intval(substr($lastCode, 4)); // extract number part
                $num = $num + 1;

                // 4 digits padding
                $newEmployeeID = "EMP_" . str_pad($num, 4, "0", STR_PAD_LEFT);
            } else {
                // First record
                $newEmployeeID = "EMP_0001";
            }

            if (mysqli_query($conn, $query1)) {
                $user_id = mysqli_insert_id($conn);

                $query2 = "
                   INSERT INTO employees 
                      (employee_id, user_id, title, name, designation, phone_no, dob, email, sales_manager_id, created_at, created_by)
                   VALUES 
                      ('$newEmployeeID', $user_id, '$e_title_esc', '$e_name_esc', '$e_design_esc', '$e_phone_esc',
                       $dob_sql, $email_sql, $e_sales_manager, '$created_at', $current_user_id)
                ";

                if (mysqli_query($conn, $query2)) {
                    $_SESSION['success'] = "Sales Executive Added Successfully!";
                    redirect('employees.php');
                } else {
                    $success = "Database Error (employees): " . mysqli_error($conn);
                    // rollback user insert
                    mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
                    $show_modal = true;
                }
            } else {
                $success = "Database Error (users): " . mysqli_error($conn);
                $show_modal = true;
            }
        }
    }
}

// -------------------- FETCH LIST --------------------
$sql = "SELECT e.*, u.user_name, u.status AS user_status,
               u.updated_at AS user_updated_at, u.updated_by AS user_updated_by
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY e.id DESC";

$result = mysqli_query($conn, $sql);

include 'header.php';
?>
<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="components-preview mx-auto">
                    <div class="nk-block nk-block-lg">
                        <div class="nk-block-head">
                            <div class="nk-block-head-content">
                                <h4 class="nk-block-title">
                                    Sales Executives
                                    <span class="float-right">
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addEmployeeModal">Add Sales Executive</button>
                                    </span>
                                </h4>
                            </div>
                        </div>
                        <div class="card card-preview">
                            <div class="card-inner">
                                <div class="table-responsive">
                                    <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="false">
                                        <thead>
                                            <tr class="nk-tb-item nk-tb-head">
                                                <th class="nk-tb-col"><span class="sub-text">Emp_Id</span></th>
                                                <th class="nk-tb-col"><span class="sub-text">Employee No </span></th>
                                                <th class="nk-tb-col"><span class="sub-text">Name</span></th>
                                                <th class="nk-tb-col tb-col-mb"><span class="sub-text">Phone No</span></th>
                                                <th class="nk-tb-col tb-col-md"><span class="sub-text">Email</span></th>
                                                <th class="nk-tb-col tb-col-md"><span class="sub-text">DOB</span></th>
                                                <th class="nk-tb-col tb-col-md"><span class="sub-text">Designation</span></th>
                                                <th class="nk-tb-col tb-col-md"><span class="sub-text">Created At</span></th>
                                                <th class="nk-tb-col tb-col-md"><span class="sub-text">Status</span></th>
                                                <th class="nk-tb-col nk-tb-col-tools text-right"><span class="sub-text">Actions</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result && mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $initials = '';
                                                    $parts = explode(' ', $row['name']);
                                                    foreach ($parts as $p) {
                                                        $initials .= strtoupper(substr($p, 0, 1));
                                                    }
                                                    $statusText = $row['user_status'] == 1 ? 'Active' : 'Inactive';
                                                    $badgeClass = $row['user_status'] == 1 ? 'badge-success' : 'badge-danger';
                                            ?>
                                                    <tr class="nk-tb-item">
                                                        <!-- Emp ID -->
                                                        <td class="nk-tb-col">
                                                            <span><?php echo htmlspecialchars($row['employee_id']); ?></span>
                                                        </td>

                                                        <!-- Title -->
                                                        <td class="nk-tb-col">
<span><?php echo $row['title'] !== '' ? htmlspecialchars($row['title']) : '-'; ?></span>
                                                        </td>

                                                        <!-- Name -->
                                                        <td class="nk-tb-col">
                                                            <div class="user-card">
                                                                <div class="user-avatar bg-dim-primary d-none d-sm-flex">
                                                                    <span><?php echo htmlspecialchars(substr($initials, 0, 2)); ?></span>
                                                                </div>
                                                                <div class="user-info">
                                                                    <span class="tb-lead"><?php echo htmlspecialchars($row['name']); ?></span>
                                                                </div>
                                                            </div>
                                                        </td>

                                                        <!-- Phone -->
                                                        <td class="nk-tb-col tb-col-mb">
                                                            <span><?php echo htmlspecialchars($row['phone_no']); ?></span>
                                                        </td>

                                                        <!-- Email -->
                                                        <td class="nk-tb-col tb-col-md">
                                                            <span><?php echo htmlspecialchars($row['email'] ?? ''); ?></span>
                                                        </td>

                                                        <!-- DOB -->
                                                        <td class="nk-tb-col tb-col-md">
                                                            <span>
                                                                <?php
                                                                if (!empty($row['dob'])) {
                                                                    echo date("d-m-Y", strtotime($row['dob']));
                                                                } else {
                                                                    echo "-";
                                                                }
                                                                ?>
                                                            </span>
                                                        </td>

                                                        <!-- Designation -->
                                                        <td class="nk-tb-col tb-col-md">
                                                            <span><?php echo htmlspecialchars($row['designation']); ?></span>
                                                        </td>

                                                        <!-- Created At -->
                                                        <td class="nk-tb-col tb-col-md">
                                                            <span><?php echo date("d-m-Y", strtotime($row['created_at'])); ?></span>
                                                        </td>

                                                        <!-- Status -->
                                                        <td class="nk-tb-col tb-col-md">
                                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                                        </td>

                                                        <!-- Actions -->
                                                        <td class="nk-tb-col nk-tb-col-tools">
                                                            <ul class="nk-tb-actions gx-1">
                                                                <li>
                                                                    <div class="drodown">
                                                                        <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-toggle="dropdown">
                                                                            <em class="icon ni ni-more-h"></em>
                                                                        </a>
                                                                        <div class="dropdown-menu dropdown-menu-right">
                                                                            <ul class="link-list-opt no-bdr">
                                                                                <!-- <li><a href="#"><em class="icon ni ni-shield-star"></em><span>Reset Pass</span></a></li> -->
                                                                                <li><a href="employees.php?edit_id=<?php echo $row['id']; ?>"><em class="icon ni ni-edit"></em><span>Edit</span></a></li>
                                                                                <li><a href="employees.php?toggle_id=<?php echo $row['user_id']; ?>"><em class="icon ni ni-power"></em><span>Change Status</span></a></li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                            } else { ?>
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted p-3">No Sales Executives Found</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- .card-preview -->
                        </div>
                        <!-- nk-block -->
                    </div>
                    <!-- .components-preview -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $edit_employee ? 'Edit Sales Executive' : 'Add New Sales Executive'; ?></h5>
                    <a href="employees.php" class="close" aria-label="Close"><em class="icon ni ni-cross"></em></a>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <?php if ($edit_employee): ?>
                            <input type="hidden" name="emp_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>
<div class="form-group">
    <label class="form-label">Employee Number</label>
    <input type="text" name="emp_title"
           class="form-control"
           value="<?php echo htmlspecialchars($emp_title); ?>"
           placeholder="Enter Employee Number">
</div>


                        <div class="form-group">
                            <label class="form-label">Full Name</label><span class="text-danger"> * </span>
                            <input type="text" name="emp_name"
                                   class="form-control <?php echo $errors['name'] ? 'is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($emp_name); ?>" placeholder="Enter Full Name">
                            <small class="text-danger"><?php echo $errors['name']; ?></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Designation</label><span class="text-danger"> * </span>
                            <input type="text" name="emp_designation"
                                   class="form-control <?php echo $errors['designation'] ? 'is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($emp_designation); ?>" placeholder="Enter Designation">
                            <small class="text-danger"><?php echo $errors['designation']; ?></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label><span class="text-danger"> * </span>
                            <input type="text" name="emp_phone"
                                   class="form-control <?php echo $errors['phone'] ? 'is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($emp_phone); ?>" placeholder="Enter Phone Number">
                            <small class="text-danger"><?php echo $errors['phone']; ?></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="emp_dob"
       max="<?php echo date('Y-m-d'); ?>"
       class="form-control <?php echo $errors['dob'] ? 'is-invalid' : ''; ?>"
       value="<?php echo htmlspecialchars($emp_dob); ?>">

                            <small class="text-danger"><?php echo $errors['dob']; ?></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="emp_email"
                                   class="form-control <?php echo $errors['email'] ? 'is-invalid' : ''; ?>"
                                   value="<?php echo htmlspecialchars($emp_email); ?>" placeholder="Enter Email">
                            <small class="text-danger"><?php echo $errors['email']; ?></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sales Manager <span class="text-danger">*</span></label>
                            <select name="sales_manager_id"
                                    class="form-control <?php echo $errors['sales_manager'] ? 'is-invalid' : ''; ?>">
                                <option value="">Select Sales Manager</option>
                                <?php
                                // 1) If form posted with errors -> keep posted value
                                // 2) Else if editing and DB has value -> use that
                                $current_sm = '';
                                if ($emp_sales_manager !== '') {
                                    $current_sm = $emp_sales_manager;
                                } elseif (!empty($edit_employee['sales_manager_id'])) {
                                    $current_sm = $edit_employee['sales_manager_id'];
                                }

                                $q = mysqli_query($conn, "
                                    SELECT a.id, a.name 
                                    FROM admins a
                                    LEFT JOIN users u ON a.user_id = u.id
                                    WHERE u.status = 1
                                    ORDER BY a.name  ASC
                                ");

                                while ($sm = mysqli_fetch_assoc($q)) {
                                    $sel = ((string)$current_sm === (string)$sm['id']) ? 'selected' : '';
                                    echo '<option value="' . $sm['id'] . '" ' . $sel . '>' . $sm['name'] . '</option>';
                                }
                                ?>
                            </select>
                            <small class="text-danger"><?php echo $errors['sales_manager']; ?></small>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="window.location='employees.php'">Cancel</button>
                            <button type="submit" name="save_employee" class="btn btn-primary">Save Sales Executive</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($show_modal): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $('#addEmployeeModal').modal('show');
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
