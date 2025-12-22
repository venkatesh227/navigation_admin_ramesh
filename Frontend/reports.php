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

include_once('../db/connection.php');

/* ---------------------------------------------------------
   FILTERS: FROM DATE, TO DATE, PLACE
--------------------------------------------------------- */
$todaySql   = date('Y-m-d');
$fromInput  = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$toInput    = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$placeInput = isset($_GET['place']) ? trim($_GET['place']) : '';
$isSearch   = isset($_GET['search']); // when search button clicked

// Defaults (today only)
$query_from = $todaySql;
$query_to   = $todaySql;

// Convert dd-mm-yyyy to Y-m-d when searching
if ($isSearch) {
    if ($fromInput !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $fromInput);
        if (!$dt) $dt = DateTime::createFromFormat('d-m-Y', $fromInput);
        if ($dt) $query_from = $dt->format('Y-m-d');
    }

    if ($toInput !== '') {
        $dt2 = DateTime::createFromFormat('Y-m-d', $toInput);
        if (!$dt2) $dt2 = DateTime::createFromFormat('d-m-Y', $toInput);
        if ($dt2) $query_to = $dt2->format('Y-m-d');
        else $query_to = $query_from;
    } else {
        $query_to = $query_from;
    }
} else {
    // initial load – show today's date in inputs
    $fromInput = date('Y-m-d');
    $toInput   = date('Y-m-d');
}

$reportsByDate = [];
$totalVisits = 0;

/* ---------------------------------------------------------
   1) REPORTS FROM member_reports (Add Member form)
--------------------------------------------------------- */
if (isset($conn) && $conn instanceof mysqli) {

    $sql = "
        SELECT
            mr.id,
            mr.description,
            mr.photo,
            mr.created_at,
            TIME(mr.created_at) AS visit_time,
            mr.clinic_name,
            mr.member_name,
            mr.place AS village_town_city,
            '' AS address,
            mr.route AS route_name,
            'member_reports' AS source
        FROM member_reports mr
        WHERE
            DATE(mr.created_at) BETWEEN ? AND ?
            AND mr.employee_id = ?
    ";

    $types  = 'ssi';
    $params = [$query_from, $query_to, $employee_id];

    // optional place filter
    if ($placeInput !== '') {
        $sql .= " AND (mr.place LIKE ? OR mr.clinic_name LIKE ? OR mr.member_name LIKE ?)";
        $like = '%' . $placeInput . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY TIME(mr.created_at) DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $visitDate = date('d-m-Y', strtotime($row['created_at']));
                if (!isset($reportsByDate[$visitDate])) {
                    $reportsByDate[$visitDate] = [];
                }
                $reportsByDate[$visitDate][] = $row;
                $totalVisits++;
            }
        }
        $stmt->close();
    }
}

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">📊</span> My Reports
        </h1>
    </div>
</header>


