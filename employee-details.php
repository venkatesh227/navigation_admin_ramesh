<?php
// employee-details.php
// Time History + Documents + Info + Notes

session_start();
include_once __DIR__ . '/db/connection.php';
include_once __DIR__ . '/db/session-check.php';

$action = $_REQUEST['action'] ?? '';

function json_out($v)
{
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($v);
  exit;
}

function sec_to_hm($s)
{
  if (!$s) return '';
  $h = floor($s / 3600);
  $m = floor(($s % 3600) / 60);
  return "{$h}h {$m}m";
}

function pick_col($row, $candidates, $default = '')
{
  foreach ($candidates as $c) {
    if (isset($row[$c]) && $row[$c] !== null && $row[$c] !== '') return $row[$c];
  }
  return $default;
}

/* -------------------- Attendance JSON -------------------- */
/* -------------------- Attendance JSON (short + AB days) -------------------- */
if ($action === 'fetch') {

  $uid   = intval($_POST['employee_id'] ?? 0);
  $start = $_POST['start_date'] ?? '';
  $end   = $_POST['end_date'] ?? '';

  if (!$uid || !$start || !$end) {
    json_out(["status" => "error", "msg" => "Missing inputs"]);
}

// 🔥 Prevent invalid date range
if (strtotime($end) < strtotime($start)) {
    json_out(["status" => "error", "msg" => "End date cannot be earlier than start date"]);
}


  // Attendance rows for this employee in range
  $sql = "
        SELECT date, clock_in, clock_out 
        FROM attendance 
        WHERE user_id = $uid
          AND date BETWEEN '$start' AND '$end'
        ORDER BY date ASC
    ";
  $rs = $conn->query($sql);

  // Store by date
  $att = [];
  if ($rs) {
    while ($r = $rs->fetch_assoc()) {
      $att[$r['date']] = $r;
    }
  }

  $out    = [];
  $cur_ts = strtotime($start);
  $end_ts = strtotime($end);

  // Loop FROM → TO (every day)
  while ($cur_ts <= $end_ts) {

    $d = date("Y-m-d", $cur_ts);

    if (isset($att[$d])) {
      // There is an attendance row → Present / Clocked In / Absent
      $row = $att[$d];

      $inTime   = !empty($row['clock_in'])  ? date("h:i A", strtotime($row['clock_in']))  : '';
      $outTime  = !empty($row['clock_out']) ? date("h:i A", strtotime($row['clock_out'])) : '';
      $totalSec = 0;

      if (!empty($row['clock_in']) && !empty($row['clock_out'])) {
        $totalSec = max(0, strtotime($row['clock_out']) - strtotime($row['clock_in']));
      }

      // Status
      if (!empty($row['clock_in']) && !empty($row['clock_out'])) {
        $status = "Present";
      } elseif (!empty($row['clock_in'])) {
        $status = "Clocked In";
      } else {
        $status = "Absent";
      }

      // Entries array (same format as old code)
      $entries = [];
      if ($inTime)  $entries[] = ['time' => $inTime,  'type' => 'In'];
      if ($outTime) $entries[] = ['time' => $outTime, 'type' => 'Out'];

      $out[] = [
        'date'      => $d,
        'entries'   => $entries,
        'total_sec' => $totalSec,
        'status'    => $status,
      ];
    } else {
      // No row for this date → AB
      $out[] = [
        'date'      => $d,
        'entries'   => [],
        'total_sec' => 0,
        'status'    => 'AB',
      ];
    }

    $cur_ts = strtotime("+1 day", $cur_ts);
  }

  json_out($out);
}

/* -------------------- Attendance CSV -------------------- */
/* -------------------- Attendance REAL Excel (.xlsx) -------------------- */
if ($action === 'excel') {

    require __DIR__ . "/vendor/autoload.php";
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Attendance");

    $uid   = intval($_GET['employee_id'] ?? 0);
    $start = $_GET['start_date'] ?? '';
    $end   = $_GET['end_date'] ?? '';
    if (!$uid || !$start || !$end) die('Missing');

    // Header
    $sheet->fromArray(['Date', 'Times', 'Total', 'Status'], NULL, 'A1');

    $sql = "SELECT date, clock_in, clock_out FROM attendance
            WHERE user_id = ? AND date BETWEEN ? AND ?
            ORDER BY date ASC";
    $st = $conn->prepare($sql);
    $st->bind_param("iss",$uid,$start,$end);
    $st->execute();
    $res = $st->get_result();

    $r = 2;

    while ($row = $res->fetch_assoc()) {
        $date = $row['date'];

        $in  = $row['clock_in']  ? date("h:i A", strtotime($row['clock_in']))  : '';
        $out = $row['clock_out'] ? date("h:i A", strtotime($row['clock_out'])) : '';

        $times = trim("$in (In)\n$out (Out)");

        $total = '';
        if ($row['clock_in'] && $row['clock_out']) {
            $sec = strtotime($row['clock_out']) - strtotime($row['clock_in']);
            $total = floor($sec/3600)."h ".floor(($sec%3600)/60)."m";
        }

        if ($row['clock_in'] && $row['clock_out']) $status = "Present";
        elseif ($row['clock_in']) $status = "Clocked In";
        else $status = "Absent";

        $sheet->setCellValue("A$r", $date);
        $sheet->setCellValue("B$r", $times);
        $sheet->setCellValue("C$r", $total);
        $sheet->setCellValue("D$r", $status);

        $r++;
    }

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=attendance_$uid.xlsx");

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}


