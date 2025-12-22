<?php
session_start();
include_once('../db/connection.php');

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Handle New Leave Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $type = trim($_POST['leave_type']);
    $start = trim($_POST['start_date']);
    $end = trim($_POST['end_date']);
    $reason = trim($_POST['reason']);

    if (empty($type) || empty($start) || empty($end) || empty($reason)) {
        $error = "All fields are required.";
    } elseif ($end < $start) {
        $error = "End date cannot be before start date.";
    } else {
        // Prepare Insert
        $sql = "INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $employee_id, $type, $start, $end, $reason);
            if ($stmt->execute()) {
                // Redirect to avoid resubmit
                header("Location: leaves.php?applied=1");
                exit;
            } else {
                $error = "Database error.";
            }
            $stmt->close();
        }
    }
}

// Flash Message
if (isset($_GET['applied'])) {
    $success = "Leave application submitted successfully!";
}

// Fetch History
$history = [];
$res = $conn->query("SELECT * FROM leaves WHERE employee_id = $employee_id ORDER BY created_at DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $history[] = $row;
    }
}

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-50">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <a href="dashboard.php" class="text-white opacity-80 hover:opacity-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <span class="text-xl">Leave Requests</span>
        </h1>
        <button onclick="document.getElementById('applyModal').classList.remove('hidden')" class="bg-white text-blue-600 px-3 py-1.5 rounded-full text-sm font-bold shadow hover:bg-gray-100 transition">
            + Apply
        </button>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">
    
    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-4 text-sm font-bold border border-green-200">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-4 text-sm font-bold border border-red-200">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($history)): ?>
        <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 border-gray-100 mt-8">
            <span class="text-4xl block mb-2">🏖️</span>
            <p class="text-gray-400">No leave history found.</p>
        </div>
    <?php else: ?>
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">My Applications</h3>
        <div class="space-y-4">
            <?php foreach ($history as $leave): 
                $statusColor = 'bg-yellow-100 text-yellow-700'; // Pending
                if ($leave['status'] == 'Approved') $statusColor = 'bg-green-100 text-green-700';
                if ($leave['status'] == 'Rejected') $statusColor = 'bg-red-100 text-red-700';
                
                $days = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / (60 * 60 * 24) + 1;
            ?>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold px-2 py-1 rounded <?= $statusColor ?>">
                        <?= $leave['status'] ?>
                    </span>
                    <span class="text-xs text-gray-400"><?= date('d M, Y', strtotime($leave['created_at'])) ?></span>
                </div>
                
                <h4 class="font-bold text-gray-800 text-lg mb-1">
                    <?= htmlspecialchars($leave['leave_type']) ?>
                    <span class="text-sm font-normal text-gray-500">
                        (<?= $days ?> day<?= $days > 1 ? 's' : '' ?>)
                    </span>
                </h4>
                
                <div class="text-sm text-gray-600 mb-2">
                    📅 <?= date('d M', strtotime($leave['start_date'])) ?>  -  <?= date('d M', strtotime($leave['end_date'])) ?>
                </div>
                
                <?php if($leave['reason']): ?>
                    <p class="text-sm text-gray-500 italic bg-gray-50 p-2 rounded">
                        "<?= htmlspecialchars($leave['reason']) ?>"
                    </p>
                <?php endif; ?>
                
                <?php if($leave['admin_remark']): ?>
                    <div class="mt-2 pt-2 border-t text-xs text-red-500">
                        <strong>Admin Note:</strong> <?= htmlspecialchars($leave['admin_remark']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Apply Modal -->
<div id="applyModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center backdrop-blur-sm p-4" style="z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6">
        <h3 class="text-xl font-bold mb-4">Apply for Leave</h3>
        <form method="post" class="space-y-4">
            <input type="hidden" name="apply_leave" value="1">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Leave Type</label>
                <select name="leave_type" class="w-full border-gray-300 rounded-lg shadow-sm h-12 bg-white p-3">
                    <option value="Casual Leave">Casual Leave</option>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Emergency">Emergency</option>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">From Date</label>
                     <input type="date" name="start_date" class="w-full border-gray-300 rounded-lg shadow-sm p-3" required>
                </div>
                <div>
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">To Date</label>
                     <input type="date" name="end_date" class="w-full border-gray-300 rounded-lg shadow-sm p-3" required>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason</label>
                <textarea name="reason" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm p-3" placeholder="Reason for leave..." required></textarea>
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('applyModal').classList.add('hidden')" class="flex-1 py-3 font-bold text-gray-500 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="flex-1 py-3 font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow">Apply</button>
            </div>
        </form>
    </div>
</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>
