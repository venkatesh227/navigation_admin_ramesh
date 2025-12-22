<?php
session_start();
include_once('../db/connection.php');

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) { header('Location: index.php'); exit; }

// Fetch Announcements
$anns = [];
$res = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 20");
if($res){
    while($row = $res->fetch_assoc()) $anns[] = $row;
}
include 'header.php';
?>

<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <a href="dashboard.php" class="text-white opacity-80 hover:opacity-100 mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <span class="text-xl">📢 Announcements</span>
        </h1>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

    <?php if(empty($anns)): ?>
        <div class="bg-white p-8 rounded-xl shadow-sm text-center border-dashed border-2 text-gray-400 mt-6">
            <span class="text-4xl block mb-2">🔕</span>
            <p>No new announcements.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach($anns as $a): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                    <div class="pl-3">
                        <h3 class="font-bold text-gray-800 text-lg mb-1 leading-tight"><?= htmlspecialchars($a['title']) ?></h3>
                        <p class="text-xs text-gray-400 mb-3"><?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></p>
                        <div class="text-sm text-gray-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($a['message'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>