/* -------------------- Attendance PDF -------------------- */
/* -------------------- Attendance PDF (FULL UI DATA) -------------------- */
if ($action === 'pdf') {

    require __DIR__ . '/vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();

    $uid   = intval($_GET['employee_id'] ?? 0);
    $start = $_GET['start_date'] ?? '';
    $end   = $_GET['end_date'] ?? '';

    if (!$uid || !$start || !$end) die("Missing");

    // 1) Fetch Attendance rows for the range
    $q = $conn->query("
        SELECT date, clock_in, clock_out
        FROM attendance
        WHERE user_id = $uid AND date BETWEEN '$start' AND '$end'
        ORDER BY date ASC
    ");

    $data = [];
    while ($r = $q->fetch_assoc()) {
        $data[$r['date']] = $r;
    }

    // 2) Build FULL RANGE exactly like UI
    $rows = "";
    $cur = strtotime($start);
    $end_ts = strtotime($end);

    while ($cur <= $end_ts) {
        $d = date("Y-m-d", $cur);

        if (isset($data[$d])) {

            $row = $data[$d];
            $in  = $row['clock_in']  ? date("h:i A", strtotime($row['clock_in']))  : '';
            $out = $row['clock_out'] ? date("h:i A", strtotime($row['clock_out'])) : '';

            // Build Times list
            $times = "";
            if ($in)  $times .= "$in (In)<br>";
            if ($out) $times .= "$out (Out)";

            // Total
            $total = "-";
            if ($row['clock_in'] && $row['clock_out']) {
                $sec = strtotime($row['clock_out']) - strtotime($row['clock_in']);
                $total = floor($sec/3600) . "h " . floor(($sec%3600)/60) . "m";
            }

            // Status
            if ($row['clock_in'] && $row['clock_out']) $status = "Present";
            elseif ($row['clock_in']) $status = "Clocked In";
            else $status = "Absent";

        } else {
            // AB day
            $times  = "-";
            $total  = "-";
            $status = "AB";
        }

        $rows .= "
            <tr>
                <td>$d</td>
                <td>$times</td>
                <td>$total</td>
                <td>$status</td>
            </tr>
        ";

        $cur = strtotime("+1 day", $cur);
    }

    // 3) Final PDF HTML
    $html = "
        <h2 style='text-align:center;'>Attendance Report</h2>
        <p style='text-align:center;'>From $start to $end</p>

        <table width='100%' border='1' cellspacing='0' cellpadding='5'
               style='border-collapse:collapse;font-size:13px;'>
            <thead style='background:#f2f2f2;'>
                <tr>
                    <th>Date</th>
                    <th>Times</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                $rows
            </tbody>
        </table>
    ";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("attendance_{$uid}.pdf", ["Attachment" => true]);
    exit;
}



/* -------------------- Documents FETCH -------------------- */
/* -------------------- Documents FETCH (MATCH reports.php) -------------------- */
if ($action === 'docs_fetch') {
    $uid   = intval($_POST['employee_id'] ?? 0);
    $start = $_POST['start_date'] ?? null;
    $end   = $_POST['end_date'] ?? null;
    $q     = trim($_POST['q'] ?? '');

    if (!$uid) json_out([]);

    // Normalize: GET id might be employees.id OR employees.user_id
    $empId = $uid;
    $stmtEmp = $conn->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    if ($stmtEmp) {
        $stmtEmp->bind_param("i", $uid);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();
        if ($rowEmp = $resEmp->fetch_assoc()) {
            $empId = (int)$rowEmp['id']; // always use employees.id
        }
        $stmtEmp->close();
    }

    $out = [];

    /* ---------------------------------------------------------
       1) REPORTS FROM assigned_members (assign-route flow)
       (same as reports.php part 2)
    --------------------------------------------------------- */
    $sql1 = "
        SELECT
            am.id,
            am.photo,
            am.created_at,
            m.*
        FROM assigned_members am
        INNER JOIN assign_routes ar ON am.assigned_route_id = ar.id
        INNER JOIN members m ON am.member_id = m.id
        WHERE 
            ar.employee_id = ?
            AND am.photo IS NOT NULL AND am.photo <> ''
    ";

    $types1  = "i";
    $params1 = [$empId];

    if ($start && $end) {
        $sql1    .= " AND DATE(am.created_at) BETWEEN ? AND ?";
        $types1  .= "ss";
        $params1[] = $start;
        $params1[] = $end;
    }

    if ($q !== '') {
        $sql1    .= " AND (m.clinic_name LIKE ? OR m.name LIKE ? OR m.village_town_city LIKE ? OR m.address LIKE ?)";
        $types1  .= "ssss";
        $like     = "%{$q}%";
        $params1[] = $like;
        $params1[] = $like;
        $params1[] = $like;
        $params1[] = $like;
    }

    $stmt1 = $conn->prepare($sql1);
    if ($stmt1) {
        $stmt1->bind_param($types1, ...$params1);
        $stmt1->execute();
        $res1 = $stmt1->get_result();

        while ($r = $res1->fetch_assoc()) {
            $cust  = pick_col($r, ['clinic_name','name','full_name','customer_name','clinic']);
            $phone = pick_col($r, ['mobile_no','mobile','phone','contact_no','contact','phone_number','telephone']);
            $area  = pick_col($r, ['village_town_city','area','city','address','town']);

            $photo_raw = trim($r['photo'] ?? '');
            if ($photo_raw === '') {
                $photo = '';
            } else {
                if (strpos($photo_raw, 'uploads/') === 0 || strpos($photo_raw, '/') !== false) {
                    // already a path like uploads/assigned_members/...
                    $photo = $photo_raw;
                } else {
                    // just filename
                    $photo = 'uploads/assigned_members/' . ltrim($photo_raw, '/');
                }
            }

            $out[] = [
                'id'            => $r['id'],
                'customer_name' => $cust ?: '',
                'phone'         => $phone ?: '',
                'area'          => $area ?: '',
                'photo'         => $photo,
                'submitted_at'  => $r['created_at'],
            ];
        }
        $stmt1->close();
    }

    /* ---------------------------------------------------------
       2) REPORTS FROM member_reports (Add Member form)
       (same as reports.php part 1)
    --------------------------------------------------------- */
   $sql2 = "
    SELECT
        mr.id,
        mr.clinic_name,
        mr.member_name,
        mr.place,
        mr.phone,
        mr.photo,
        mr.created_at
    FROM member_reports mr
    WHERE
        mr.employee_id = ?
        AND mr.photo IS NOT NULL AND mr.photo <> ''
";

    $types2  = "i";
    $params2 = [$empId];

    if ($start && $end) {
        $sql2    .= " AND DATE(mr.created_at) BETWEEN ? AND ?";
        $types2  .= "ss";
        $params2[] = $start;
        $params2[] = $end;
    }

    if ($q !== '') {
        $sql2    .= " AND (mr.clinic_name LIKE ? OR mr.member_name LIKE ? OR mr.place LIKE ?)";
        $types2  .= "sss";
        $like2    = "%{$q}%";
        $params2[] = $like2;
        $params2[] = $like2;
        $params2[] = $like2;
    }

    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param($types2, ...$params2);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        while ($r = $res2->fetch_assoc()) {

            $photo_raw = trim($r['photo'] ?? '');
            if ($photo_raw === '') {
                $photo = '';
            } else {
                // in member_reports we usually store "uploads/reports/..." or filename
                if (strpos($photo_raw, 'uploads/') === 0 || strpos($photo_raw, '/') !== false) {
                    $photo = $photo_raw;               // e.g. uploads/reports/abc.jpg
                } else {
                    $photo = 'uploads/reports/' . ltrim($photo_raw, '/');
                }
            }

           $custName = $r['clinic_name'] ?: $r['member_name'];

$out[] = [
    'id'            => $r['id'],
    'customer_name' => $custName ?: '',
    'phone'         => $r['phone'] ?? '',
    'area'          => $r['place'] ?: '',
    'photo'         => $photo,
    'submitted_at'  => $r['created_at'],
];

        }
        $stmt2->close();
    }

    // Sort like newest first (optional)
    usort($out, function ($a, $b) {
        return strcmp($b['submitted_at'], $a['submitted_at']);
    });

    json_out($out);
}


