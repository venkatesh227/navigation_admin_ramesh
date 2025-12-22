<?php
// add_admin.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$debug = true; // change to false in production
define('ROLE_ID_FOR_ADMIN', 2);

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    include_once('db/connection.php');
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('DB connection missing');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status'=>'error','message'=>'Method not allowed']);
        exit;
    }

    // Input fields
    $name        = trim($_POST['full_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $phone_no    = trim($_POST['phone_no'] ?? '');
    $branch_id   = trim($_POST['branch_id'] ?? '');

    // ===== Validation =====
    if ($name === '' || $designation === '' || $phone_no === '' || $branch_id === '') {
        echo json_encode(['status'=>'error','message'=>'All fields including Branch are required']);
        exit;
    }

    // Phone normalize
    $phone_no = preg_replace('/\D+/', '', $phone_no);
    if (!preg_match('/^\d{10}$/', $phone_no)) {
        echo json_encode(['status'=>'error','message'=>'Invalid phone number (10 digits required)']);
        exit;
    }


    // ============================================================
    // FIX 1: Accept all branches, do not check status
    // ============================================================
    $chkBranch = $conn->prepare("SELECT id FROM branches WHERE id=?");
    $chkBranch->bind_param("i", $branch_id);
    $chkBranch->execute();
    $chkBranch->store_result();
    if ($chkBranch->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Invalid branch selected']);
        exit;
    }
    $chkBranch->close();


    // ============================================================
    // FIX 2: CHECK IF THIS BRANCH ALREADY HAS A SALES MANAGER
    // ============================================================
    $branch_id = mysqli_real_escape_string($conn, $branch_id);

    $checkSQL = "
        SELECT id 
        FROM admins 
        WHERE branch_id = '$branch_id'
        LIMIT 1
    ";

    $checkResult = mysqli_query($conn, $checkSQL);

    if (mysqli_num_rows($checkResult) > 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'This branch already has a Sales Manager'
        ]);
        exit;
    }


    $created_by = $_SESSION['user_id'] ?? 0;
    $now = date('Y-m-d H:i:s');

    // Generate temp password
    try { 
        $temp_pass = bin2hex(random_bytes(4)); 
    } catch (Exception $e) { 
        $temp_pass = substr(md5(uniqid('',true)),0,8); 
    }

    $hashed = password_hash($temp_pass, PASSWORD_BCRYPT);

    // Start transaction
    $conn->begin_transaction();

    // Check duplicate phone
    $stmt = $conn->prepare("SELECT id FROM admins WHERE phone_no=? LIMIT 1");
    $stmt->bind_param("s", $phone_no);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['status'=>'duplicate','message'=>'Phone number already exists']);
        exit;
    }
    $stmt->close();

    // Insert into users table
    $sql1 = "INSERT INTO users (user_name, password, email, status, role_id, created_at, created_by)
             VALUES (?, ?, '', 1, ?, ?, ?)";

    $st1 = $conn->prepare($sql1);
    $roleid = ROLE_ID_FOR_ADMIN;
    $st1->bind_param("sssii", $phone_no, $hashed, $roleid, $now, $created_by);
    $st1->execute();
    $new_user_id = $st1->insert_id;
    $st1->close();

    // Insert into admins table (NO status_int column)
    $sql2 = "INSERT INTO admins (user_id, name, designation, branch_id, phone_no, created_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)";

    $st2 = $conn->prepare($sql2);
    $st2->bind_param("ississi",
        $new_user_id,
        $name,
        $designation,
        $branch_id,
        $phone_no,
        $now,
        $created_by
    );
    $st2->execute();
    $new_admin_id = $st2->insert_id;
    $st2->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status'      => 'success',
        'admin_id'    => $new_admin_id,
        'user_id'     => $new_user_id,
        'created_at'  => $now
    ]);
    exit;

} catch (mysqli_sql_exception $mse) {
    if ($conn->in_transaction) $conn->rollback();
    echo json_encode(['status'=>'error','message'=>$debug?$mse->getMessage():'DB error']);
    exit;

} catch (Throwable $e) {
    if ($conn->in_transaction) $conn->rollback();
    echo json_encode(['status'=>'error','message'=>$debug?$e->getMessage():'Server error']);
    exit;
}
