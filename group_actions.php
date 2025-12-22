<?php
session_start();
include_once("db/connection.php");

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_REQUEST['action']) ? $_REQUEST['action'] : '');
$created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$response = [
    "status" => "error",
    "message" => "",
];

// Get all routes (from database)
if ($action == 'get_routes') {
    $routeSql = "SELECT DISTINCT name,id FROM routes WHERE status = 1 ORDER BY name ASC";
    $routeRes = mysqli_query($conn, $routeSql);
    $routes = [];
    if ($routeRes && mysqli_num_rows($routeRes) > 0) {
        while ($row = mysqli_fetch_assoc($routeRes)) {
            $routes[] = [$row['name'], $row['id']];
        }
    }
    echo json_encode(['routes' => $routes]);
    exit;
}

// Handle creating a new group
if ($action == "save") {

    $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
    // 🔥 SERVER VALIDATION — ONLY ALPHABETS
if (!preg_match('/^[A-Za-z ]+$/', $group_name)) {
    echo json_encode([
        "status" => "error",
        "message" => "Only alphabets are allowed"
    ]);
    exit;
}

    $group_name_esc = mysqli_real_escape_string($conn, $group_name);

    // 1️⃣ EMPTY VALIDATION
    if ($group_name == "") {
        $response["message"] = "Group name is required";
        echo json_encode($response);
        exit;
    }

    // 2️⃣ DUPLICATE VALIDATION
    $sql = "SELECT COUNT(*) AS cnt FROM `groups` WHERE name = '$group_name_esc'";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);

    if ($row['cnt'] > 0) {
        $response["message"] = "Group name already exists";
        echo json_encode($response);
        exit;
    }

    // 3️⃣ SAVE RECORD
    $sql = "INSERT INTO `groups` (name, status, created_at, created_by)
            VALUES ('$group_name_esc', 1, NOW(), '$created_by')";

    if (mysqli_query($conn, $sql)) {
        $newId = mysqli_insert_id($conn);
        $response["status"] = "success";
        $response["id"] = $newId;
        $response["data"] = [
            'id' => $newId,
            'name' => $group_name,
            'status_code' => 1
        ];
        echo json_encode($response);
        exit;
    } else {
        $response["message"] = "Database error while saving group: " . mysqli_error($conn);
        echo json_encode($response);
        exit;
    }
}

// Toggle member status safely (adds column if missing)
if ($action === 'toggle_member_status') {
    $member_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($member_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid member id']);
        exit;
    }

    // check if members table has 'status' column
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM members LIKE 'status'");
    if (!$colRes) {
        echo json_encode(['status' => 'error', 'message' => 'DB error: ' . mysqli_error($conn)]);
        exit;
    }

    if (mysqli_num_rows($colRes) == 0) {
        // add status column default 1 (active)
        $alter = mysqli_query($conn, "ALTER TABLE members ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1");
        if (!$alter) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add status column: ' . mysqli_error($conn)]);
            exit;
        }
    }

    // read current status (if null, treat as active)
    $r = mysqli_query($conn, "SELECT status FROM members WHERE id = $member_id LIMIT 1");
    if (!$r || mysqli_num_rows($r) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        exit;
    }
    $row = mysqli_fetch_assoc($r);
    $current = isset($row['status']) ? intval($row['status']) : 1;
    $new = $current === 1 ? 0 : 1;

    $upd = mysqli_query($conn, "UPDATE members SET status = $new, updated_at = NOW(), updated_by = '$created_by' WHERE id = $member_id LIMIT 1");
    if (!$upd) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status: ' . mysqli_error($conn)]);
        exit;
    }

    echo json_encode(['status' => 'success', 'new_status' => ($new == 1 ? 'Active' : 'Inactive'), 'new_status_code' => $new]);
    exit;
}