/* -------------------- Documents CSV -------------------- */
/* -------------------- Documents REAL Excel (.xlsx) -------------------- */
/* -------------------- Documents REAL Excel (.xlsx) -------------------- */
if ($action === 'docs_excel') {

    require __DIR__ . "/vendor/autoload.php";

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Documents");

    $uid   = intval($_GET['employee_id'] ?? 0);   // can be employees.id or users.id
    $start = $_GET['start_date'] ?? null;
    $end   = $_GET['end_date'] ?? null;

    if (!$uid) die("Missing");

    // Header row
    $sheet->fromArray(
        ['ID','Clinic Name','Phone','Area','Photo','Submitted'],
        null,
        'A1'
    );

    // 🔹 Normalize to employees.id (same idea as docs_fetch)
    $empId = $uid;
    $stmtEmp = $conn->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    if ($stmtEmp) {
        $stmtEmp->bind_param("i", $uid);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();
        if ($rowEmp = $resEmp->fetch_assoc()) {
            $empId = (int)$rowEmp['id'];
        }
        $stmtEmp->close();
    }

    // 🔹 Documents from assigned_members (same source as table)
    $sql = "
        SELECT
            am.id,
            am.photo AS am_photo,
            am.created_at,
            m.*
        FROM assigned_members am
        INNER JOIN assign_routes ar ON am.assigned_route_id = ar.id
        INNER JOIN members m        ON am.member_id = m.id
        WHERE
            ar.employee_id = ?
            AND am.photo IS NOT NULL
            AND am.photo <> ''
    ";

    $types  = "i";
    $params = [$empId];

    if ($start && $end) {
        $sql   .= " AND DATE(am.created_at) BETWEEN ? AND ?";
        $types .= "ss";
        $params[] = $start;
        $params[] = $end;
    }

    $sql .= " ORDER BY am.created_at DESC";

    $st = $conn->prepare($sql);
    if ($st === false) {
        die("Prepare failed: " . $conn->error);
    }

    $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result();

    $r = 2;
    while ($row = $res->fetch_assoc()) {
        $clinic = pick_col($row, ['clinic_name','name','full_name','customer_name'], '-');
        $phone  = pick_col($row, ['mobile_no','mobile','phone','contact_no'], '-');
        $area   = pick_col($row, ['area','village_town_city','city','address'], '-');

        $photo = $row['am_photo'] ?: $row['photo'];
        if ($photo && strpos($photo, 'uploads/') === false) {
            $photo = "uploads/assigned_members/" . ltrim($photo, '/');
        }

        $sheet->setCellValue("A$r", $row['id']);
        $sheet->setCellValue("B$r", $clinic);
        $sheet->setCellValue("C$r", $phone);
        $sheet->setCellValue("D$r", $area);
        $sheet->setCellValue("E$r", $photo);
        $sheet->setCellValue("F$r", $row['created_at']);
        $r++;
    }

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=documents_$uid.xlsx");

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}



/* -------------------- Documents PDF -------------------- */
/* -------------------- Documents PDF -------------------- */
if ($action === 'docs_pdf') {

    require __DIR__ . '/vendor/autoload.php';

    $dompdf = new \Dompdf\Dompdf(["isRemoteEnabled" => true]);

    $uid   = intval($_GET['employee_id'] ?? 0);
    $start = $_GET['start_date'] ?? null;
    $end   = $_GET['end_date'] ?? null;

    if (!$uid) die("Missing employee");

    // check employee_id column in assigned_members
    $has_am = false;
    $chk = $conn->query("SHOW COLUMNS FROM assigned_members LIKE 'employee_id'");
    if ($chk && $chk->num_rows > 0) $has_am = true;

    // ---------- FIXED SQL ----------
    $sql = "SELECT am.id, am.photo AS am_photo, am.created_at,
                   m.*, ar.employee_id AS ar_emp
            FROM assigned_members am
            LEFT JOIN assign_routes ar ON ar.id = am.assigned_route_id
            LEFT JOIN employees e ON e.id = ar.employee_id
            LEFT JOIN members m ON m.id = am.member_id
            WHERE (
                " . ($has_am ? "am.employee_id=? OR " : "") . "
                ar.employee_id=? OR
                e.user_id=?
            )";

    $types = "";
    $vals  = [];

    if ($has_am) {
        $types .= "i";
        $vals[] = $uid;
    }

    $types .= "i";
    $vals[] = $uid;

    $types .= "i";
    $vals[] = $uid;

    if ($start && $end) {
        $sql .= " AND DATE(am.created_at) BETWEEN ? AND ?";
        $types .= "ss";
        $vals[] = $start;
        $vals[] = $end;
    }

    $sql .= " ORDER BY am.created_at DESC";

    $s = $conn->prepare($sql);
    $s->bind_param($types, ...$vals);
    $s->execute();
    $res = $s->get_result();
    // ---------- END FIXED SQL ----------


    // HTML
    $html = "
    <h2 style='text-align:center;'>Documents Report</h2>
    <table width='100%' border='1' cellspacing='0' cellpadding='6' style='font-size:13px;border-collapse:collapse'>
        <tr style='background:#f2f2f2'>
            <th>ID</th>
            <th>Clinic</th>
            <th>Phone</th>
            <th>Area</th>
            <th>Photo</th>
            <th>Submitted</th>
        </tr>";

    while ($r = $res->fetch_assoc()) {

        $clinic = pick_col($r, ['clinic_name','name','full_name','customer_name'], '-');
        $phone  = pick_col($r, ['mobile_no','mobile','phone','contact_no'], '-');
        $area   = pick_col($r, ['area','village_town_city','city','address'], '-');

        // fix image path
        $photo_raw = $r['am_photo'] ?: $r['photo'];
        $imgTag = "-";

        if ($photo_raw) {

            if (strpos($photo_raw, 'uploads/') !== false)
                $photo = $photo_raw;
            else
                $photo = "uploads/assigned_members/" . $photo_raw;

            $abs = __DIR__ . '/' . $photo;

            if (file_exists($abs)) {
                $full = "file:///" . str_replace("\\", "/", $abs);
                // $imgTag = "<img src='$full' width='100' height='70'>";
            }
        }

        $html .= "
        <tr>
            <td>{$r['id']}</td>
            <td>{$clinic}</td>
            <td>{$phone}</td>
            <td>{$area}</td>
            <td>{$imgTag}</td>
            <td>{$r['created_at']}</td>
        </tr>";
    }

    $html .= "</table>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("documents_{$uid}.pdf", ["Attachment" => true]);
    exit;
}



