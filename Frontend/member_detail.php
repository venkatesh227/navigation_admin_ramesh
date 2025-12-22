<?php
session_start();
include_once('../db/connection.php');
include_once('../db/functions.php');

$APP_BASE = '/';
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

function safe_ext(string $name): string {
  $ext = pathinfo($name, PATHINFO_EXTENSION);
  $ext = strtolower(preg_replace('/[^a-z0-9]+/', '', $ext));
  return $ext ?: 'jpg';
}

function column_exists(mysqli $conn, string $table, string $column): bool {
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($column);
  $q = "SHOW COLUMNS FROM `{$t}` LIKE '{$c}'";
  $res = $conn->query($q);
  return ($res && $res->num_rows > 0);
}

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (empty($employee_id)) {
  header('Location: index.php');
  exit;
}

$assign_id = isset($_GET['assign_id']) ? intval($_GET['assign_id']) : 0;
$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $assign_id = intval($_POST['assign_id'] ?? $assign_id);
  $member_id = intval($_POST['member_id'] ?? $member_id);
}

// -------------------------------------------------------------
// HANDLE FORM SUBMISSION
// -------------------------------------------------------------
$errors = [];
$success = '';
$posted_description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $posted_description = trim($_POST['description'] ?? '');
  $geo_lat = isset($_POST['geo_lat']) && $_POST['geo_lat'] !== '' ? floatval($_POST['geo_lat']) : null;
  $geo_lng = isset($_POST['geo_lng']) && $_POST['geo_lng'] !== '' ? floatval($_POST['geo_lng']) : null;

  if ($posted_description === '') {
    $errors[] = 'Description is required.';
  }

  $photo_provided = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
  if (!$photo_provided) {
    if (isset($_POST['has_existing_photo']) && $_POST['has_existing_photo'] == '1') {
       // Photo exists, no new upload needed
    } else {
       $errors[] = 'Photo is required.';
    }
  }

  $photo_db = null;
  $target = null;

  if ($photo_provided && empty($errors)) {
    $up = $_FILES['photo'];
    if ($up['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'File upload error (code ' . intval($up['error']) . ').';
    } elseif ($up['size'] > $MAX_FILE_SIZE) {
      $errors[] = 'File too large. Max 5MB.';
    } else {
      $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mtype = finfo_file($finfo, $up['tmp_name']);
      finfo_close($finfo);

      if (!in_array($mtype, $allowed, true)) {
        $errors[] = 'Only images allowed (jpg, png, webp, gif).';
      } else {
        $uploadDir = __DIR__ . '/../uploads/assigned_members/';
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
          $errors[] = 'Failed to create upload directory.';
        } else {
          $ext = safe_ext($up['name']);
          $basename = uniqid('vis_', true) . '.' . $ext;
          $target = $uploadDir . $basename;
          if (move_uploaded_file($up['tmp_name'], $target)) {
            $photo_db = 'uploads/assigned_members/' . $basename;
          } else {
            $errors[] = 'Failed to move uploaded file.';
          }
        }
      }
    }
  }

  if (empty($errors)) {
    // Check if record exists
    $exists = false;
    $am_id = 0;
    $chk = $conn->prepare("SELECT id, photo FROM assigned_members WHERE assigned_route_id = ? AND member_id = ? LIMIT 1");
    if ($chk) {
      $chk->bind_param('ii', $assign_id, $member_id);
      $chk->execute();
      $cres = $chk->get_result();
      if ($cres && $crow = $cres->fetch_assoc()) {
        $exists = true;
        $am_id = intval($crow['id']);
        if(!$photo_db) $photo_db = $crow['photo']; // Keep existing photo
      }
      $chk->close();
    }

    $cols = [];
    $types = '';
    $vals = [];

    if ($exists) {
      // UPDATE
      $sql = "UPDATE assigned_members SET description=?";
      $params = [$posted_description];
      $types = 's';

      if ($photo_db) {
        $sql .= ", photo=?";
        $params[] = $photo_db;
        $types .= 's';
      }
      
      // Geo
      if (column_exists($conn, 'assigned_members', 'latitude')) {
        $sql .= ", latitude=?, longitude=?";
        $params[] = $geo_lat;
        $params[] = $geo_lng;
        $types .= 'dd';
      }
      // Meta
      if (column_exists($conn, 'assigned_members', 'updated_at')) {
        $sql .= ", updated_at=NOW(), updated_by=?";
        $params[] = $employee_id;
        $types .= 'i';
      }

      $sql .= " WHERE id=?";
      $params[] = $am_id;
      $types .= 'i';

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      if ($stmt->execute()) {
        $success = 'Report updated successfully!';
      } else {
        $errors[] = 'Update failed: ' . $conn->error;
      }

    } else {
      // INSERT
      // We assume basic cols always exist based on schema
      $has_geo = column_exists($conn, 'assigned_members', 'latitude');
      
      $colNames = "assigned_route_id, member_id, description, photo, created_by, created_at";
      $valMarks = "?, ?, ?, ?, ?, NOW()";
      $types = "iisss";
      $params = [$assign_id, $member_id, $posted_description, $photo_db, $employee_id];

      if ($has_geo) {
        $colNames .= ", latitude, longitude";
        $valMarks .= ", ?, ?";
        $types .= "dd";
        $params[] = $geo_lat;
        $params[] = $geo_lng;
      }

      $stmt = $conn->prepare("INSERT INTO assigned_members ($colNames) VALUES ($valMarks)");
      $stmt->bind_param($types, ...$params);
      if ($stmt->execute()) {
        $success = 'Report submitted successfully!';
      } else {
        $errors[] = 'Insert failed: ' . $conn->error;
      }
    }
  }
}

