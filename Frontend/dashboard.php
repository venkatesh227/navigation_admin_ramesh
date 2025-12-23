<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');
include_once('../db/connection.php');
include_once('../db/functions.php');

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit;
}

$employeeName = htmlspecialchars($_SESSION['employee_name'] ?? 'Employee', ENT_QUOTES, 'UTF-8');

// -----------------------------------------
// ATTENDANCE STATUS
// -----------------------------------------
$userIdForAttendance = $_SESSION['user_id']
                    ?? $_SESSION['employee_id']
                    ?? $_SESSION['emp_id'];

$today = date('Y-m-d');

$stmtAtt = $conn->prepare("SELECT id, clock_in, clock_out 
                           FROM attendance 
                           WHERE user_id=? AND date=? LIMIT 1");
$stmtAtt->bind_param("is", $userIdForAttendance, $today);
$stmtAtt->execute();
$resAtt = $stmtAtt->get_result();

$attendanceState = 'none';

if ($resAtt && $resAtt->num_rows === 1) {
    $a = $resAtt->fetch_assoc();
    if (!empty($a['clock_in']) && empty($a['clock_out'])) {
        $attendanceState = 'in';
    } elseif (!empty($a['clock_in']) && !empty($a['clock_out'])) {
        $attendanceState = 'done';
    }else{
        $attendanceState = 'none';
    }
}
$stmtAtt->close();

// -----------------------------------------
// LATEST ANNOUNCEMENT
// -----------------------------------------
$latestAnn = null;
$resAnn = $conn->query("SELECT id, title, message, created_at FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
if ($resAnn && $resAnn->num_rows > 0) {
    $latestAnn = $resAnn->fetch_assoc();
}

// -----------------------------------------
// EXPENSE NOTIFICATIONS
// -----------------------------------------
// Check for any expense where notification_seen = 0 AND status IN ('Approved', 'Rejected')
// Using raw query for simplicity since inputs are internal
$expAlert = null;
$resExp = $conn->query("SELECT id, expense_type, status FROM expenses WHERE employee_id = $employee_id AND notification_seen = 0 AND status IN ('Approved', 'Rejected') LIMIT 1");

if ($resExp && $resExp->num_rows > 0) {
    $expAlert = $resExp->fetch_assoc();
}

// -----------------------------------------
// TARGETS & ACHIEVEMENTS
// -----------------------------------------
$currentMonth = date('Y-m');
$target = 0;
$achieved = 0;

// 1. Get Target
$resTarget = $conn->query("SELECT visit_target FROM employee_targets WHERE employee_id = $employee_id AND month_year = '$currentMonth'");
if ($resTarget && $resTarget->num_rows > 0) {
    $target = $resTarget->fetch_assoc()['visit_target'];
}

// 2. Count Achieved (Visits this month)
$resAch = $conn->query("SELECT COUNT(*) as cnt FROM member_reports WHERE employee_id = $employee_id AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'");
if ($resAch) {
    $achieved = $resAch->fetch_assoc()['cnt'];
}

// 3. Percent
$goalPercent = ($target > 0) ? min(100, round(($achieved / $target) * 100)) : 0;

// -----------------------------------------
// NOTE NOTIFICATIONS
// -----------------------------------------
$noteAlert = null;
$resNote = $conn->query("SELECT id, note FROM employee_note WHERE employee_id = $employee_id AND is_seen = 0 AND deleted_at IS NULL LIMIT 1");
if ($resNote && $resNote->num_rows > 0) {
    $noteAlert = $resNote->fetch_assoc();
}

// -----------------------------------------
// CHART DATA (Last 7 Days)
// -----------------------------------------
$chartDates = [];
$chartCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = date('D', strtotime($d)); // Mon, Tue...
    
    // Count visits for this day
    $resC = $conn->query("SELECT COUNT(*) as c FROM member_reports WHERE employee_id = $employee_id AND DATE(created_at) = '$d'");
    $chartCounts[] = ($resC) ? $resC->fetch_assoc()['c'] : 0;
}


// -----------------------------------------
// FETCH TODAYS TASKS
// -----------------------------------------
$todaysTasks = [];
$taskSql = "
    SELECT 
        r.name AS route_name,
        t.start_date AS assign_date
    FROM assign_routes t
    JOIN routes r ON r.id = t.route_id
    WHERE 
        t.employee_id = ?
        AND CURDATE() BETWEEN t.start_date AND t.end_date
    GROUP BY t.route_id
    ORDER BY r.name
";
$tStmt = $conn->prepare($taskSql);
if ($tStmt) {
    $tStmt->bind_param('i', $employee_id);
    $tStmt->execute();
    $tRes = $tStmt->get_result();
    while ($r = $tRes->fetch_assoc()) {
        $todaysTasks[] = $r;
    }
    $tStmt->close();
}

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-50">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">🏥</span> Health Hospitals FSMS
        </h1>
        <a href="logout.php" class="text-white bg-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-800">Logout</a>
    </div>
</header>

<!-- PAGE CONTENT -->
<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- WELCOME -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-5 rounded-2xl shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-1">Hello, <?= $employeeName ?>! 👋</h2>
        <p class="text-blue-100 text-sm mb-4">Have a productive day.</p>
        
        <div class="bg-white bg-opacity-20 rounded-lg p-3 flex items-center justify-between backdrop-filter backdrop-blur-sm">
            <div>
                <p class="text-xs uppercase tracking-wide opacity-80">Current Status</p>
                <p class="font-bold text-lg">

                    <?= $attendanceState === 'in' ? '🟢 Checked In' : ($attendanceState === 'done' ? '🏁 Completed' : '🔴 Not Checked In') ?>
                </p>
            </div>
            
            <?php if ($attendanceState !== 'done'): ?>
            <button id="attendanceBtn" data-state="<?= $attendanceState ?>" 
                class="bg-white text-blue-600 px-5 py-2 rounded-full font-bold shadow hover:bg-gray-100 transition transform active:scale-95">
                <?= $attendanceState === 'in' ? 'Check Out' : 'Check In' ?>
            </button>
            <?php else: ?>
            <!-- Resume Option -->
            <button id="resumeBtn" 
                class="bg-white text-blue-600 px-5 py-2 rounded-full font-bold shadow hover:bg-gray-100 transition transform active:scale-95">
                Resume Work
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- TODAY TASKS -->
    <div class="mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span>📋</span> Today's Assignments
        </h3>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div id="tasksTableBody" class="p-0">
                <?php if (empty($todaysTasks)): ?>
                    <div class="p-8 text-center bg-gray-50 flex flex-col items-center">
                        <span class="text-4xl mb-2">🎉</span>
                        <h4 class="text-gray-600 font-medium">No tasks assigned today</h4>
                        <p class="text-gray-400 text-sm">Enjoy your day!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($todaysTasks as $task): ?>
                    <a href="mainpage.php?route=<?= urlencode($task['route_name']) ?>&date=<?= date('Y-m-d') ?>" 
                       class="block p-4 border-b border-gray-100 last:border-0 hover:bg-blue-50 transition group">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-gray-800 text-lg group-hover:text-blue-700 transition">
                                    <?= htmlspecialchars($task['route_name']) ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">📅 Assigned for Today</p>
                            </div>
                            <div class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MONTHLY GOAL (Premium Design) -->
    <?php if ($target > 0): ?>
    <div class="mb-6 relative overflow-hidden bg-gradient-to-br from-indigo-600 to-blue-500 rounded-2xl shadow-lg text-white p-6">
        
        <!-- Background Decor -->
        <div class="absolute -top-4 -right-4 text-white opacity-10">
            <svg class="w-24 h-24 transform rotate-12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        </div>

        <div class="relative z-10 flex justify-between items-start mb-4">
            <div>
                <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-1">Monthly Goal</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold"><?= $achieved ?></span>
                    <span class="text-sm text-blue-100 opacity-80">/ <?= $target ?> Visits</span>
                </div>
            </div>
            <div class="bg-white bg-opacity-20 backdrop-blur-sm px-3 py-1 rounded-lg border border-white/10">
                <span class="font-bold text-lg"><?= $goalPercent ?>%</span>
            </div>
        </div>

        <div class="relative z-10">
            <div class="w-full bg-black bg-opacity-20 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-green-300 to-green-400 h-3 rounded-full shadow-sm transition-all duration-1000" style="width: <?= $goalPercent ?>%"></div>
            </div>
            <div class="flex justify-between items-center mt-2 text-xs text-blue-100 font-medium">
                <span><?= $achieved ?> completed</span>
                 <?= $goalPercent >= 100 ? '🎉 Goal Reached!' : ($target - $achieved) . ' to go' ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- WEEKLY PERFORMANCE CHART -->
    <div class="mb-6 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>📈</span> Performance (Last 7 Days)
        </h3>
        <div class="relative h-48 w-full">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>

    <!-- NAVIGATION GRID -->
    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
        <span>🚀</span> Quick Actions
    </h3>
    <div class="grid grid-cols-2 gap-4">
        
        <a href="members.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition border-l-4 border-l-blue-400">
            <span class="text-3xl">👨‍⚕️</span>
            <span class="font-semibold text-gray-700">Members</span>
        </a>
        
        <a href="reports.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition border-l-4 border-l-green-400">
            <span class="text-3xl">📊</span>
            <span class="font-semibold text-gray-700">My Reports</span>
        </a>

        <a href="attendance.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition">
            <span class="text-3xl">⏳</span>
            <span class="font-semibold text-gray-700">History</span>
        </a>

        <a href="employee_notes.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition relative">
             <span class="text-3xl">📝</span>
             <span class="font-semibold text-gray-700">Notes</span>
             <?php if ($noteAlert): ?>
                <span class="absolute top-3 right-3 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
             <?php endif; ?>
        </a>

        <!-- Expenses Card -->
        <a href="expenses.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition relative">
             <span class="text-3xl">💰</span>
             <span class="font-semibold text-gray-700">Expenses</span>
             <?php if ($expAlert): ?>
                <span class="absolute top-3 right-3 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
             <?php endif; ?>
        </a>

        <!-- Announcements Card -->
        <a href="announcements.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition relative">
             <span class="text-3xl">📢</span>
             <span class="font-semibold text-gray-700">Notices</span>
             <!-- Red dot for new announcement -->
             <span id="announcementBadge" class="hidden absolute top-3 right-3 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
        </a>

        <a href="profile.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition">
            <span class="text-3xl">👤</span>
            <span class="font-semibold text-gray-700">Profile</span>
        </a>
        
        <a href="help.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition">
            <span class="text-3xl">❓</span>
            <span class="font-semibold text-gray-700">Help</span>
        </a>

        <!-- Leaves Card -->
        <a href="leaves.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center gap-2 hover:bg-blue-50 transition">
            <span class="text-3xl">🏖️</span>
            <span class="font-semibold text-gray-700">Leaves</span>
        </a>

    </div>

</div>

<!-- BOTTOM NAVIGATION -->
<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>

<!-- Chart.js Library (UMD) -->
<script src="https://cdntest.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- Fallback if CDN fails -->
<script>
    if(typeof Chart === 'undefined'){
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"><\/script>');
    }
</script>

<script>
function escapeHtml(t){
    return t ? t.replace(/&/g,"&amp;").replace(/</g,"&lt;") : "";
}

// -----------------------------------------------------
// NOTIFICATION LOGIC
// -----------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Announcements
    <?php if ($latestAnn): ?>
        const latestId = <?= $latestAnn['id'] ?>;
        const latestTitle = "<?= addslashes($latestAnn['title']) ?>";
        const latestMsg = `<?= addslashes(nl2br($latestAnn['message'])) ?>`;
        const lastSeen = localStorage.getItem('lastSeenAnnouncementId');
        
        if (!lastSeen || parseInt(lastSeen) < latestId) {
            document.getElementById('announcementBadge').classList.remove('hidden');
            Swal.fire({
                title: '📢 New Announcement',
                html: `
                    <div class="text-left mt-2">
                        <h4 class="font-bold text-gray-800 mb-2">${latestTitle}</h4>
                        <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded border">${latestMsg}</div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Got it!',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.setItem('lastSeenAnnouncementId', latestId);
                    document.getElementById('announcementBadge').classList.add('hidden');
                }
            });
        }
    <?php endif; ?>

    // 2. Expense Updates
    <?php if ($expAlert): ?>
        Swal.fire({
            title: 'Expense Status Updated',
            text: 'Your expense for "<?= $expAlert['expense_type'] ?>" has been <?= $expAlert['status'] ?>.',
            icon: '<?= $expAlert['status'] == 'Approved' ? 'success' : 'warning' ?>',
            confirmButtonText: 'View Expenses',
            allowOutsideClick: false
        }).then((res) => {
            if(res.isConfirmed) {
                window.location.href = 'expenses.php';
            }
        });
    <?php endif; ?>

    // 3. New Note Alert
    <?php if ($noteAlert): ?>
        Swal.fire({
            title: 'New Note Received',
            text: 'You have received a new note from Admin.',
            icon: 'info',
            confirmButtonText: 'View Notes',
            allowOutsideClick: false
        }).then((res) => {
            if(res.isConfirmed) {
                window.location.href = 'employee_notes.php';
            }
        });
    <?php endif; ?>

});


// -----------------------------------------------------
// CHECK-IN / CHECK-OUT
// -----------------------------------------------------
document.addEventListener("click", async function(e){
    if(e.target.id === "attendanceBtn"){

        const btn = e.target;
        let state = btn.getAttribute("data-state");

        let action = (state === "in") ? "checkout" : "checkin";

        try{
            let res = await fetch("attendance_action.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=" + action
            });

            let out = await res.json();

            if(out.status === "success"){
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: out.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(()=> location.reload());
            } 
            else {
                Swal.fire({ icon: "error", title: "Error", text: out.message });
            }

        } catch(err){
            Swal.fire({ icon: "error", text: "Connection failed!" });
        }
    }
});

// RESUME WORK ACTION
document.addEventListener("click", async function(e){
    if(e.target.id === "resumeBtn"){
        try{
            let res = await fetch("attendance_action.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=resume"
            });
            let out = await res.json();
            if(out.status === "success"){
                Swal.fire({
                    icon: "success", title: "Resumed", text: "Welcome back!", timer: 1000, showConfirmButton: false
                }).then(()=> location.reload());
            } else {
                Swal.fire({ icon: "error", title: "Error", text: out.message });
            }
        } catch(err){
            Swal.fire({ icon: "error", text: "Connection failed!" });
        }
    }
});

// -----------------------------------------------------
// LIVE GPS TRACKING (Background)
// -----------------------------------------------------
const ATTENDANCE_STATE = "<?= $attendanceState ?>";

if (ATTENDANCE_STATE === 'in') {
    startTracking();
}

function startTracking() {
    if ("geolocation" in navigator) {
        // Send immediately
        sendPosition();
        // Then every 60 seconds
        setInterval(sendPosition, 60000);
    } else {
        console.log("Geolocation not available");
    }
}

function sendPosition() {
    navigator.geolocation.getCurrentPosition(async (position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        try {
            const formData = new FormData();
            formData.append('lat', lat);
            formData.append('lng', lng);

            await fetch('../update_location.php', {
                method: 'POST',
                body: formData
            });
            console.log("Location updated:", lat, lng);
        } catch (err) {
            console.error("Loc update failed", err);
        }
    }, (err) => {
        console.warn("GPS Error:", err.message);
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    });
}

// -----------------------------------------------------
// CHART JS VISUALIZATION
// -----------------------------------------------------
document.addEventListener("DOMContentLoaded", function(){
    const ctx = document.getElementById('performanceChart');
    if(!ctx) return;

    try {
        const dates = <?= json_encode($chartDates) ?>;
        const counts = <?= json_encode($chartCounts) ?>;
        
        // Wait for Chart to be defined if loaded async
        const initChart = () => {
            if(typeof Chart === 'undefined') {
                console.warn("Chart.js not ready, retrying...");
                setTimeout(initChart, 500); 
                return;
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Visits',
                        data: counts,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                        barThickness: 12
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { 
                            backgroundColor: '#1e293b',
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [4, 4], color: '#f1f5f9' },
                            ticks: { precision: 0 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        };

        // If Chart is loaded via script tag at bottom it should include before this, 
        // but if CDN is slow, we might need to wait carefully.
        // The script tag is synchronous usually, so it should be there.
        initChart();

    } catch(e) {
        console.error("Chart Error:", e);
    }
});
</script>
</body>
</html>
