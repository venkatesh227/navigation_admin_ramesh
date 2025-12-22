<?php
session_start();

// Must be logged in
if (
    empty($_SESSION['user_id']) &&
    empty($_SESSION['employee_id']) &&
    empty($_SESSION['emp_id'])
) {
    header('Location: index.php');
    exit;
}

// Logged-in employee
$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (empty($employee_id)) {
    header('Location: dashboard.php');
    exit;
}
$employee_id = (int)$employee_id;

include_once('../db/connection.php'); // $conn

$today      = date('Y-m-d');
$members    = [];
$form_error = '';

/* ------------------------------------------------------
   LOAD GROUPS FOR DROPDOWN
------------------------------------------------------- */
$allGroups = [];
$gRes = $conn->query("SELECT id, name FROM groups ORDER BY name");
if ($gRes) {
    while ($g = $gRes->fetch_assoc()) {
        $allGroups[] = $g;
    }
}

/* ------------------------------------------------------
   HANDLE ADD MEMBER FORM (modal) SUBMIT
------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_member_report'])) {

    $clinic_name = trim($_POST['clinic_name'] ?? '');
    $member_name = trim($_POST['member_name'] ?? '');
    $route       = trim($_POST['route'] ?? '');
    $place       = trim($_POST['place'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $group_name  = trim($_POST['group_name'] ?? '');


    $latitude    = trim($_POST['latitude'] ?? '');
    $longitude   = trim($_POST['longitude'] ?? '');

    if ($clinic_name === '' || $member_name === '' || $route === '' || $place === '' || $description === '' || $group_name === '') {
        $form_error = 'All fields marked with * are required.';
    } else {

        $photoPath = null;

        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $form_error = 'Only jpg, jpeg, png, gif, webp allowed.';
            } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                $form_error = 'Max file size is 5MB.';
            } else {
                $uploadDirFs = dirname(__DIR__) . '/uploads/reports/';
                if (!is_dir($uploadDirFs)) {
                    @mkdir($uploadDirFs, 0777, true);
                }

                $newName  = time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                $fullPath = $uploadDirFs . $newName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $fullPath)) {
                    $photoPath = 'uploads/reports/' . $newName;
                } else {
                    $form_error = 'Unable to upload file.';
                }
            }
        }

        if ($form_error === '') {
            $sqlIns = "INSERT INTO member_reports
                       (employee_id, clinic_name, member_name, route, place, phone, description, photo, group_name, latitude, longitude, created_at)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())";

            $stmtIns = $conn->prepare($sqlIns);
            $stmtIns->bind_param(
                "issssssssss",   // 1 int + 10 strings
                $employee_id,
                $clinic_name,
                $member_name,
                $route,
                $place,
                $phone,
                $description,
                $photoPath,
                $group_name,
                $latitude,
                $longitude
            );

            if ($stmtIns->execute()) {
                header("Location: members.php?added=1");
                exit;
            } else {
                $form_error = 'Database error while saving.';
            }
            $stmtIns->close();
        }
    }
}

/* ------------------------------------------------------
   TODAY'S ASSIGNED MEMBERS (original list)
------------------------------------------------------- */
$sql = "
    SELECT 
        am.assigned_route_id AS assign_id,
        m.id AS member_id,
        m.clinic_name,
        m.name AS member_name,
        m.village_town_city AS place,
        m.mobile_no AS member_phone,
        g.name AS group_name,
        r.name AS route_name
    FROM assigned_members am
    JOIN assign_routes t ON t.id = am.assigned_route_id
    JOIN members m        ON m.id = am.member_id
    JOIN groups g         ON g.id = m.group_id
    JOIN routes r         ON r.id = t.route_id
    WHERE 
        t.employee_id = ?
        AND ? BETWEEN t.start_date AND t.end_date
        AND FIND_IN_SET(m.group_id, t.group_id)
    ORDER BY r.name, g.name, m.clinic_name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $employee_id, $today);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

/* ------------------------------------------------------
   TODAY'S ADDED MEMBERS FROM member_reports
------------------------------------------------------- */
$starMembers = [];
$sqlStar = "SELECT clinic_name, member_name, route, place, phone, group_name
            FROM member_reports
            WHERE employee_id = ? AND DATE(created_at) = ?
            ORDER BY created_at DESC";
$stmtStar = $conn->prepare($sqlStar);
$stmtStar->bind_param("is", $employee_id, $today);
$stmtStar->execute();
$resStar = $stmtStar->get_result();
while ($row = $resStar->fetch_assoc()) {
    $starMembers[] = $row;
}
$stmtStar->close();

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">👨‍⚕️</span> Members
        </h1>
        <button onclick="openMemberModal()" class="text-blue-600 bg-white px-4 py-1.5 rounded-full text-sm font-bold shadow hover:bg-gray-100 transition">
            + Add New
        </button>
    </div>
</header>


