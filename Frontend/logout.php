<?php
ob_start();
session_start();

// 1. Database Connection
include_once('../db/connection.php');

if (isset($conn) && $conn instanceof mysqli) {
    // 2. Log Logout Activity
    // Use user_id if available (standard), else try employee_id
    $userId = $_SESSION['user_id'] ?? null;
    $empId  = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
    
    // If we only have employee_id, we might need to find the user_id if login_activity uses user_id
    if (!$userId && $empId) {
        $uRes = $conn->query("SELECT user_id FROM employees WHERE id = $empId");
        if ($uRes && $uRes->num_rows > 0) {
            $userId = $uRes->fetch_assoc()['user_id'];
        }
    }

    if ($userId) {
        $logoutLat  = $_POST['logout_latitude']  ?? '0.0'; // Default if not provided
        $logoutLong = $_POST['logout_longitude'] ?? '0.0'; // Default if not provided
        
        // Find latest login activity for this user
        $sel = $conn->prepare("SELECT id FROM login_activity WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        if ($sel) {
            $sel->bind_param('i', $userId);
            $sel->execute();
            $res = $sel->get_result();
            if ($res && $res->num_rows === 1) {
                $row = $res->fetch_assoc();
                $latestId = (int)$row['id'];
                
                // Update Logout Time & Location
                $upd = $conn->prepare("UPDATE login_activity SET logout_latitude = ?, logout_longitude = ?, logout_time = NOW() WHERE id = ?");
                if ($upd) {
                    $upd->bind_param('ssi', $logoutLat, $logoutLong, $latestId);
                    $upd->execute();
                    $upd->close();
                }
            }
            $sel->close();
        }
    }
}

// 3. Destroy Session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_unset();
session_destroy();

// 4. Redirect
if (ob_get_length()) ob_end_clean(); // Discard any output
header('Location: index.php?v=' . time());
exit;
?>