// Handle updating an existing group (reuse Add Group modal for edit)
if ($action == 'update') {

    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
    $group_name_esc = mysqli_real_escape_string($conn, $group_name);

    if ($group_id <= 0) {
        $response['message'] = 'Invalid group id';
        echo json_encode($response);
        exit;
    }

    if ($group_name == '') {
        $response['message'] = 'Group name is required';
        echo json_encode($response);
        exit;
    }

    // duplicate check excluding current group
    $sql = "SELECT COUNT(*) AS cnt FROM `groups` WHERE name = '$group_name_esc' AND id != $group_id";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    if ($row['cnt'] > 0) {
        $response['message'] = 'Group name already exists';
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE `groups` SET name = '$group_name_esc', updated_at = NOW(), updated_by = '$created_by' WHERE id = $group_id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        $response['status'] = 'success';
        $response['id'] = $group_id;
        $response['data'] = [
            'id' => $group_id,
            'name' => $group_name
        ];
        echo json_encode($response);
        exit;
    } else {
        $response['message'] = 'Database error while updating group: ' . mysqli_error($conn);
        echo json_encode($response);
        exit;
    }
}

// Fetch a member's data for editing

// Fetch a member's data for editing
if ($action == 'get_member') {
    $member_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($member_id <= 0) {
        $response['message'] = 'Invalid member id';
        echo json_encode($response);
        exit;
    }

    $sql = "SELECT * FROM members WHERE id = $member_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $response['status'] = 'success';
        $response['data'] = $row;
        echo json_encode($response);
        exit;
    } else {
        $response['message'] = 'Member not found';
        echo json_encode($response);
        exit;
    }
}

