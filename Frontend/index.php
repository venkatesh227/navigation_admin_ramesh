<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$cfile = __DIR__ . '/../db/connection.php';
if (file_exists($cfile)) {
    include_once $cfile;
} else {
    die('Database connection file not found at: ' . $cfile);
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection failed.');
}

// Already logged in
if (!empty($_SESSION['employee_id'])) {
    header('Location: dashboard.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$serverErrors = [];
$oldPhone     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $serverErrors[] = 'Invalid request (CSRF token mismatch).';
    } else {
        $phone    = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $oldPhone = htmlspecialchars($phone, ENT_QUOTES);

        // Validation
        if ($phone === '') {
            $serverErrors[] = 'Phone number is required.';
        }
        if (!preg_match('/^\d{10}$/', $phone)) {
            $serverErrors[] = 'Enter a valid 10-digit phone number.';
        }
        if ($password === '') {
            $serverErrors[] = 'Password is required.';
        }

        if (empty($serverErrors)) {

            // 1. Try to find user in `users` table first (Secure Login)
            $uStmt = $conn->prepare("SELECT id, user_name, password FROM users WHERE user_name = ? AND role_id = 2 LIMIT 1"); // role_id 2 = employee
            $userFound = false;
            $userId = null;
            
            if ($uStmt) {
                $uStmt->bind_param('s', $phone);
                $uStmt->execute();
                $uRes = $uStmt->get_result();
                if ($uRes && $uRes->num_rows === 1) {
                    $uRow = $uRes->fetch_assoc();
                    if (password_verify($password, $uRow['password'])) {
                        // User verified via separate Users table
                        $userFound = true;
                        $userId = $uRow['id'];
                        
                        // We still need employee details for session
                         $empStmt = $conn->prepare("SELECT id, name, designation, phone_no FROM employees WHERE phone_no = ?");
                         $empStmt->bind_param('s', $phone);
                         $empStmt->execute();
                         $empRes = $empStmt->get_result();
                         if ($empRow = $empRes->fetch_assoc()) {
                             $row = $empRow; // Populate $row for session setup below
                         } else {
                             $serverErrors[] = 'Account data inconsistency. Contact admin.';
                             $userFound = false;
                         }
                         $empStmt->close();
                    }
                }
                $uStmt->close();
            }

            // 2. If not found in users table or password failed, try legacy/setup check in `employees` table
            if (!$userFound) {
                 $stmt = $conn->prepare("SELECT id, name, designation, phone_no FROM employees WHERE phone_no = ? LIMIT 1");
                 if ($stmt) {
                    $stmt->bind_param('s', $phone);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows === 1) {
                        $row = $res->fetch_assoc();
                        // Auth rule: password == phone_no
                        if ($password === $row['phone_no']) {
                             $userFound = true;
                             // Proceed to sync with users table below
                        }
                    }
                    $stmt->close();
                 }
            }

            if ($userFound) {
                 // ===== LOGIN SUCCESS =====
                //  session_regenerate_id(true);
                 $_SESSION['employee_id']          = $row['id'];
                 $_SESSION['employee_name']        = $row['name'];
                 $_SESSION['employee_designation'] = $row['designation'] ?? null;
                 $_SESSION['employee_phone']       = $row['phone_no'];

                 // ===== ENSURE users ROW EXISTS (user_name = phone_no) =====
                 $userName      = $row['phone_no'];
                 $plainPassword = $row['phone_no']; // Default password if creating new
                 
                 // If we already found the userId from the first check, we are good.
                 // Otherwise we check/create.
                 if ($userId === null) {
                        // Find users row (redundant but safe for the creation flow)
                        $uStmt = $conn->prepare("SELECT id FROM users WHERE user_name = ? LIMIT 1");
                        if ($uStmt) {
                            $uStmt->bind_param('s', $userName);
                            $uStmt->execute();
                            $uRes = $uStmt->get_result();
                            if ($uRes && $uRes->num_rows === 1) {
                                $uRow   = $uRes->fetch_assoc();
                                $userId = (int)$uRow['id'];
                            }
                            $uStmt->close();
                        }
                        
                        // Create user if not exists
                        if ($userId === null) {
                            $status     = 1;
                            $role_id    = 2; // employee
                            $email      = '';
                            $created_by = 0;

                            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

                            $insertUser = $conn->prepare("
                                INSERT INTO users (user_name, password, email, status, role_id, created_at, created_by)
                                VALUES (?, ?, ?, ?, ?, NOW(), ?)
                            ");
                            if ($insertUser) {
                                $insertUser->bind_param(
                                    'sssiii',
                                    $userName,
                                    $hashedPassword,
                                    $email,
                                    $status,
                                    $role_id,
                                    $created_by
                                );
                                if ($insertUser->execute()) {
                                    $userId = $insertUser->insert_id;
                                }
                                $insertUser->close();
                            }
                        }
                 }

                 // Put users.id in session
                 if ($userId !== null) {
                     $_SESSION['user_id'] = $userId;
                 } else {
                     error_log('Could not determine users.id for phone=' . $userName);
                 }

                 // ===== LOGIN ACTIVITY INSERT =====
                 if ($userId !== null) {
                     $createdBy = $userId;

                     // Lat/Long from POST
                     $loginLat  = isset($_POST['login_latitude'])  ? trim($_POST['login_latitude'])  : '';
                     $loginLong = isset($_POST['login_longitude']) ? trim($_POST['login_longitude']) : '';

                     // If browser didn’t send location, use Vijayawada fallback
                     if ($loginLat === '' || $loginLong === '') {
                         // Vijayawada approx
                         $loginLat  = '16.5062';
                         $loginLong = '80.6480';
                     }

                     $logStmt = $conn->prepare("
                         INSERT INTO login_activity (
                             user_id,
                             login_date,
                             login_time,
                             login_latitude,
                             login_longitude,
                             created_at,
                             created_by
                         )
                         VALUES (
                             ?,          -- user_id
                             CURDATE(),  -- login_date
                             CURTIME(),  -- login_time
                             ?,          -- login_latitude
                             ?,          -- login_longitude
                             NOW(),      -- created_at
                             ?           -- created_by
                         )
                     ");
                     if ($logStmt) {
                         // user_id (int), lat (string), long (string), created_by (int)
                         $logStmt->bind_param('issi', $userId, $loginLat, $loginLong, $createdBy);
                         $logStmt->execute();
                         $logStmt->close();
                     }
                 }
                 // ===== END LOGIN ACTIVITY =====

                 header('Location: dashboard.php');
                 exit;

            } else {
                $serverErrors[] = 'Phone or password is incorrect.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Employee Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
  body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f3f6fb; }
  .login-container { background:#fff; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.12); overflow:hidden; width:100%; max-width:400px; }
  .login-header { background:linear-gradient(to right,#6a11cb,#2575fc); color:#fff; padding:30px 20px; text-align:center; }
  .login-header h2 { margin:0; font-weight:600; }
  .login-body { padding:30px; }
  .form-control { border-radius:50px; padding:12px 20px; border:1px solid #e1e1e1; }
  .form-control:focus { border-color:#6a11cb; box-shadow:0 0 0 0.25rem rgba(106,17,203,0.12); }
  .btn-login { background:linear-gradient(to right,#6a11cb,#2575fc); border:none; border-radius:50px; color:#fff; padding:12px; width:100%; font-weight:600; margin-top:10px; transition:all .3s; }
  .btn-login:hover { transform: translateY(-2px); box-shadow:0 5px 15px rgba(106,17,203,0.28); }
  .req-star { color:#d6336c; margin-left:4px; }
  .text-error { color:#dc3545; font-size:.9rem; margin-top:6px; }
  .server-errors { margin-bottom:12px; }
  @media (max-width:576px){ .login-container{ margin:20px } .login-body{ padding:20px } }
</style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <h2><i class="fas fa-user-circle me-2"></i>Employee Login</h2>
      <p class="mb-0">Sign in with your phone number</p>
    </div>

    <div class="login-body">
      <?php if (!empty($serverErrors)): ?>
        <div class="server-errors">
          <?php foreach ($serverErrors as $e): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($e, ENT_QUOTES) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="post" onsubmit="return validateLoginForm()" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

        <!-- Hidden fields for login location -->
        <input type="hidden" name="login_latitude" id="login_latitude">
        <input type="hidden" name="login_longitude" id="login_longitude">

        <div class="mb-3">
          <label for="phone" class="form-label">Phone Number <span class="req-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required value="<?= $oldPhone ?>">
          </div>
          <div id="phoneError" class="text-error" style="display:none"></div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password <span class="req-star">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
          </div>
          <div id="passwordError" class="text-error" style="display:none"></div>
        </div>

        <button type="submit" class="btn btn-login">Sign In</button>
      </form>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const phoneRegex = /^\d{10}$/;

  function showError(el, msg) {
    el.style.display = 'block';
    el.textContent = msg;
    const input = document.getElementById(el.id.replace('Error',''));
    if (input) input.classList.add('is-invalid');
  }
  function hideError(el) {
    el.style.display = 'none';
    const input = document.getElementById(el.id.replace('Error',''));
    if (input) input.classList.remove('is-invalid');
  }

  function validateLoginForm() {
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const phoneError = document.getElementById('phoneError');
    const passwordError = document.getElementById('passwordError');
    hideError(phoneError);
    hideError(passwordError);
    let valid = true;
    const phoneVal = (phone.value || '').trim();
    const pwdVal   = (password.value || '').trim();

    if (!phoneVal) {
      showError(phoneError, 'Phone number is required.');
      valid = false;
    } else if (!phoneRegex.test(phoneVal)) {
      showError(phoneError, 'Enter a valid 10-digit phone number.');
      valid = false;
    }

    if (!pwdVal) {
      showError(passwordError, 'Password is required.');
      valid = false;
    }

    if (!valid) {
      const firstInvalid = document.querySelector('.is-invalid');
      if (firstInvalid) firstInvalid.focus();
    }
    return valid;
  }

  document.getElementById('phone').addEventListener('input', function(){
    hideError(document.getElementById('phoneError'));
  });
  document.getElementById('password').addEventListener('input', function(){
    hideError(document.getElementById('passwordError'));
  });

  // ===== GEOLOCATION FOR LOGIN =====
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        var latField = document.getElementById('login_latitude');
        var lngField = document.getElementById('login_longitude');
        if (latField && lngField) {
          latField.value = pos.coords.latitude || '';
          lngField.value = pos.coords.longitude || '';
        }
      },
      function (err) {
        console.warn('Geolocation error (login):', err.message);
        // Fallback Vijayawada co-ordinates
        var latField = document.getElementById('login_latitude');
        var lngField = document.getElementById('login_longitude');
        if (latField && lngField) {
          latField.value = '16.5062';
          lngField.value = '80.6480';
        }
      }
    );
  } else {
    // Browser not supporting geolocation - fallback
    document.getElementById('login_latitude').value  = '16.5062';
    document.getElementById('login_longitude').value = '80.6480';
  }
  // ================================
</script>
</body>
</html>