/* -------------------- Info (employees) -------------------- */
if ($action === 'info_fetch') {
  $uid = intval($_POST['employee_id'] ?? 0);
  if (!$uid) json_out(['error' => 'missing']);

  // match by employees.id OR employees.user_id
  $sql = "SELECT id, title, name, dob, phone_no, email
            FROM employees
            WHERE id = ? OR user_id = ?
            LIMIT 1";
  $s = $conn->prepare($sql);
  $s->bind_param('ii', $uid, $uid);
  $s->execute();
  $res = $s->get_result();
  $r   = $res->fetch_assoc();
  json_out($r ?: []);
}

/* -------------------- NOTES (employee_note) -------------------- */
if ($action === 'notes_fetch') {
  $uid = intval($_POST['employee_id'] ?? 0);
  if (!$uid) json_out([]);

  $uid_int = (int)$uid;
  $sql = "SELECT id, employee_id, note, created_at, created_by
            FROM employee_note
            WHERE employee_id = {$uid_int} AND deleted_at IS NULL
            ORDER BY created_at DESC";
  $res = $conn->query($sql);
  $out = [];
  if ($res) {
    while ($r = $res->fetch_assoc()) {
      $out[] = [
        'id'         => $r['id'],
        'note'       => $r['note'],
        'created_at' => $r['created_at'],
        'created_by' => $r['created_by'],
      ];
    }
  }
  json_out($out);
}

if ($action === 'notes_add') {
  $uid  = intval($_POST['employee_id'] ?? 0);
  $note = trim($_POST['note'] ?? '');
  if (!$uid || $note === '') json_out(['status' => 0, 'msg' => 'Please Add a Note ']);

  $noteEsc    = $conn->real_escape_string($note);
  $created_by = intval($_SESSION['user_id'] ?? 0);

  $sql = "INSERT INTO employee_note (employee_id, note, created_at, created_by)
            VALUES ({$uid}, '{$noteEsc}', NOW(), {$created_by})";
  if ($conn->query($sql)) {
    json_out(['status' => 1]);
  } else {
    json_out(['status' => 0, 'msg' => $conn->error]);
  }
}

if ($action === 'notes_update') {
  $id   = intval($_POST['id'] ?? 0);
  $note = trim($_POST['note'] ?? '');

  if (!$id) {
    json_out(['status' => 0, 'msg' => 'Invalid note']);
  }
  if ($note === '') {
    json_out(['status' => 0, 'type' => 'validation', 'msg' => 'Please enter a note']);
  }

  $noteEsc = $conn->real_escape_string($note);

  $sql = "UPDATE employee_note SET note = '{$noteEsc}' WHERE id = {$id}";
  if ($conn->query($sql)) {
    json_out(['status' => 1]);
  } else {
    json_out(['status' => 0, 'msg' => $conn->error]);
  }
}


if ($action === 'notes_delete') {
  $id = intval($_POST['id'] ?? 0);
  if (!$id) json_out(['status' => 0, 'msg' => 'Missing id']);

  $deleted_by = intval($_SESSION['user_id'] ?? 0);
  $sql = "UPDATE employee_note
            SET deleted_at = NOW(), deleted_by = {$deleted_by}
            WHERE id = {$id}";
  if ($conn->query($sql)) {
    json_out(['status' => 1]);
  } else {
    json_out(['status' => 0, 'msg' => $conn->error]);
  }
}
/* -------------------- LIVE LOCATION JSON -------------------- */
/* -------------------- LIVE LOCATION JSON (FULL FIX) -------------------- */
/* ----------- LOCATION AJAX FILTER ----------- */
/* ----------- LOCATION AJAX FILTER (SAFE) ----------- */
if ($action === "fetch_location") {

    $uid   = intval($_POST["employee_id"] ?? 0);
    $start = $_POST["start_date"] ?? "";
    $end   = $_POST["end_date"] ?? "";

    if (!$uid || !$start || !$end) {
        json_out(["status" => "error", "msg" => "Invalid inputs"]);
    }

    // 🔹 Normalize employee id: accept employees.id OR employees.user_id
    $empId = $uid;
    $empStmt = $conn->prepare("SELECT id FROM employees WHERE id = ? OR user_id = ? LIMIT 1");
    if ($empStmt) {
        $empStmt->bind_param("ii", $empId, $empId);
        $empStmt->execute();
        $empRes = $empStmt->get_result();
        if ($er = $empRes->fetch_assoc()) {
            $empId = (int)$er['id'];
        }
        $empStmt->close();
    }

    $data = [];

    /* ---------- 1) Points from assigned_members (Assign-Route flow) ---------- */
    $sql1 = "
        SELECT 
            am.latitude,
            am.longitude,
            am.created_at,
            m.clinic_name
        FROM assigned_members am
        JOIN assign_routes ar ON am.assigned_route_id = ar.id
        JOIN members m        ON m.id = am.member_id
        WHERE 
            ar.employee_id = ?
            AND DATE(am.created_at) BETWEEN ? AND ?
            AND am.latitude  IS NOT NULL AND am.latitude  <> ''
            AND am.longitude IS NOT NULL AND am.longitude <> ''
        ORDER BY am.created_at ASC
    ";

    $stmt1 = $conn->prepare($sql1);
    if ($stmt1) {
        $stmt1->bind_param("iss", $empId, $start, $end);
        $stmt1->execute();
        $res1 = $stmt1->get_result();

        while ($row = $res1->fetch_assoc()) {
            $data[] = [
                "lat"    => (float)$row["latitude"],
                "lng"    => (float)$row["longitude"],
                "clinic" => $row["clinic_name"] ?: "Unknown Clinic",
                "time"   => $row["created_at"],
            ];
        }
        $stmt1->close();
    }

    /* ---------- 2) Optional points from member_reports (Add Member flow) ---------- */
    // ✅ Only run if latitude/longitude columns really exist
    $hasLat = false;
    $hasLng = false;

    if ($cols = $conn->query("SHOW COLUMNS FROM member_reports LIKE 'latitude'")) {
        $hasLat = $cols->num_rows > 0;
    }
    if ($cols2 = $conn->query("SHOW COLUMNS FROM member_reports LIKE 'longitude'")) {
        $hasLng = $cols2->num_rows > 0;
    }

    if ($hasLat && $hasLng) {
        $sql2 = "
            SELECT
                mr.latitude,
                mr.longitude,
                mr.created_at,
                mr.clinic_name
            FROM member_reports mr
            WHERE
                mr.employee_id = ?
                AND DATE(mr.created_at) BETWEEN ? AND ?
                AND mr.latitude  IS NOT NULL AND mr.latitude  <> ''
                AND mr.longitude IS NOT NULL AND mr.longitude <> ''
            ORDER BY mr.created_at ASC
        ";

        $stmt2 = $conn->prepare($sql2);
        if ($stmt2) {
            $stmt2->bind_param("iss", $empId, $start, $end);
            $stmt2->execute();
            $res2 = $stmt2->get_result();

            while ($row = $res2->fetch_assoc()) {
                $data[] = [
                    "lat"    => (float)$row["latitude"],
                    "lng"    => (float)$row["longitude"],
                    "clinic" => $row["clinic_name"] ?: "Unknown Clinic",
                    "time"   => $row["created_at"],
                ];
            }
            $stmt2->close();
        }
    }

    // sort points by time
    usort($data, function ($a, $b) {
        return strcmp($a['time'], $b['time']);
    });

    json_out(["status" => "ok", "data" => $data]);
}




