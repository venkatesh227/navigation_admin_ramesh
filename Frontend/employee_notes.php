<?php
session_start();

$session_employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (empty($session_employee_id)) {
    header('Location: ../index.php');
    exit;
}
$session_employee_id = (int)$session_employee_id;

include_once('../db/connection.php');

$notes    = [];
$errorMsg = '';

if (!($conn instanceof mysqli)) {
    $errorMsg = 'Database not available.';
} else {
    // 1) Mark all unseen notes as seen for this employee
    $updSql = "UPDATE employee_note SET is_seen = 1 WHERE employee_id = ? AND is_seen = 0";
    if ($stmtUpd = $conn->prepare($updSql)) {
        $stmtUpd->bind_param("i", $session_employee_id);
        $stmtUpd->execute();
        $stmtUpd->close();
    }

    // 2) Fetch notes directly using session_employee_id (which is employees.id)
    $sql = "
        SELECT
            id, note, created_at
        FROM employee_note
        WHERE 
            employee_id = ?
          AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
        ORDER BY created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $session_employee_id);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $notes[] = $row;
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
            <a href="dashboard.php" class="text-white opacity-80 hover:opacity-100 mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <span class="text-xl">Admin Notes</span>
        </h1>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- ERROR -->
    <?php if ($errorMsg !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm font-bold">
            <?= htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <!-- LIST -->
    <?php if (empty($notes) && $errorMsg === ''): ?>
        <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 border-gray-100 mt-4">
            <span class="text-4xl text-gray-200 block mb-2">📝</span>
            <p class="text-gray-400">No notes assigned to you.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($notes as $n): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-yellow-400"></div>
                    
                    <div class="flex justify-between items-start mb-2 pl-2">
                        <span class="bg-yellow-50 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wide">Admin Note</span>
                        <span class="text-xs text-gray-400 font-medium">
                            <?= date('d M, h:i A', strtotime($n['created_at'])); ?>
                        </span>
                    </div>

                    <div class="text-gray-800 text-sm leading-relaxed whitespace-pre-line pl-2 font-medium">
                        <?= nl2br(htmlspecialchars($n['note'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>