// Update an existing member
if ($action == 'update_member') {
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $group_id = isset($_POST['group_id']) && $_POST['group_id'] !== '' ? intval($_POST['group_id']) : null;
    $route = isset($_POST['route']) ? trim($_POST['route']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : (isset($_POST['qual']) ? trim($_POST['qual']) : '');
    $clinic_name = isset($_POST['clinic_name']) ? trim($_POST['clinic_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $alt_mobile = isset($_POST['alt_mobile']) ? trim($_POST['alt_mobile']) : '';

    if ($member_id <= 0) {
        $response['message'] = 'Invalid member id';
        echo json_encode($response);
        exit;
    }

    // validate clinic name
    if ($clinic_name == '') {
        $response['status'] = 'error';
        $response['field'] = 'clinic_name';
        $response['message'] = 'Clinic name is required';
        echo json_encode($response);
        exit;
    }

    // validate other required fields
    if ($route == '' || $name == '' || $qualification == '' || $address == '' || $location == '' || $mobile == '') {
        $response['message'] = 'Please fill all required fields';
        echo json_encode($response);
        exit;
    }

    // escape
    $route_esc = mysqli_real_escape_string($conn, $route);
    $name_esc = mysqli_real_escape_string($conn, $name);
    $qualification_esc = mysqli_real_escape_string($conn, $qualification);
    $clinic_name_esc = mysqli_real_escape_string($conn, $clinic_name);
    $address_esc = mysqli_real_escape_string($conn, $address);
    $location_esc = mysqli_real_escape_string($conn, $location);
    $mobile_esc = mysqli_real_escape_string($conn, $mobile);
    $alt_mobile_esc = mysqli_real_escape_string($conn, $alt_mobile);

    // Duplicate validation: within same group, phone can only be duplicate if BOTH route AND clinic are identical
    // If route or clinic is DIFFERENT = allow (different entry)
    if ($mobile_esc !== '' && !is_null($group_id)) {
        $groupValForCheck = intval($group_id);
        $dupSql = "SELECT id, route_id, clinic_name FROM members WHERE mobile_no = '$mobile_esc' AND group_id = $groupValForCheck AND id != $member_id LIMIT 1";
        $dupRes = mysqli_query($conn, $dupSql);
        if ($dupRes && mysqli_num_rows($dupRes) > 0) {
            $dupRow = mysqli_fetch_assoc($dupRes);
            $existingRoute = $dupRow['route_id'];
            $existingClinic = $dupRow['clinic_name'];
            
            // Block ONLY if BOTH route AND clinic are EXACTLY the same (exact duplicate)
            if ($existingRoute == $route_esc && $existingClinic == $clinic_name_esc) {
                $response['status'] = 'error';
                $response['field'] = 'mobile';
                $response['message'] = 'This entry already exists (same phone, route, and clinic)';
                echo json_encode($response);
                exit;
            }
            // If route or clinic is different, allow it (different entry even with same phone)
        }
    }
    
    // NOTE: Same phone number is ALLOWED in different groups - no cross-group validation

    // Build update SQL
    $route_val = $route_esc !== '' ? "'" . $route_esc . "'" : 'NULL';
    $group_val = is_null($group_id) ? 'NULL' : "'" . intval($group_id) . "'";

    $sql = "UPDATE members SET route_id = $route_val, group_id = $group_val, name = '$name_esc', qualification = '$qualification_esc', clinic_name = '$clinic_name_esc', address = '$address_esc', village_town_city = '$location_esc', mobile_no = '$mobile_esc', alternative_no = '$alt_mobile_esc', updated_at = NOW(), updated_by = '$created_by' WHERE id = $member_id LIMIT 1";

    if (!mysqli_query($conn, $sql)) {
        $response['message'] = 'Failed to update member: ' . mysqli_error($conn);
        echo json_encode($response);
        exit;
    }

    // Fetch updated member data to return
    $fetchSql = "SELECT * FROM members WHERE id = $member_id LIMIT 1";
    $fetchRes = mysqli_query($conn, $fetchSql);
    if ($fetchRes && mysqli_num_rows($fetchRes) > 0) {
        $updatedRow = mysqli_fetch_assoc($fetchRes);
        $response['status'] = 'success';
        $response['message'] = 'Member updated successfully';
        $response['data'] = $updatedRow;
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Member updated successfully';
        $response['data'] = [];
    }
    echo json_encode($response);
    exit;
}

// Handle adding a member (insert into users and members)
if ($action == 'add_member') {

    // collect and sanitize
    $group_id = isset($_POST['group_id']) && $_POST['group_id'] !== '' ? intval($_POST['group_id']) : null;
    $route = isset($_POST['route']) ? trim($_POST['route']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : (isset($_POST['qual']) ? trim($_POST['qual']) : '');
    $clinic_name = isset($_POST['clinic_name']) ? trim($_POST['clinic_name']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
    $alt_mobile = isset($_POST['alt_mobile']) ? trim($_POST['alt_mobile']) : '';

    // field-specific validations (alt_mobile optional)
    if ($clinic_name == '') {
        $response['status'] = 'error';
        $response['field'] = 'clinic_name';
        $response['message'] = 'Clinic name is required';
        echo json_encode($response);
        exit;
    }

    // required validations for other fields (alt_mobile optional)
    if ($route == '' || $name == '' || $qualification == '' || $address == '' || $location == '' || $mobile == '') {
        $response['message'] = 'Please fill all required fields';
        echo json_encode($response);
        exit;
    }

    // escape
    $route_esc = mysqli_real_escape_string($conn, $route);
    $name_esc = mysqli_real_escape_string($conn, $name);
    $qualification_esc = mysqli_real_escape_string($conn, $qualification);
    $clinic_name_esc = mysqli_real_escape_string($conn, $clinic_name);
    $address_esc = mysqli_real_escape_string($conn, $address);
    $location_esc = mysqli_real_escape_string($conn, $location);
    $mobile_esc = mysqli_real_escape_string($conn, $mobile);
    $alt_mobile_esc = mysqli_real_escape_string($conn, $alt_mobile);

    // Duplicate validation: within same group, phone can only be duplicate if BOTH route AND clinic are identical
    // If route or clinic is DIFFERENT = allow (different entry)
    if ($mobile_esc !== '' && !is_null($group_id)) {
        $groupValForCheck = intval($group_id);
        $dupSql = "SELECT id, route_id, clinic_name FROM members WHERE mobile_no = '$mobile_esc' AND group_id = $groupValForCheck LIMIT 1";
        $dupRes = mysqli_query($conn, $dupSql);
        if ($dupRes && mysqli_num_rows($dupRes) > 0) {
            $dupRow = mysqli_fetch_assoc($dupRes);
            $existingRoute = $dupRow['route_id'];
            $existingClinic = $dupRow['clinic_name'];
            
            // Block ONLY if BOTH route AND clinic are EXACTLY the same (exact duplicate)
            if ($existingRoute == $route_esc && $existingClinic == $clinic_name_esc) {
                $response['status'] = 'error';
                $response['field'] = 'mobile';
                $response['message'] = 'This entry already exists (same phone, route, and clinic)';
                echo json_encode($response);
                exit;
            }
            // If route or clinic is different, allow it (different entry even with same phone)
        }
    }
    
    // NOTE: Same phone number is ALLOWED in different groups - no cross-group validation

    // Try to find an existing user by username or email using the provided mobile value
    // Note: the `users` table in this installation does not have a `mobile` column.
    $sql = "SELECT id FROM users WHERE user_name = '$mobile_esc' OR email = '$mobile_esc' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $user_id = $row['id'];
    } else {
        // create user - set user_name to the mobile value so the account is findable by phone
        // password is random 6 chars (stored as md5 to match existing app behaviour)
        $randPass = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        $user_name = $mobile_esc !== '' ? $mobile_esc : 'user' . time();
        $password_hash = md5($randPass);

        // Insert into `users`. The `users` table columns in this DB are:
        // (user_name, password, email, status, role_id, created_at, created_by, ...)
        // We'll provide the safe minimal set. If your schema requires extra non-null columns,
        // the insert will fail and the error will be returned in the response.
        $sql = "INSERT INTO users (user_name, `password`, email, status, created_at, created_by, role_id) VALUES ('" . mysqli_real_escape_string($conn, $user_name) . "', '$password_hash', '', 1, NOW(), '$created_by', '4')";

        if (!mysqli_query($conn, $sql)) {
            $response['message'] = 'Failed to create user: ' . mysqli_error($conn);
            echo json_encode($response);
            exit;
        }

        $user_id = mysqli_insert_id($conn);
    }

    // insert into members table
    // Map incoming fields to the actual members table columns in this DB:
    // members: (route_id, group_id, name, qualification, clinic_name, address, village_town_city, mobile_no, alternative_no, created_at, created_by, ...)

    $route_val = $route_esc !== '' ? "'" . $route_esc . "'" : 'NULL';
    $group_val = is_null($group_id) ? 'NULL' : "'" . intval($group_id) . "'";

    $sql = "INSERT INTO members (user_id,route_id, group_id, name, qualification, clinic_name, address, village_town_city, mobile_no, alternative_no, created_at, created_by) VALUES ($user_id,$route_val, $group_val, '$name_esc', '$qualification_esc', '$clinic_name_esc', '$address_esc', '$location_esc', '$mobile_esc', '$alt_mobile_esc', NOW(), '$created_by')";

    if (!mysqli_query($conn, $sql)) {
        $response['message'] = 'Failed to add member: ' . mysqli_error($conn);
        echo json_encode($response);
        exit;
    }

    $newMemberId = mysqli_insert_id($conn);

    // Fetch newly added member data to return
    $fetchSql = "SELECT * FROM members WHERE id = $newMemberId LIMIT 1";
    $fetchRes = mysqli_query($conn, $fetchSql);
    if ($fetchRes && mysqli_num_rows($fetchRes) > 0) {
        $newRow = mysqli_fetch_assoc($fetchRes);
        $response['status'] = 'success';
        $response['message'] = 'Member added successfully';
        $response['id'] = $newMemberId;
        $response['data'] = $newRow;
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Member added successfully';
        $response['id'] = $newMemberId;
        $response['data'] = [];
    }
    echo json_encode($response);
    exit;
}