<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- FILTERS -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-4">
        <form method="get" class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date Range</label>
                <div class="flex gap-2">
                     <input type="date" name="from_date" value="<?= htmlspecialchars($fromInput) ?>" class="w-full text-sm border-gray-200 rounded-lg bg-gray-50 p-3">
                     <span class="self-center text-gray-400">to</span>
                     <input type="date" name="to_date" value="<?= htmlspecialchars($toInput) ?>" class="w-full text-sm border-gray-200 rounded-lg bg-gray-50 p-3">
                </div>
            </div>
             <div class="col-span-2">
                <input type="text" name="place" placeholder="Filter by Place / Clinic..." value="<?= htmlspecialchars($placeInput) ?>" class="w-full text-sm border-gray-200 rounded-lg bg-gray-50 placeholder-gray-400 p-3">
            </div>
            
            <button type="submit" name="search" value="1" class="col-span-2 bg-blue-600 text-white py-2 rounded-lg font-bold shadow hover:bg-blue-700 transition">
                Apply Filters
            </button>
        </form>
    </div>

    <!-- SUMMARY -->
    <div class="flex items-center justify-between mb-4 px-2">
        <h3 class="font-bold text-gray-700">Found: <span class="text-blue-600"><?= $totalVisits ?> Reports</span></h3>
        <span class="text-xs text-gray-400">Sorted Newest First</span>
    </div>

    <!-- REPORT LIST -->
    <?php if (empty($reportsByDate)) : ?>
      <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 border-gray-100">
          <span class="text-4xl text-gray-200 block mb-2">📋</span>
          <p class="text-gray-400">No reports found for this selection.</p>
      </div>

    <?php else : ?>

      <?php foreach ($reportsByDate as $dateKey => $items) : ?>
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded text-xs"><?= $dateKey ?></span>
                <span class="h-px bg-gray-200 flex-grow"></span>
            </div>

            <div class="space-y-4">
            <?php foreach ($items as $report) : ?>
              <?php
                $clinic    = $report['clinic_name'] ?: 'Unknown Clinic';
                $name      = $report['member_name'] ?: 'Unknown Member';
                $timeText  = $report['visit_time'] ? date('h:i A', strtotime($report['visit_time'])) : '-';
                $placeTxt  = $report['village_town_city'] ?: $report['address'];
                
                // Photo
                $rawPhoto = trim($report['photo'] ?? '');
                $photoSrc = '';
                if ($rawPhoto !== '') {
                    if (!empty($report['source']) && $report['source'] === 'member_reports') {
                        $photoRel = ltrim($rawPhoto, '/');
                        $photoSrc = '../' . $photoRel;
                    } else {
                        $filename = basename($rawPhoto);
                        $photoSrc = '../uploads/assigned_members/' . $filename;
                    }
                }
              ?>

              <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative group">
                 <div class="flex justify-between items-start mb-2">
                     <div>
                        <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($clinic) ?></h4>
                        <p class="text-sm text-gray-600">👤 <?= htmlspecialchars($name) ?></p>
                     </div>
                     <span class="text-xs font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded">🕐 <?= $timeText ?></span>
                 </div>

                 <div class="text-sm text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100 mb-3">
                     <?php if($placeTxt): ?>
                        <div class="flex items-center gap-2 mb-1">
                            <span>📍</span> <?= htmlspecialchars($placeTxt) ?>
                        </div>
                     <?php endif; ?>
                     <?php if(!empty($report['description'])): ?>
                        <div class="flex items-start gap-2 mt-2 pt-2 border-t border-gray-200">
                            <span>📝</span> 
                            <span class="italic text-gray-600"><?= nl2br(htmlspecialchars($report['description'])) ?></span>
                        </div>
                     <?php endif; ?>
                 </div>

                 <?php if($photoSrc): ?>
                     <div class="mt-2">
                         <img src="<?= htmlspecialchars($photoSrc) ?>" class="h-20 w-20 object-cover rounded-lg border cursor-pointer hover:opacity-80 transition" onclick="showImageModal(this.src)">
                     </div>
                 <?php endif; ?>
              </div>
            <?php endforeach; ?>
            </div>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>

</div>

<!-- REUSABLE BOTTOM NAV -->
<?php include 'bottom_nav.php'; ?>


<!-- Image Zoom Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-[60] flex items-center justify-center p-4 backdrop-blur-sm" onclick="closeImageModal()">
    <img id="zoomedImage" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl transition-transform transform scale-95" />
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-4xl leading-none">&times;</button>
</div>

<script>
function showImageModal(src) {
  const m = document.getElementById('imageModal');
  const img = document.getElementById('zoomedImage');
  img.src = src;
  m.classList.remove('hidden');
  m.classList.add('flex');
  setTimeout(()=> img.classList.remove('scale-95'), 10);
}
function closeImageModal() {
  const m = document.getElementById('imageModal');
  const img = document.getElementById('zoomedImage');
  img.classList.add('scale-95');
  setTimeout(()=> {
      m.classList.add('hidden');
      m.classList.remove('flex');
  }, 150);
}
</script>

<?php include 'footer.php'; ?>