<div class="max-w-3xl mx-auto p-4 pb-24">

    <?php if (isset($_GET['added'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             Swal.fire({ icon: 'success', title: 'Saved!', text: 'Member report submitted successfully.', timer: 2000, showConfirmButton: false });
        });
    </script>
    <?php endif; ?>

    <?php if ($form_error): ?>
      <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded shadow-sm">
        <p class="font-bold">Error</p>
        <p><?= htmlspecialchars($form_error) ?></p>
      </div>
    <?php endif; ?>


    <!-- SECTION: Added Today -->
    <?php if (!empty($starMembers)): ?>
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 mt-2">Recently Added Log</h3>
        <div class="space-y-3 mb-6">
            <?php foreach ($starMembers as $s): ?>
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-bl">New</div>
                <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($s['clinic_name']) ?></h4>
                <p class="text-sm text-gray-600 mb-1">👤 <?= htmlspecialchars($s['member_name']) ?></p>
                <div class="flex flex-wrap gap-2 mt-2 text-xs text-gray-500">
                    <span class="bg-white px-2 py-1 rounded border">📍 <?= htmlspecialchars($s['place']) ?></span>
                    <span class="bg-white px-2 py-1 rounded border">🚌 <?= htmlspecialchars($s['route']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <!-- SECTION: Assigned Today -->
    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Assigned Schedule (<?= date('d M') ?>)</h3>

    <?php if (empty($members)): ?>
        <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 border-gray-200">
            <span class="text-4xl text-gray-300 block mb-2">📭</span>
            <p class="text-gray-500">No members assigned for today.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php foreach ($members as $app): ?>
          <a href="member_detail.php?assign_id=<?= intval($app['assign_id']) ?>&member_id=<?= intval($app['member_id']) ?>" class="block group">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:border-blue-300 transition relative">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-bold text-gray-800 text-lg group-hover:text-blue-600 transition"><?= htmlspecialchars($app['clinic_name']) ?></h4>
                        <p class="text-sm text-gray-600">👤 <?= htmlspecialchars($app['member_name']) ?></p>
                    </div>
                     <span class="text-gray-300 group-hover:text-blue-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </span>
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-50 grid grid-cols-2 gap-2 text-xs text-gray-500">
                    <div>
                        <span class="block uppercase text-[10px] tracking-wide text-gray-400">Place</span>
                        <?= htmlspecialchars($app['place']) ?>
                    </div>
                    <div>
                         <span class="block uppercase text-[10px] tracking-wide text-gray-400">Group</span>
                        <?= htmlspecialchars($app['group_name']) ?>
                    </div>
                </div>
            </div>
          </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- REUSABLE BOTTOM NAV -->
<?php include 'bottom_nav.php'; ?>


<!-- MODAL: Add Member -->
<div id="memberModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center backdrop-blur-sm p-4" style="z-index: 9999;">
  <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
    <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
      <h3 class="text-lg font-bold">Add Unassigned Visit</h3>
      <button type="button" onclick="closeMemberModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xl font-bold">&times;</button>
    </div>

    <form method="post" enctype="multipart/form-data" class="p-5 space-y-4">
      <input type="hidden" name="create_member_report" value="1">
      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">

        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Clinic Name *</label>
          <input type="text" name="clinic_name" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-3" required placeholder="e.g. Health Plus Clinic">
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Doctor Name *</label>
              <input type="text" name="member_name" class="w-full border-gray-300 rounded-lg shadow-sm p-3" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone</label>
              <input type="tel" name="phone" class="w-full border-gray-300 rounded-lg shadow-sm p-3">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
             <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Route *</label>
              <input type="text" name="route" class="w-full border-gray-300 rounded-lg shadow-sm p-3" required>
            </div>
             <div>
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Place *</label>
              <input type="text" name="place" class="w-full border-gray-300 rounded-lg shadow-sm p-3" required>
            </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Group *</label>
          <select name="group_name" class="w-full border-gray-300 rounded-lg shadow-sm h-12 bg-white p-3" required>
            <option value="">Select Group</option>
            <?php foreach ($allGroups as $g): ?>
              <option value="<?= htmlspecialchars($g['name']) ?>"><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Remarks *</label>
        <textarea name="description" class="w-full border-gray-300 rounded-lg shadow-sm p-3" rows="3" required placeholder="Meeting details..."></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Attach Photo</label>
        <input type="file" name="photo" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full bg-blue-600 text-white rounded-lg py-3 font-bold shadow-lg hover:bg-blue-700 transition transform active:scale-95">
           Save Report
        </button>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
// GPS Capture
function setCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
          document.getElementById('latitude').value  = pos.coords.latitude;
          document.getElementById('longitude').value = pos.coords.longitude;
      },
      (err) => console.warn('GPS Error:', err),
      { enableHighAccuracy: true, timeout: 5000 }
    );
  }
}

function openMemberModal() {
  const m = document.getElementById('memberModal');
  m.classList.remove('hidden');
  setCurrentLocation(); 
}
function closeMemberModal() {
  const m = document.getElementById('memberModal');
  m.classList.add('hidden');
}
</script>
