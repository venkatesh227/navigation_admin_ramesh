<?php
session_start();

include 'header.php';
include_once('../db/connection.php');

// ---------- GET LOGGED-IN EMPLOYEE ID FROM SESSION ---------- //
$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
    die("User not logged in. Please login first.");
}
$employee_id = (int)$employee_id;

// ---------- FETCH EMPLOYEE DETAILS ---------- //
if (!($conn instanceof mysqli)) {
    die("Database connection error.");
}

$sql_emp = "SELECT id, user_id, name FROM employees WHERE id = ?";
$stmt_emp = $conn->prepare($sql_emp);
if (!$stmt_emp) {
    die("Failed to prepare employee query: " . $conn->error);
}

$stmt_emp->bind_param('i', $employee_id);
$stmt_emp->execute();
$res_emp = $stmt_emp->get_result();
$emp = $res_emp->fetch_assoc();
$stmt_emp->close();

if (!$emp) {
    die("Employee record not found.");
}

$employee_name = $emp['name'] ?: 'Employee';

// ---------- FETCH LOCATIONS FOR THIS EMPLOYEE'S ROUTES ---------- //
// NOTE: assign_routes.employee_id should reference employees.id
$locations = [];

// ✅ JOIN WITH members TO GET clinic_name
$sql = "
    SELECT 
        am.latitude, 
        am.longitude, 
        am.created_at, 
        am.description,
        m.clinic_name
    FROM assigned_members am
    INNER JOIN assign_routes ar ON am.assigned_route_id = ar.id
    INNER JOIN members m ON am.member_id = m.id
    WHERE 
        ar.employee_id = ?          -- ONLY THIS EMPLOYEE'S ROUTES
        AND am.latitude IS NOT NULL
        AND am.longitude IS NOT NULL
    ORDER BY am.created_at ASC
    LIMIT 25
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Failed to prepare locations query: " . $conn->error);
}

$stmt->bind_param('i', $employee_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $locations[] = [
        'lat'        => (float)$row['latitude'],
        'lng'        => (float)$row['longitude'],
        // ✅ USE CLINIC NAME INSTEAD OF DESCRIPTION
        'clinic'     => $row['clinic_name'] ?: "Clinic Not Found",
        // keep description too if you ever need it on front-end
        'description'=> $row['description'],
        'time'       => $row['created_at'],
    ];
}

$stmt->close();
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-3 flex justify-between items-center">
  <button onclick="toggleSidebar()" class="text-white text-2xl focus:outline-none">&#9776;</button>
  <h1 class="text-lg font-semibold">
    <a href="dashboard.php" class="inline-block hover:text-white font-semibold py-1 px-1 rounded">←</a>
    Health Hospitals FSMS
  </h1>
</header>

<div class="flex mt-2 p-3 gap-4">
  <div style="flex:0 0 20%;">
    <div id="sidebarOverlay" class="fixed inset-0 bg-gray bg-opacity-50 hidden z-40" onclick="closeSidebar()"></div>
    <div id="sidebar" class="fixed inset-y-0 left-0 bg-gray-900 text-white w-64 transform -translate-x-full transition-transform duration-300 z-50">
      <div class="p-3 flex justify-between items-center border-b border-gray-600">
        <span class="font-bold text-lg">Menu</span>
        <button onclick="closeSidebar()" class="text-white text-xl font-bold">&times;</button>
      </div>
      <?php include 'navbar.php'; ?>
    </div>
  </div>

  <div class="bg-white p-3 rounded shadow" style="flex:0 0 80%;">
    <h2 class="text-lg font-semibold mb-2">
      <?php echo htmlspecialchars($employee_name); ?> - Visit Locations
    </h2>
    <div id="map" style="height:450px;width:100%;border-radius:10px;">
      <?php if (empty($locations)) echo "<p style='padding:20px;text-align:center;'>No visit locations found for this employee.</p>"; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<!-- MAP SCRIPT -->
<script>
let leafletMap = null;
let markersLayer = null;

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('-translate-x-full');
  document.getElementById('sidebarOverlay').classList.toggle('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('sidebarOverlay').classList.add('hidden');
}

function getRandomColor() {
    const colors = ["#ff2e2e", "#2e8bff", "#29c46b", "#ff9900", "#9b59b6", "#e91e63"];
    return colors[Math.floor(Math.random() * colors.length)];
}

function loadLeafletMap() {
    const locations = <?php echo json_encode($locations); ?>;

    if (!locations || locations.length === 0) {
        document.getElementById("map").innerHTML =
            "<p style='padding:20px;text-align:center;'>No Location Found</p>";
        return;
    }

    leafletMap = L.map('map').setView([locations[0].lat, locations[0].lng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(leafletMap);

    markersLayer = L.layerGroup();
    leafletMap.addLayer(markersLayer);

    locations.forEach((loc, i) => {
        addMarker(loc, i + 1);
    });

    fitBoundsToLayer();
}

function addMarker(loc, number) {
    const customIcon = L.divIcon({
        className: "custom-pin",
        html: `
            <div class="pin-wrapper">
                <div class="pin-badge" style="background:${getRandomColor()}">${number}</div>
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" class="pin-img">
            </div>
        `,
        iconSize: [40, 50],
        iconAnchor: [15, 40]
    });

    const marker = L.marker([loc.lat, loc.lng], { icon: customIcon });

    const dateObj = new Date(loc.time);
    const formattedDate =
        dateObj.getDate().toString().padStart(2,'0') + '-' +
        (dateObj.getMonth()+1).toString().padStart(2,'0') + '-' +
        dateObj.getFullYear();

    marker.bindTooltip(
        `<b>Clinic:</b> ${loc.clinic || 'No Clinic Name'}<br> <b>Date:</b> ${formattedDate}`,
        { permanent:false, direction:"top", offset:[0,-20], opacity:0.9 }
    );

    marker.on('mouseover', function () { this.openTooltip(); });
    marker.on('mouseout', function () { this.closeTooltip(); });

    markersLayer.addLayer(marker);
}

function fitBoundsToLayer() {
    if (markersLayer && markersLayer.getLayers().length > 0) {
        let group = L.featureGroup(markersLayer.getLayers());
        leafletMap.fitBounds(group.getBounds(), { padding: [30, 30] });
    }
}

document.addEventListener("DOMContentLoaded", loadLeafletMap);
</script>
