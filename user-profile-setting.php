<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$user_id = $_SESSION['user_id'] ?? 0;
$success_msg = '';
$error_msg = '';

// FETCH CURRENT USER & ADMIN DETAILS
$stmt = $conn->prepare("
    SELECT u.user_name, u.role_id, a.name AS full_name 
    FROM users u 
    LEFT JOIN admins a ON u.id = a.user_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$currentUser = $res->fetch_assoc();
$stmt->close();

if (!$currentUser) {
    header("Location: index.php");
    exit;
}

// HANDLE FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PROFILE INFO (Name & Username)
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['user_name']);
        $new_fullname = trim($_POST['full_name']);
        
        if ($new_username) {
            // Check duplicate username
            $chk = $conn->prepare("SELECT id FROM users WHERE user_name = ? AND id != ?");
            $chk->bind_param("si", $new_username, $user_id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error_msg = "Username already exists.";
            } else {
                // Update Users table
                $upd = $conn->prepare("UPDATE users SET user_name = ? WHERE id = ?");
                $upd->bind_param("si", $new_username, $user_id);
                $upd->execute();
                $upd->close();

                // Update/Insert Admins table
                // Check if admin record exists
                $chkAdmin = $conn->query("SELECT id FROM admins WHERE user_id = $user_id");
                if ($chkAdmin && $chkAdmin->num_rows > 0) {
                    $updAdmin = $conn->prepare("UPDATE admins SET name = ? WHERE user_id = ?");
                    $updAdmin->bind_param("si", $new_fullname, $user_id);
                    $updAdmin->execute();
                    $updAdmin->close();
                } else {
                    // Create if missing (unlikely now, but good for safety)
                    $insAdmin = $conn->prepare("INSERT INTO admins (user_id, name, created_at) VALUES (?, ?, NOW())");
                    $insAdmin->bind_param("is", $user_id, $new_fullname);
                    $insAdmin->execute();
                    $insAdmin->close();
                }

                $_SESSION['user_name'] = $new_username; // Update session
                $success_msg = "Profile updated successfully.";
                $currentUser['user_name'] = $new_username;
                $currentUser['full_name'] = $new_fullname;
            }
            $chk->close();
        } else {
            $error_msg = "Username cannot be empty.";
        }
    }

    // 2. UPDATE PASSWORD
    if (isset($_POST['update_password'])) {
        $pass    = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        if ($pass && $confirm) {
            if ($pass === $confirm) {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hash, $user_id);
                if ($upd->execute()) {
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Database Error.";
                }
                $upd->close();
            } else {
                $error_msg = "Passwords do not match.";
            }
        } else {
            $error_msg = "Please enter and confirm new password.";
        }
    }
}

include 'header.php';
?>

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Account Settings</h3>
                            <div class="nk-block-des text-soft">
                                <p>Update your profile information and password.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NOTIFICATIONS -->
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-icon">
                        <em class="icon ni ni-check-circle"></em> <?= $success_msg; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-icon">
                        <em class="icon ni ni-cross-circle"></em> <?= $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="nk-block">
                    <div class="row g-gs">
                        
                        <!-- PROFILE FORM -->
                        <div class="col-lg-6">
                            <div class="card card-bordered h-100">
                                <div class="card-inner">
                                    <div class="card-head">
                                        <h5 class="card-title">Profile Info</h5>
                                    </div>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label class="form-label">Full Name (Administrator Name)</label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" placeholder="e.g. John Doe">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Username / Login Email</label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" name="user_name" value="<?= htmlspecialchars($currentUser['user_name']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Role</label>
                                            <div class="form-control-wrap">
                                                <input type="text" class="form-control" value="<?= ($currentUser['role_id'] == 1) ? 'Super Admin' : 'User' ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- PASSWORD FORM -->
                        <div class="col-lg-6">
                            <div class="card card-bordered h-100">
                                <div class="card-inner">
                                    <div class="card-head">
                                        <h5 class="card-title">Change Password</h5>
                                    </div>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label class="form-label">New Password</label>
                                            <div class="form-control-wrap">
                                                <a href="#" class="form-icon form-icon-right passcode-switch lg" data-target="password">
                                                    <em class="passcode-icon icon-show icon ni ni-eye"></em>
                                                    <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
                                                </a>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Confirm Password</label>
                                            <div class="form-control-wrap">
                                                <input type="password" class="form-control" name="confirm_password" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="update_password" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </form>
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
