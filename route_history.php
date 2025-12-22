<?php
session_start();
include_once('db/connection.php');

if(empty($_SESSION['username'])){
    header("Location: index.php");
    exit;
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$employees = [];

// Fetch employees with tracking data for this day
$sql = "
    SELECT DISTINCT lt.employee_id, e.name, e.mobile 
    FROM live_tracking lt
    JOIN employees e ON e.id = lt.employee_id
    WHERE DATE(lt.created_at) = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $employees[] = $row;
$stmt->close();

$historyData = [];
if(isset($_GET['employee_id'])) {
    $empId = (int)$_GET['employee_id'];
    $sqlH = "SELECT latitude, longitude, created_at FROM live_tracking WHERE employee_id = ? AND DATE(created_at) = ? ORDER BY created_at ASC";
    $stmtH = $conn->prepare($sqlH);
    $stmtH->bind_param("is", $empId, $selectedDate);
    $stmtH->execute();
    $resH = $stmtH->get_result();
    while($r = $resH->fetch_assoc()) {
        $historyData[] = [
            'lat' => (float)$r['latitude'], 
            'lng' => (float)$r['longitude'], 
            'time' => date('h:i A', strtotime($r['created_at']))
        ];
    }
    $stmtH->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Route History - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 flex min-h-screen">
    
    <!-- SIDEBAR -->
    <?php include 'navbar.php'; ?>

    <!-- CONTENT -->
    <div class="flex-1 p-6">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Route History Replay</h1>
            <a href="dashboard.php" class="text-blue-600 hover:underline">Back to Dashboard</a>
        </header>

        <!-- FILTERS -->
        <div class="bg-white p-4 rounded-lg shadow mb-6 flex gap-4 items-end">
             <form class="flex gap-4 w-full">
                 <div class="flex-1">
                     <label class="block text-gray-500 text-sm font-bold mb-1">Select Date</label>
                     <input type="date" name="date" value="<?= $selectedDate ?>" class="w-full border p-2 rounded" onchange="this.form.submit()">
                 </div>
                 <div class="flex-1">
                     <label class="block text-gray-500 text-sm font-bold mb-1">Select Employee</label>
                     <select name="employee_id" class="w-full border p-2 rounded" onchange="this.form.submit()">
                         <option value="">-- Choose Employee --</option>
                         <?php foreach($employees as $e): ?>
                            <option value="<?= $e['employee_id'] ?>" <?= (isset($_GET['employee_id']) && $_GET['employee_id'] == $e['employee_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['name']) ?> (<?= $e['mobile'] ?>)
                            </option>
                         <?php endforeach; ?>
                     </select>
                 </div>
             </form>
        </div>

        <!-- MAP -->
        <div id="map" class="w-full h-[600px] bg-gray-200 rounded-lg shadow-inner border relative">
             <?php if(empty($historyData) && isset($_GET['employee_id'])): ?>
                <div class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 z-[1000]">
                    <p class="text-xl font-bold text-gray-500">No tracking data found for this employee on this date.</p>
                </div>
             <?php elseif(!isset($_GET['employee_id'])): ?>
                <div class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 z-[1000]">
                    <p class="text-xl font-bold text-gray-500">Select an employee to view route.</p>
                </div>
             <?php endif; ?>
        </div>

    </div>

    <script>
        const map = L.map('map').setView([16.5062, 80.6480], 13); // Default Vijayawada
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const historyData = <?= json_encode($historyData) ?>;

        if (historyData.length > 0) {
            const latlngs = historyData.map(p => [p.lat, p.lng]);
            const polyline = L.polyline(latlngs, {color: 'blue', weight: 4}).addTo(map);
            map.fitBounds(polyline.getBounds());

            // Markers
            // Start
            L.marker(latlngs[0]).addTo(map).bindPopup("Start: " + historyData[0].time);
            // End
            L.marker(latlngs[latlngs.length - 1]).addTo(map).bindPopup("End: " + historyData[historyData.length - 1].time);

            // Points
            historyData.forEach(p => {
                L.circleMarker([p.lat, p.lng], {radius: 3, color: 'red'}).addTo(map).bindPopup(p.time);
            });
        }
    </script>

</body>
</html>
