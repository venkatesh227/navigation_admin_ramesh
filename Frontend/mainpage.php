<?php
session_start();
include_once('../db/connection.php');

// -----------------------------------
// INPUTS
// -----------------------------------
$route = $_GET['route'] ?? '';
$date  = $_GET['date'] ?? date('Y-m-d');

$errorMsg = '';
if ($route === '') {
    $errorMsg = "Route parameters missing.";
}

$routeEsc = htmlspecialchars($route);
$dateEsc  = date('d M Y', strtotime($date));

// -----------------------------------
// FETCH DATA
// -----------------------------------
$groupIds = [];
$groupNameMap = [];
$finalData = [];
$countData = [];
$assignedRouteId = 0;

if (!$errorMsg) {
    // FETCH ASSIGNED ROUTE ID
    $sqlRoute = "
        SELECT ar.id, ar.group_id
        FROM assign_routes ar
        JOIN routes r ON r.id = ar.route_id
        WHERE r.name = ?
          AND ? BETWEEN ar.start_date AND ar.end_date
        LIMIT 1
    ";

    $stmtR = $conn->prepare($sqlRoute);
    $stmtR->bind_param("ss", $route, $date);
    $stmtR->execute();
    $resRoute = $stmtR->get_result();

    if ($resRoute->num_rows === 0) {
        $errorMsg = "No route found for this date.";
    } else {
        $routeRow = $resRoute->fetch_assoc();
        $assignedRouteId = $routeRow['id'];
        $groupsCsv       = $routeRow['group_id'];

        $groupIds = array_filter(array_map('intval', explode(",", $groupsCsv)));

        if (empty($groupIds)) {
            $errorMsg = "No groups assigned to this route.";
        } else {
            // FETCH GROUP NAMES
            $placeholders = implode(",", array_fill(0, count($groupIds), "?"));
            $sqlGroups = "SELECT id, name FROM groups WHERE id IN ($placeholders)";
            
            $stmtG = $conn->prepare($sqlGroups);
            $stmtG->bind_param(str_repeat("i", count($groupIds)), ...$groupIds);
            $stmtG->execute();
            $resultGroups = $stmtG->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($resultGroups as $g) {
                $groupNameMap[$g['id']] = strtoupper($g['name']);
            }

            // FETCH MEMBERS PER GROUP
            foreach ($groupIds as $gid) {
                $sqlMembers = "
                    SELECT 
                        m.id AS member_id,
                        m.name,
                        m.clinic_name,
                        m.qualification,
                        m.mobile_no,
                        m.village_town_city AS place
                    FROM assigned_members am
                    JOIN members m ON m.id = am.member_id
                    WHERE am.assigned_route_id = ?
                      AND m.group_id = ?
                    GROUP BY m.id
                    ORDER BY m.name ASC
                ";

                $stmtM = $conn->prepare($sqlMembers);
                $stmtM->bind_param("ii", $assignedRouteId, $gid);
                $stmtM->execute();
                $resM = $stmtM->get_result();

                $membersArr = [];
                while ($row = $resM->fetch_assoc()) {
                    $membersArr[] = $row;
                }

                $finalData[$gid] = $membersArr;
                $countData[$gid] = count($membersArr);
            }
        }
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
            <span class="text-xl">Route Details</span>
        </h1>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- ERROR STATE -->
    <?php if ($errorMsg): ?>
        <div class="bg-red-50 p-6 rounded-xl shadow-sm text-center border border-red-100">
            <span class="text-4xl text-red-300 block mb-3">⚠️</span>
            <h3 class="text-red-800 font-bold mb-1">Route Not Found</h3>
            <p class="text-red-600 text-sm"><?= htmlspecialchars($errorMsg) ?></p>
            <a href="dashboard.php" class="inline-block mt-4 bg-red-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow">Go Back</a>
        </div>
    <?php else: ?>

        <!-- HEADER INFO -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl p-5 text-white shadow-lg mb-6 relative overflow-hidden">
            <div class="relative z-10">
                <p class="opacity-70 text-xs font-bold uppercase tracking-wider mb-1">Assigned Route</p>
                <h2 class="text-3xl font-bold mb-1"><?= $routeEsc ?></h2>
                <div class="flex items-center gap-2 text-sm opacity-90">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span><?= $dateEsc ?></span>
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -bottom-4 -right-4 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <?php foreach ($groupIds as $gid): 
                $gName = $groupNameMap[$gid] ?? 'Unknown';
                $count = $countData[$gid];
                
                // Color coding based on name
                $bgClass = 'bg-white border-gray-100';
                $textClass = 'text-gray-600';
                
                if (stripos($gName, 'QUALIFIED') !== false) { $bgClass = 'bg-green-50 border-green-100'; $textClass = 'text-green-700'; }
                elseif (stripos($gName, 'RMP') !== false)   { $bgClass = 'bg-yellow-50 border-yellow-100'; $textClass = 'text-yellow-700'; }
                elseif (stripos($gName, 'PHC') !== false)   { $bgClass = 'bg-blue-50 border-blue-100'; $textClass = 'text-blue-700'; }
                elseif (stripos($gName, 'SHOP') !== false)  { $bgClass = 'bg-purple-50 border-purple-100'; $textClass = 'text-purple-700'; }
            ?>
                <div class="<?= $bgClass ?> p-3 rounded-xl border text-center shadow-sm">
                    <span class="block text-2xl font-bold text-gray-800 mb-1"><?= $count ?></span>
                    <span class="text-xs font-bold uppercase tracking-wide <?= $textClass ?>"><?= $gName ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- LISTS -->
        <?php foreach ($groupIds as $gid): ?>
            <?php 
                $gName = $groupNameMap[$gid]; 
                $members = $finalData[$gid];
            ?>
            
            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-3 pl-1 border-l-4 border-blue-500 ml-1">
                    &nbsp;<?= $gName ?>
                </h3>

                <?php if (empty($members)): ?>
                    <p class="text-gray-400 text-sm pl-4 italic">No members assigned.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($members as $m): ?>
                            <a href="member_detail.php?assign_id=<?= $assignedRouteId ?>&member_id=<?= $m['member_id'] ?>" class="block group">
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:border-blue-300 transition relative">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg group-hover:text-blue-600 transition"><?= htmlspecialchars($m['clinic_name']) ?></h4>
                                            <p class="text-sm text-gray-600">👤 <?= htmlspecialchars($m['name']) ?></p>
                                        </div>
                                         <span class="bg-gray-50 text-gray-400 p-2 rounded-full group-hover:bg-blue-50 group-hover:text-blue-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-3 pt-3 border-t border-gray-50 grid grid-cols-2 gap-2 text-xs text-gray-500">
                                        <div>
                                            <span class="block uppercase text-[10px] tracking-wide text-gray-400">Location</span>
                                            <?= htmlspecialchars($m['place'] ?? '-') ?>
                                        </div>
                                        <?php if($m['mobile_no']): ?>
                                        <div>
                                             <span class="block uppercase text-[10px] tracking-wide text-gray-400">Phone</span>
                                            <?= htmlspecialchars($m['mobile_no']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>