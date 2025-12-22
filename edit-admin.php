<?php
// edit-admin.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$debug = true;

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    include_once('db/connection.php');

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('DB connection missing');
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // ===== INPUTS =====
    $admin_id   = intval($_POST['admin_id'] ?? 0);
    $user_id    = intval($_POST['user_id'] ?? 0);
    $name       = trim($_POST['full_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $phone_no   = trim($_POST['phone_no'] ?? '');
    $branch_id  = trim($_POST['branch_id'] ?? ''); // <-- NEW

    // ===== VALIDATION =====
    if ($admin_id <= 0 || $user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit;
    }

    if ($name === '' || $designation === '' || $phone_no === '' || $branch_id === '') {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // phone must be 10 digits
    $phone_no = preg_replace('/\D+/', '', $phone_no);
    if (!preg_match('/^\d{10}$/', $phone_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number']);
        exit;
    }

    // Validate branch exists
    $chk = $conn->prepare("SELECT id FROM branches WHERE id=? AND status=1");
    $chk->bind_param("i", $branch_id);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows === 0) {
        echo json_encode(['status'=>'error','message'=>'Invalid Branch selected']);
        exit;
    }
    $chk->close();

    // Duplicate check in admins table
    $stmt = $conn->prepare("SELECT id FROM admins WHERE phone_no=? AND id!=? LIMIT 1");
    $stmt->bind_param("si", $phone_no, $admin_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'duplicate', 'message' => 'Phone number already exists']);
        exit;
    }
    $stmt->close();

    // Duplicate check in users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE user_name=? AND id!=? LIMIT 1");
    $stmt->bind_param("si", $phone_no, $user_id);
    $stmt->execute();
    $stmt->store_result();
    // ===== UPDATE PROCESS =====
$conn->begin_transaction();


    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'duplicate', 'message' => 'Phone number already exists']);
        exit;
    }
    $stmt->close();
    // ===== CHECK IF THIS BRANCH ALREADY HAS ANOTHER SALES MANAGER =====
$chk2 = $conn->prepare("
    SELECT id 
    FROM admins 
    WHERE branch_id = ? 
      AND id != ? 
    LIMIT 1
");
$chk2->bind_param("ii", $branch_id, $admin_id);
$chk2->execute();
$chk2->store_result();

if ($chk2->num_rows > 0) {
    echo json_encode([
        'status' => 'duplicate',
        'message' => 'Sales Manager already exists for this Branch'
    ]);
    exit;
}
$chk2->close();


    // ===== UPDATE PROCESS =====
    $conn->begin_transaction();

    $now = date('Y-m-d H:i:s');

    // UPDATE users (phone_no == username)
    $sql_u = "UPDATE users SET user_name=?, updated_at=? WHERE id=?";
    $stm_u = $conn->prepare($sql_u);
    $stm_u->bind_param("ssi", $phone_no, $now, $user_id);
    $stm_u->execute();
    $stm_u->close();

    // UPDATE admins INCLUDING BRANCH
    $sql_a = "UPDATE admins 
              SET name=?, designation=?, branch_id=?, phone_no=?, updated_at=? 
              WHERE id=?";

    $stm_a = $conn->prepare($sql_a);
    $stm_a->bind_param("ssissi", 
        $name,
        $designation,
        $branch_id,
        $phone_no,
        $now,
        $admin_id
    );
    $stm_a->execute();
    $stm_a->close();

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'admin_id' => $admin_id,
        'user_id' => $user_id
    ]);
    exit;

} catch (mysqli_sql_exception $mse) {

    if ($conn->in_transaction) $conn->rollback();
    $msg = $debug ? $mse->getMessage() : "Database Error";

    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;

} catch (Throwable $e) {

    if ($conn->in_transaction) $conn->rollback();
    $msg = $debug ? $e->getMessage() : "Server Error";

    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}
?>
