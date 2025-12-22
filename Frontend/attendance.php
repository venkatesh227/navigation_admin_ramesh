<?php
session_start();

if (
    empty($_SESSION['user_id']) &&
    empty($_SESSION['employee_id']) &&
    empty($_SESSION['emp_id'])
) {
    header('Location: index.php');
    exit;
}

include_once('../db/connection.php');

// prefer users.id
$userId = $_SESSION['user_id']
    ?? $_SESSION['employee_id']
    ?? $_SESSION['emp_id']
    ?? null;

$dateError = '';
$attendanceByDate = [];
$fromEmpty = false;
$toEmpty   = false;

$isFilterSubmit = isset($_GET['filter_submit']);

if ($isFilterSubmit) {
    $from_date = $_GET['from_date'] ?? '';
    $to_date   = $_GET['to_date']   ?? '';

    $fromEmpty = ($from_date === '');
    $toEmpty   = ($to_date === '');

    if (!$fromEmpty && !$toEmpty && $to_date < $from_date) {
        $dateError = 'To Date cannot be earlier than From Date.';
    }
} else {
    $from_date = date('Y-m-01');
    $to_date   = date('Y-m-d');
}

if (
    !$fromEmpty &&
    !$toEmpty &&
    $dateError === '' &&
    isset($conn) &&
    $conn instanceof mysqli
) {

    $sql = "SELECT id, clock_in, clock_out, date
            FROM attendance
            WHERE user_id = ?
              AND date BETWEEN ? AND ?
            ORDER BY date DESC, clock_in ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $userId, $from_date, $to_date);

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendanceByDate[$row['date']][] = $row;
    }

    $stmt->close();
}

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">⏳</span> History
        </h1>
        <button onclick="toggleAttendanceFilter()" class="bg-blue-700 p-2 rounded-full hover:bg-blue-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </button>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

   <!-- FILTERS -->
   <div id="attendanceFilterSection" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-4 hidden">
        <form method="get" class="grid grid-cols-2 gap-3">
            <input type="hidden" name="filter_submit" value="1">
            
            <div class="col-span-2">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date Range</label>
                <div class="flex gap-2">
                     <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="w-full text-sm border-gray-200 rounded-lg bg-gray-50">
                     <span class="self-center text-gray-400">to</span>
                     <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="w-full text-sm border-gray-200 rounded-lg bg-gray-50">
                </div>
                <?php if ($dateError): ?>
                    <p class="text-xs text-red-500 mt-1"><?= $dateError ?></p>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="col-span-2 bg-blue-600 text-white py-2 rounded-lg font-bold shadow hover:bg-blue-700 transition">
                Show Records
            </button>
        </form>
   </div>

   <!-- LIST -->
  <div class="space-y-4">
    <?php if (empty($attendanceByDate)) : ?>
      <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 border-gray-100">
          <span class="text-4xl text-gray-200 block mb-2">📅</span>
          <p class="text-gray-400">No records found.</p>
      </div>
    <?php else : ?>
      <?php foreach ($attendanceByDate as $date => $entries) : ?>
        <?php
        $totalMinutes = 0;
        foreach ($entries as $entry) {
            if (!empty($entry['clock_in']) && !empty($entry['clock_out'])) {
                $in  = new DateTime($entry['clock_in']);
                $out = new DateTime($entry['clock_out']);
                $diff = $in->diff($out);
                $totalMinutes += ($diff->h * 60) + $diff->i;
            }
        }
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        
        $dayName = date('l', strtotime($date));
        $dayDate = date('d M Y', strtotime($date));
        ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b flex justify-between items-center">
                <div>
                     <span class="font-bold text-gray-800"><?= $dayDate ?></span>
                     <span class="text-xs text-gray-500 ml-1">(<?= $dayName ?>)</span>
                </div>
                <?php if ($totalMinutes > 0): ?>
                    <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded"><?= $hours ?>h <?= $minutes ?>m</span>
                <?php else: ?>
                    <span class="text-xs font-bold bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">Running</span>
                <?php endif; ?>
            </div>

            <div class="p-4 space-y-3">
                
                <?php foreach ($entries as $entry) : ?>
                     <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                             <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                             </div>
                             <div>
                                 <p class="font-bold text-gray-700">Clock In</p>
                                 <p class="text-xs text-gray-500"><?= date('h:i A', strtotime($entry['clock_in'])) ?></p>
                             </div>
                        </div>
                        
                        <?php if (!empty($entry['clock_out'])) : ?>
                        <div class="flex items-center gap-2 text-right">
                             <div>
                                 <p class="font-bold text-gray-700">Clock Out</p>
                                 <p class="text-xs text-gray-500"><?= date('h:i A', strtotime($entry['clock_out'])) ?></p>
                             </div>
                             <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                             </div>
                        </div>
                        <?php else: ?>
                            <span class="text-xs text-blue-600 font-bold bg-blue-50 px-2 py-1 rounded">Active</span>
                        <?php endif; ?>
                     </div>
                <?php endforeach; ?>

            </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>

<script>
function toggleAttendanceFilter() {
    document.getElementById('attendanceFilterSection').classList.toggle('hidden');
}
</script>