/* ------------- Normal page rendering ------------- */
include __DIR__ . '/header.php';
$start_month = date('Y-m-01');
$today       = date('Y-m-d');
$employee_id = intval($_GET['id'] ?? 0);

// heading: <Name> Details
$employee_label = '';
if ($employee_id) {
  $id = (int)$employee_id;
  $sqlHead = "SELECT name FROM employees WHERE id = {$id} OR user_id = {$id} LIMIT 1";
  if ($resHead = $conn->query($sqlHead)) {
    if ($rowHead = $resHead->fetch_assoc()) {
      $employee_label = $rowHead['name'];
    }
  }
}
?>
<?php
/* ----------- LOCATION INITIAL LOAD (ASSIGN + MEMBER REPORTS) ----------- */

$empId = intval($_GET['id'] ?? 0);

/* ---- DATE FILTER (use your own GET names if different) ---- */
$from = $_GET['from_date'] ?? '';
$to   = $_GET['to_date']   ?? '';

if ($from !== '') $from = date('Y-m-d', strtotime($from));
if ($to   !== '') $to   = date('Y-m-d', strtotime($to));

// if empty, you can give defaults (optional)
if ($from === '') $from = '1970-01-01';
if ($to   === '') $to   = '2100-12-31';

/* ---- Normalize employee (accept employees.id OR employees.user_id) ---- */
$empUserId = 0;
$empStmt = $conn->prepare("SELECT id, user_id FROM employees WHERE id = ? OR user_id = ? LIMIT 1");
if ($empStmt) {
    $empStmt->bind_param("ii", $empId, $empId);
    $empStmt->execute();
    $empRes = $empStmt->get_result();
    if ($empRow = $empRes->fetch_assoc()) {
        $empId     = (int)$empRow['id'];      // for assign_routes
        $empUserId = (int)$empRow['user_id']; // for member_reports if stored as users.id
    }
    $empStmt->close();
}

$locations = [];

