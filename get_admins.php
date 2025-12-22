<?php
// get_admins.php
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once('db/connection.php'); // $conn (mysqli)

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['__error' => 'DB connection missing']);
    exit;
}

/*
    New JOIN:
    - admins.branch_id
    - branches.name AS branch_name
    - branches.status AS branch_status
*/

$sql = "
    SELECT 
        a.id AS admin_id,
        a.user_id,
        a.name,
        a.designation,
        a.phone_no,
        a.branch_id,
        b.name AS branch_name,
        b.status AS branch_status,

        a.created_at,
        a.created_by,

        u.user_name AS username,
        u.created_by AS user_created_by,
        u.status AS user_status

    FROM admins a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN branches b ON b.id = a.branch_id
    ORDER BY a.id DESC
";

$res = $conn->query($sql);
if (!$res) {
    echo json_encode(['__error' => 'Query failed: ' . $conn->error]);
    exit;
}

$out = [];
while ($r = $res->fetch_assoc()) {

    // Active / Inactive
    $status_label = ($r['user_status'] == 1) ? 'Active' : 'Inactive';

    $out[] = [
        'admin_id'      => intval($r['admin_id']),
        'user_id'       => intval($r['user_id']),

        'name'          => $r['name'] ?? '',
        'designation'   => $r['designation'] ?? '',
        'phone_no'      => $r['phone_no'] ?? '',

        // NEW
        'branch_id'     => intval($r['branch_id'] ?? 0),
        'branch_name'   => $r['branch_name'] ?? '—', 
        'branch_status' => intval($r['branch_status'] ?? 0),

        'created_at'    => $r['created_at'] ?? '',
        'created_by'    => $r['created_by'] ?? ($r['user_created_by'] ?? 0),

        'username'      => $r['username'] ?? '',
        'status'        => $status_label,
        'status_int'    => intval($r['user_status'])
    ];
}

echo json_encode($out);
exit;