// -------------------------------------------------------------
// FETCH DETAILS
// -------------------------------------------------------------
$member = null;
$stmt = $conn->prepare("
    SELECT m.*,
           COALESCE(r.name, '') AS route_name,
           COALESCE(g.name, '') AS group_name,
           a.id AS assigned_id,
           a.photo AS assigned_photo,
           a.description AS am_description
    FROM members m
    LEFT JOIN assigned_members a 
           ON a.member_id = m.id 
          AND a.assigned_route_id = ?
    LEFT JOIN assign_routes t ON t.id = ?
    LEFT JOIN routes r ON r.id = COALESCE(m.route_id, t.route_id)
    LEFT JOIN groups g ON g.id = t.group_id
    WHERE m.id = ? LIMIT 1
");

if ($stmt) {
  $stmt->bind_param('iii', $assign_id, $assign_id, $member_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $member = $res->fetch_assoc();
  $stmt->close();
}

$assigned_photo = $member['assigned_photo'] ?? '';
$has_existing_photo = !empty($assigned_photo);

$assigned_photo_url = '';
if ($has_existing_photo) {
  $assigned_photo_url = (strpos($assigned_photo, 'http') === 0 || $assigned_photo[0] === '/') 
      ? $assigned_photo 
      : '../' . ltrim($assigned_photo, '/');
}

$desc_val = $member['am_description'] ?? '';

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <a href="members.php" class="text-white opacity-80 hover:opacity-100 mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <span class="text-xl">Visit Report</span>
        </h1>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- ERROR / SUCCESS -->
    <?php if ($errors): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-4 shadow-sm">
            <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
             Swal.fire({ 
                 icon: 'success', 
                 title: 'Saved!', 
                 text: '<?= addslashes($success) ?>', 
                 confirmButtonColor: '#2563eb'
             }).then(() => {
                 window.location.href = 'members.php';
             });
        });
        </script>
    <?php endif; ?>


    <?php if (!$member): ?>
        <div class="bg-white p-6 rounded-xl shadow-sm text-center">
            <p class="text-gray-500">Member details not found.</p>
            <a href="members.php" class="text-blue-600 font-bold mt-2 inline-block">Go Back</a>
        </div>
    <?php else: ?>

        <!-- MEMBER INFO CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 relative overflow-hidden">
             <div class="absolute top-0 right-0 bg-blue-50 text-blue-600 text-xs font-bold px-3 py-1 rounded-bl-xl border-b border-l border-blue-100">
                 <?= htmlspecialchars($member['group_name']) ?>
             </div>
             
             <h2 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($member['clinic_name']) ?></h2>
             <div class="text-gray-500 text-sm mb-4">
                 <span class="font-semibold text-gray-700">Dr. <?= htmlspecialchars($member['name']) ?></span>
             </div>

             <div class="grid grid-cols-2 gap-3 text-xs text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100">
                 <div>
                     <span class="block uppercase tracking-wide text-[10px] text-gray-400">Route</span>
                     <span class="font-medium text-gray-700"><?= htmlspecialchars($member['route_name']) ?></span>
                 </div>
                 <div>
                     <span class="block uppercase tracking-wide text-[10px] text-gray-400">Location</span>
                     <span class="font-medium text-gray-700"><?= htmlspecialchars($member['village_town_city'] ?? '-') ?></span>
                 </div>
                 <?php if($member['mobile_no']): ?>
                 <div class="col-span-2 pt-2 border-t border-gray-200 flex justify-between items-center">
                     <div>
                        <span class="block uppercase tracking-wide text-[10px] text-gray-400">Phone</span>
                        <span class="font-medium text-gray-700"><?= htmlspecialchars($member['mobile_no']) ?></span>
                     </div>
                     <a href="tel:<?= htmlspecialchars($member['mobile_no']) ?>" class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold">Call</a>
                 </div>
                 <?php endif; ?>
             </div>
        </div>

        <!-- REPORT FORM -->
        <form method="post" enctype="multipart/form-data" id="reportForm" class="space-y-4">
            <input type="hidden" name="assign_id" value="<?= $assign_id ?>">
            <input type="hidden" name="member_id" value="<?= $member_id ?>">
            <input type="hidden" name="geo_lat" id="geo_lat">
            <input type="hidden" name="geo_lng" id="geo_lng">
            <input type="hidden" name="has_existing_photo" value="<?= $has_existing_photo ? '1' : '0' ?>">

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Submit Report</h3>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Remarks *</label>
                    <textarea name="description" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter visit details..." required><?= htmlspecialchars($desc_val) ?></textarea>
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Photo Evidence <?php if(!$has_existing_photo) echo '*'; ?></label>
                    
                    <!-- Preview Area -->
                    <div id="previewContainer" class="mb-3 relative w-full h-48 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400 overflow-hidden group">
                        
                        <?php if ($has_existing_photo): ?>
                            <img src="<?= htmlspecialchars($assigned_photo_url) ?>" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <span class="text-white text-xs font-bold">Click below to change</span>
                            </div>
                        <?php else: ?>
                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-xs">Tap to upload photo</span>
                        <?php endif; ?>
                        
                        <img id="newPreview" class="absolute inset-0 w-full h-full object-cover hidden z-10">
                    </div>

                    <input type="file" name="photo" id="photoInput" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*" <?php if(!$has_existing_photo) echo 'required'; ?>>
                </div>

            </div>

        <div class="flex items-center justify-between mb-4 bg-blue-50 p-2 rounded text-xs text-blue-700">
            <span id="locStatus">📍 Fetching location...</span>
        </div>

        <button type="submit" id="submitBtn" disabled class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl shadow-lg hover:bg-blue-700 transition transform active:scale-95 disabled:bg-gray-400 disabled:cursor-not-allowed">
            <?= $has_existing_photo ? 'Update Report' : 'Submit Report' ?>
        </button>

        </form>
    <?php endif; ?>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>

<script>
// Auto-capture GPS
document.addEventListener('DOMContentLoaded', () => {
    const statusEl = document.getElementById('locStatus');
    const btn = document.getElementById('submitBtn');

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById('geo_lat').value = pos.coords.latitude;
                document.getElementById('geo_lng').value = pos.coords.longitude;
                statusEl.innerHTML = "✅ Location captured";
                statusEl.classList.remove('text-blue-700');
                statusEl.classList.add('text-green-700');
                btn.disabled = false;
            },
            (err) => {
                console.log('Location access denied or failed');
                statusEl.innerHTML = "⚠️ Location failed. Submitting without GPS.";
                statusEl.classList.remove('text-blue-700');
                statusEl.classList.add('text-orange-700');
                btn.disabled = false; // Allow submit anyway
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    } else {
        statusEl.innerHTML = "⚠️ Geolocation not supported";
        btn.disabled = false;
    }
});

// Image Preview Logic
const photoInput = document.getElementById('photoInput');
const newPreview = document.getElementById('newPreview');

if(photoInput){
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                newPreview.src = ev.target.result;
                newPreview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });
}
</script>