if ($empId > 0) {

    /* -- 1) Assigned route points (assigned_members) -- */
    $sqlA = "
        SELECT 
            am.latitude,
            am.longitude,
            am.created_at,
            m.clinic_name
        FROM assigned_members am
        JOIN assign_routes ar ON am.assigned_route_id = ar.id
        JOIN members m        ON m.id = am.member_id
        WHERE 
            ar.employee_id = ?
            AND am.latitude  IS NOT NULL AND am.latitude  <> ''
            AND am.longitude IS NOT NULL AND am.longitude <> ''
            AND DATE(am.created_at) BETWEEN ? AND ?
        ORDER BY am.created_at ASC
    ";

    if ($stmtA = $conn->prepare($sqlA)) {
        $stmtA->bind_param("iss", $empId, $from, $to);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        while ($row = $resA->fetch_assoc()) {
            $locations[] = [
                "lat"    => (float)$row["latitude"],
                "lng"    => (float)$row["longitude"],
                "clinic" => $row["clinic_name"] ?: "Unknown Clinic",
                "time"   => $row["created_at"],
            ];
        }
        $stmtA->close();
    }

    /* -- 2) Member report points (member_reports – Add Member flow) -- */

    // check columns exist
    $hasLat = false;
    $hasLng = false;

    if ($cols = $conn->query("SHOW COLUMNS FROM member_reports LIKE 'latitude'")) {
        $hasLat = $cols->num_rows > 0;
    }
    if ($cols2 = $conn->query("SHOW COLUMNS FROM member_reports LIKE 'longitude'")) {
        $hasLng = $cols2->num_rows > 0;
    }

    if ($hasLat && $hasLng) {
        $sqlB = "
            SELECT 
                mr.latitude,
                mr.longitude,
                mr.created_at,
                mr.clinic_name
            FROM member_reports mr
            WHERE 
                (mr.employee_id = ? OR mr.employee_id = ?)
                AND mr.latitude  IS NOT NULL AND mr.latitude  <> ''
                AND mr.longitude IS NOT NULL AND mr.longitude <> ''
                AND DATE(mr.created_at) BETWEEN ? AND ?
            ORDER BY mr.created_at ASC
        ";

        if ($stmtB = $conn->prepare($sqlB)) {
            $stmtB->bind_param("iiss", $empId, $empUserId, $from, $to);
            $stmtB->execute();
            $resB = $stmtB->get_result();
            while ($row = $resB->fetch_assoc()) {
                $locations[] = [
                    "lat"    => (float)$row["latitude"],
                    "lng"    => (float)$row["longitude"],
                    "clinic" => $row["clinic_name"] ?: "Unknown Clinic",
                    "time"   => $row["created_at"],
                ];
            }
            $stmtB->close();
        }
    }

    // final order by time
    usort($locations, function ($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
}
?>



<div class="nk-content">
  <div class="container-fluid">
    <div class="nk-content-inner">
      <div class="nk-content-body">
        <div class="components-preview mx-auto">
          <div class="nk-block nk-block-lg">
            <div class="nk-block-head">
              <div class="nk-block-head-content">
                <div class="nk-block-head-sub">
                  <a class="back-to" href="reports.php">
                    <em class="icon ni ni-arrow-left"></em><span>Reports</span>
                  </a>
                </div>
                <h4 class="nk-block-title">
                  <?php
                  if ($employee_label !== '') {
                    echo htmlspecialchars($employee_label) . ' Details';
                  } else {
                    echo 'Employee Details';
                  }
                  ?>
                </h4>
              </div>
            </div>

            <div class="card card-preview">
              <div class="card-inner">
                <ul class="nav nav-tabs mt-n3">
                  <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabItem5"><em class="icon ni ni-table-view"></em><span>Time History</span></a></li>
                  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabItem6"><em class="icon ni ni-map-pin-fill"></em><span>Location</span></a></li>
                  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabItem7"><em class="icon ni ni-upload-cloud"></em><span>Documents</span></a></li>
                  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabItem8"><em class="icon ni ni-user"></em><span>Info</span></a></li>
                </ul>

                <div class="tab-content">
                  <!-- Time History -->
                  <div class="tab-pane active" id="tabItem5">
                    <div class="row mb-3">
                      <div class="col-md-3"><input type="date" id="startDate" class="form-control" value="<?php echo $start_month; ?>"></div>
                      <div class="col-md-3"><input type="date" id="endDate" class="form-control" value="<?php echo $today; ?>"></div>
                      <div class="col-md-6 text-right">
                        <button id="filterBtn" class="btn btn-primary">Filter</button>
                        <button id="pdfBtn" class="btn btn-secondary">PDF</button>
                        <button id="excelBtn" class="btn btn-success">Excel</button>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-bordered" id="attendanceTable">
                        <thead style="background:#eee">
                          <tr>
                            <th>Date</th>
                            <th>Clock In/Out Times</th>
                            <th>Total Working Hours</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td colspan="4" class="text-center">Loading...</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Location -->
<div class="tab-pane fade" id="tabItem6">
    <h4>Live Location</h4>

    <div class="row mb-3">
        <div class="col-md-3">
    <input type="date" id="locStartDate" class="form-control"
           value="<?php echo date('Y-m-01'); ?>">
</div>

<div class="col-md-3">
    <input type="date" id="locEndDate" class="form-control"
           value="<?php echo date('Y-m-d'); ?>">
</div>

        <div class="col-md-6 text-right">
            <button id="locFilterBtn" class="btn btn-primary">Filter</button>
        </div>
    </div>

    <div id="map" style="height:400px;width:100%;border-radius:10px;overflow:hidden;"></div>
</div>



                  <!-- Documents -->
                  <div class="tab-pane" id="tabItem7">
                    <div class="row mb-3">
  <div class="col-md-2"><input type="date" id="docStartDate" class="form-control" value="<?php echo $start_month; ?>"></div>
  <div class="col-md-2"><input type="date" id="docEndDate" class="form-control" value="<?php echo $today; ?>"></div>
  <div class="col-md-4 text-right">
    <button id="docFilterBtn" class="btn btn-primary">Filter</button>
    <button id="docPdfBtn" class="btn btn-secondary">PDF</button>
    <button id="docExcelBtn" class="btn btn-success">Excel</button>
  </div>
</div>

                    <div class="table-responsive">
                      <table class="table table-bordered" id="documentsTable">
                        <thead style="background:#eee">
                          <tr>
                            <th>Clinic Name</th>
                            <th>Phone</th>
                            <th>Area</th>
                            <th>Photo</th>
                            <th>Submitted</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td colspan="6" class="text-center">Loading...</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Info + Notes -->
                  <div class="tab-pane" id="tabItem8">
                    <div id="infoBox" class="mb-3">Loading...</div>

                    <div class="card">
                      <div class="card-inner">
                        <h6 class="title mb-3">Employee Notes</h6>

                        <form id="noteForm" class="mb-3">
                          <input type="hidden" id="editNoteId" value="">

                          <div class="form-group">
                            <div class="form-control-wrap">
                              <textarea id="noteText" class="form-control" rows="3" placeholder="Enter note about this employee"></textarea>
                              <div id="noteError" style="margin-top:3px;"></div>

                            </div>
                          </div>
                         <button type="submit" id="noteSubmitBtn" class="btn btn-primary btn-sm">Add Note</button>

                        </form>

                        <div id="notesList">
                          <p class="text-center text-soft mb-0">No notes</p>
                        </div>
                      </div>
                    </div>
                  </div>

                </div><!-- tab-content -->
              </div><!-- card-inner -->
            </div><!-- card -->

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    const empId = <?php echo json_encode($employee_id); ?>;

    function esc(s) {
      return (s || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /* -------- Attendance -------- */
    async function fetchAttendance() {
      const s = document.getElementById('startDate').value;
      const e = document.getElementById('endDate').value;
      const tb = document.querySelector('#attendanceTable tbody');
      tb.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
      if (!empId || !s || !e) {
        tb.innerHTML = '<tr><td colspan="4" class="text-center">Select dates and employee</td></tr>';
        return;
      }
      try {
        const fd = new FormData();
        fd.append('employee_id', empId);
        fd.append('start_date', s);
        fd.append('end_date', e);
        const res = await fetch('employee-details.php?action=fetch', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          tb.innerHTML = '<tr><td colspan="4" class="text-center">No records</td></tr>';
          return;
        }
        tb.innerHTML = '';
        data.forEach(d => {
const timesArr = (d.entries || []).map(x => x.time + ' (' + x.type + ')');
const times = timesArr.length ? timesArr.join('<br>') : '-';

const total = d.total_sec
  ? Math.floor(d.total_sec / 3600) + 'h ' + Math.floor((d.total_sec % 3600) / 60) + 'm'
  : '-';
          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${esc(d.date)}</td><td>${times}</td><td>${esc(total)}</td><td>${esc(d.status)}</td>`;
          tb.appendChild(tr);
        });
      } catch (err) {
        tb.innerHTML = '<tr><td colspan="4" class="text-center">Error loading</td></tr>';
        console.error(err);
      }
    }

    document.getElementById('filterBtn').addEventListener('click', fetchAttendance);
    document.getElementById('pdfBtn').addEventListener('click', () => {
      const s = document.getElementById('startDate').value;
      const e = document.getElementById('endDate').value;
      if (!s || !e) {
        alert('Select dates');
        return;
      }
      window.location = `employee-details.php?action=pdf&employee_id=${empId}&start_date=${s}&end_date=${e}`;
    });
    document.getElementById('excelBtn').addEventListener('click', () => {
      const s = document.getElementById('startDate').value;
      const e = document.getElementById('endDate').value;
      if (!s || !e) {
        alert('Select dates');
        return;
      }
      window.location = `employee-details.php?action=excel&employee_id=${empId}&start_date=${s}&end_date=${e}`;
    });

    /* -------- Documents -------- */
   async function fetchDocs() {
  const s = document.getElementById('docStartDate').value || '';
  const e = document.getElementById('docEndDate').value || '';

  const searchEl = document.getElementById('docSearch');   // may not exist
  const q = searchEl ? (searchEl.value || '') : '';

  const tb = document.querySelector('#documentsTable tbody');

      tb.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
      if (!empId) {
        tb.innerHTML = '<tr><td colspan="6" class="text-center">Missing employee</td></tr>';
        return;
      }
      try {
        const fd = new FormData();
        fd.append('employee_id', empId);
        fd.append('start_date', s);
        fd.append('end_date', e);
        fd.append('q', q);
        const res = await fetch('employee-details.php?action=docs_fetch', {
          method: 'POST',
          body: fd
        });
        const ct = res.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) {
          const data = await res.json();
          if (!Array.isArray(data) || data.length === 0) {
            tb.innerHTML = '<tr><td colspan="6" class="text-center">No documents</td></tr>';
            return;
          }
          tb.innerHTML = '';
        data.forEach(r => {
    let photo = r.photo ? esc(r.photo) : '';

    // ❌ If no picture → skip this row completely (HIDE)
    if (photo === '') return;

    // ✔️ Image exists → show full row
    let imgHtml = `<img src="${photo}" style="width:100px;height:50px;object-fit:cover" />`;
    let btnHtml = `<button class="btn btn-sm btn-primary" onclick="window.open('${photo}','_blank')">View</button>`;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${esc(r.customer_name || '-')}</td>
        <td>${esc(r.phone || '-')}</td>
        <td>${esc(r.area || '-')}</td>
        <td>${imgHtml}</td>
        <td>${esc(r.submitted_at || '')}</td>
        <td>${btnHtml}</td>
    `;
    tb.appendChild(tr);
});


          try {
            if (typeof jQuery !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
              if ($.fn.DataTable.isDataTable('#documentsTable')) {
                $('#documentsTable').DataTable().destroy();
              }
              NioApp.DataTable('#documentsTable', {
                responsive: {
                  details: true
                }
              });
            }
          } catch (e) {
            console.warn('Datatable init failed', e);
          }
        } else {
          const text = await res.text();
          tb.innerHTML = `<tr><td colspan="6" class="text-left text-danger">Server response:<pre style="white-space:pre-wrap;margin:0;">${esc(text)}</pre></td></tr>`;
          console.error('docs_fetch response (non-json):', text);
        }
      } catch (err) {
        tb.innerHTML = '<tr><td colspan="6" class="text-center">Error</td></tr>';
        console.error(err);
      }
    }
document.getElementById('docFilterBtn').addEventListener('click', fetchDocs);

const docSearchEl = document.getElementById('docSearch');
if (docSearchEl) {
  docSearchEl.addEventListener('input', () => fetchDocs());
}

    document.getElementById('docPdfBtn').addEventListener('click', () => {
      const s = document.getElementById('docStartDate').value || '';
      const e = document.getElementById('docEndDate').value || '';
      window.location = `employee-details.php?action=docs_pdf&employee_id=${empId}&start_date=${s}&end_date=${e}`;
    });
    document.getElementById('docExcelBtn').addEventListener('click', () => {
      const s = document.getElementById('docStartDate').value || '';
      const e = document.getElementById('docEndDate').value || '';
      window.location = `employee-details.php?action=docs_excel&employee_id=${empId}&start_date=${s}&end_date=${e}`;
    });

    /* -------- Info (Personal Information) -------- */
    async function fetchInfo() {
      const el = document.getElementById('infoBox');
      if (!empId) {
        el.innerHTML = '';
        return;
      }
      try {
        const fd = new FormData();
        fd.append('employee_id', empId);
        const res = await fetch('employee-details.php?action=info_fetch', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (data.error) {
          el.innerHTML = '';
          return;
        }

        const title = data.title || '';
        const name = data.name || '';
        const dob = data.dob || '-';
        const phone = data.phone_no || '-';
        const email = data.email || '-';

        el.innerHTML = `
        <h6 class="title mb-3">Personal Information</h6>
        <div class="row gy-2">
          <div class="col-md-4">
            <div class="nk-data">
              <div class="nk-data-head">Title</div>
              <div class="nk-data-text">${esc(title)}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="nk-data">
              <div class="nk-data-head">Full Name</div>
              <div class="nk-data-text">${esc(name)}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="nk-data">
              <div class="nk-data-head">Mobile Number</div>
              <div class="nk-data-text">${esc(phone)}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="nk-data mt-2">
              <div class="nk-data-head">Date of Birth</div>
              <div class="nk-data-text">${esc(dob)}</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="nk-data mt-2">
              <div class="nk-data-head">Email Address</div>
              <div class="nk-data-text">${esc(email)}</div>
            </div>
          </div>
        </div>
      `;
      } catch (err) {
        el.innerHTML = '';
        console.error(err);
      }
    }

    /* -------- Notes -------- */
      /* -------- Notes -------- */
    const noteForm  = document.getElementById('noteForm');
    const notesList = document.getElementById('notesList');

    async function fetchNotes() {
      const list = document.getElementById('notesList');
      if (!list) return;
      list.innerHTML = '<p class="text-center mb-0">Loading...</p>';
      if (!empId) {
        list.innerHTML = '<p class="text-center mb-0">Missing employee</p>';
        return;
      }
      try {
        const fd = new FormData();
        fd.append('employee_id', empId);
        const res = await fetch('employee-details.php?action=notes_fetch', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          list.innerHTML = '<p class="text-center text-soft mb-0">No notes</p>';
          return;
        }
        let html = '';
        data.forEach(r => {
          html += `
            <div class="border rounded p-2 mb-2" data-note-id="${esc(r.id)}">
              <div class="mb-1 note-text">${esc(r.note || '')}</div>

              <div class="small text-soft d-flex justify-content-between align-items-center">
                <span>Added on ${esc(r.created_at || '')} | By ${esc(r.created_by || 'Admin')}</span>

                <div>
                  <button type="button" class="btn btn-xs btn-info mr-1" data-edit-id="${esc(r.id)}">Edit</button>
                  <button type="button" class="btn btn-xs btn-danger" data-del-id="${esc(r.id)}">Delete</button>
                </div>
              </div>
            </div>
          `;
        });



        list.innerHTML = html;
      } catch (err) {
        list.innerHTML = '<p class="text-center mb-0 text-danger">Error loading notes</p>';
        console.error(err);
      }
    }
    if (noteForm) {
      noteForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const txt       = document.getElementById('noteText');
        const errBox    = document.getElementById('noteError');
        const submitBtn = document.getElementById('noteSubmitBtn');
        const editId    = document.getElementById('editNoteId').value;

        errBox.innerHTML = "";
        const note = (txt.value || "").trim();

        const fd = new FormData();
        fd.append('note', note);

        let action = 'notes_add';

        if (editId) {
          fd.append('id', editId);
          action = 'notes_update';
        } else {
          fd.append('employee_id', empId);
        }

        try {
          const res  = await fetch(`employee-details.php?action=${action}`, {
            method: 'POST',
            body: fd
          });
          const data = await res.json();

          if (!data.status) {
            // any error (including validation)
            errBox.innerHTML = `<span style="color:red;font-size:13px;">${data.msg || "Error saving note"}</span>`;
            return;
          }

          // success
          txt.value = "";
          document.getElementById('editNoteId').value = "";
          submitBtn.innerText = "Add Note";
          errBox.innerHTML = "";

          if (action === 'notes_update') {
            Swal.fire({
              icon: 'success',
              title: 'Note updated successfully',
              timer: 1300,
              showConfirmButton: false
            });
          }

          fetchNotes();

        } catch (err) {
          errBox.innerHTML = `<span style="color:red;font-size:13px;">Error saving note</span>`;
          console.error(err);
        }
      });
    }

    if (notesList) {
      notesList.addEventListener('click', async function (e) {

        // ---------- EDIT ----------
        const editBtn = e.target.closest('button[data-edit-id]');
        if (editBtn) {
          const wrapper = editBtn.closest('[data-note-id]');
          const id      = editBtn.getAttribute('data-edit-id');
          const textEl  = wrapper ? wrapper.querySelector('.note-text') : null;
          const text    = textEl ? textEl.textContent : '';

          document.getElementById('editNoteId').value = id;
          document.getElementById('noteText').value   = text.trim();
          document.getElementById('noteSubmitBtn').innerText = "Update Note";
          document.getElementById('noteError').innerHTML = "";

          window.scrollTo({ top: noteForm.offsetTop - 80, behavior: 'smooth' });
          return;
        }

        // ---------- DELETE ----------
        const delBtn = e.target.closest('button[data-del-id]');
        if (!delBtn) return;

        const id = delBtn.getAttribute('data-del-id');
        if (!id) return;

        try {
          const fd = new FormData();
          fd.append('id', id);

          const res  = await fetch('employee-details.php?action=notes_delete', {
            method: 'POST',
            body: fd
          });
          const data = await res.json();

          if (data.status) {
            Swal.fire({
              icon: 'success',
              title: 'Note Deleted',
              timer: 1200,
              showConfirmButton: false
            });
            fetchNotes();
          } else {
            Swal.fire({
              icon: 'error',
              title: data.msg || 'Failed to delete note'
            });
          }

        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error deleting note',
          });
          console.error(err);
        }
      });
    }


    // initial loads
    fetchAttendance();
    fetchDocs();
    fetchInfo();
    fetchNotes();
  })();
</script>
<script>
let map = null;
let markersLayer = null;

function getRandomColor() {
    const colors = ["#ff2e2e", "#2e8bff", "#29c46b", "#ff9900", "#9b59b6", "#e91e63"];
    return colors[Math.floor(Math.random() * colors.length)];
}

// ---------- LOAD INITIAL MAP ----------
function loadLeafletMap() {
    const points = <?php echo json_encode($locations); ?>;

    if (!points || points.length === 0) {
        document.getElementById("map").innerHTML =
            "<p style='padding:20px;text-align:center;'>No Location Found</p>";
        return;
    }

    map = L.map("map").setView([points[0].lat, points[0].lng], 14);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    points.forEach((loc, i) => addMarker(loc, i + 1));

    fitBounds();
}

function addMarker(loc, num) {
    const icon = L.divIcon({
        className: "custom-pin",
        html: `
            <div class="pin-wrapper">
                <div class="pin-badge" style="background:${getRandomColor()}">${num}</div>
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" style="width:40px;">
            </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [15, 35],
    });

    let marker = L.marker([loc.lat, loc.lng], { icon }).addTo(markersLayer);

    marker.bindTooltip(
        `<b>Clinic:</b> ${loc.clinic}<br>
         <b>Date:</b> ${loc.time}`,
        { direction: "top" }
    );
}

function fitBounds() {
    if (markersLayer.getLayers().length > 0) {
        let group = L.featureGroup(markersLayer.getLayers());
        map.fitBounds(group.getBounds(), { padding: [30, 30] });
    }
}

// ---------- FILTER ----------
document.getElementById("locFilterBtn").addEventListener("click", function () {
    let s = document.getElementById("locStartDate").value;
    let e = document.getElementById("locEndDate").value;
    let uid = <?php echo intval($empId); ?>;

    if (!s || !e) {
        Swal.fire("Please select both Start and End dates");
        return;
    }

    // 🔥 NEW VALIDATION: End date < Start date
    if (new Date(e) < new Date(s)) {
        Swal.fire({
            icon: "error",
            title: "Invalid Date Range",
            text: "End date cannot be earlier than start date"
        });
        return;
    }

    $.post("employee-details.php?action=fetch_location",
        { employee_id: uid, start_date: s, end_date: e },
        function (res) {
            if (!res.data || res.data.length === 0) {
                Swal.fire("No locations found for selected dates");
                return;
            }
            markersLayer.clearLayers();
            res.data.forEach((loc, i) => addMarker(loc, i + 1));
            fitBounds();
        }, "json");
});



// ---------- LOAD MAP WHEN TAB IS CLICKED ----------
document.querySelector("a[href='#tabItem6']").addEventListener("click", function () {
    setTimeout(() => {
        if (!map) loadLeafletMap();
        map.invalidateSize();
    }, 300);
});
</script>


<?php include __DIR__ . '/footer.php'; ?>