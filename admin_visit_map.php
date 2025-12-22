<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$from_date = $_GET['from_date'] ?? date('Y-m-d');
$to_date   = $_GET['to_date']   ?? date('Y-m-d');
$employee_id = $_GET['employee_id'] ?? '';

// Build Query for Assigned Members
$where1 = "WHERE am.latitude IS NOT NULL AND am.latitude != 0";
$params1 = [];
$types1 = "";

if ($from_date) {
    $where1 .= " AND DATE(am.created_at) >= ?";
    $params1[] = $from_date;
    $types1 .= "s";
}
if ($to_date) {
    $where1 .= " AND DATE(am.created_at) <= ?";
    $params1[] = $to_date;
    $types1 .= "s";
}
if ($employee_id) {
    $where1 .= " AND e.id = ?";
    $params1[] = $employee_id;
    $types1 .= "i";
}

// Build Query for Member Reports (Unassigned/Ad-hoc)
$where2 = "WHERE mr.latitude IS NOT NULL AND mr.latitude != 0";
$params2 = [];
$types2 = "";

if ($from_date) {
    $where2 .= " AND DATE(mr.created_at) >= ?";
    $params2[] = $from_date;
    $types2 .= "s";
}
if ($to_date) {
    $where2 .= " AND DATE(mr.created_at) <= ?";
    $params2[] = $to_date;
    $types2 .= "s";
}
if ($employee_id) {
    $where2 .= " AND mr.employee_id = ?";
    $params2[] = $employee_id;
    $types2 .= "i";
}

// Combine Logic
// 1. Assigned Members
$sql1 = "SELECT am.latitude, am.longitude, am.description, am.photo, am.created_at,
               e.name as emp_name, m.name as member_name, m.clinic_name, 'Assigned' as type
        FROM assigned_members am
        JOIN employees e ON am.created_by = e.id
        JOIN members m ON am.member_id = m.id
        $where1";

// 2. Member Reports
$sql2 = "SELECT mr.latitude, mr.longitude, mr.description, mr.photo, mr.created_at,
               e.name as emp_name, mr.member_name, mr.clinic_name, 'New/Adhoc' as type
        FROM member_reports mr
        JOIN employees e ON mr.employee_id = e.id
        $where2";

// We execute separately to handle params easily, then merge in PHP
$markers = [];

// Fetch Assigned
$stmt1 = $conn->prepare($sql1);
if (!empty($params1)) {
    $stmt1->bind_param($types1, ...$params1);
}
$stmt1->execute();
$res1 = $stmt1->get_result();
while ($row = $res1->fetch_assoc()) {
    $row['time'] = date('d M h:i A', strtotime($row['created_at']));
    $markers[] = $row;
}
$stmt1->close();

// Fetch Reports
$stmt2 = $conn->prepare($sql2);
if (!empty($params2)) {
    $stmt2->bind_param($types2, ...$params2);
}
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $row['time'] = date('d M h:i A', strtotime($row['created_at']));
    $markers[] = $row;
}
$stmt2->close();

// Fetch Employees for Filter
$empRes = $conn->query("SELECT id, name FROM employees ORDER BY name ASC");
$employees = [];
while ($r = $empRes->fetch_assoc()) $employees[] = $r;

include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 600px; width: 100%; border-radius: 8px; z-index: 1; }
</style>

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Member Visit Map</h3>
                            <div class="nk-block-des text-soft">
                                <p>Visualize where employees are submitting reports.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTERS -->
                <div class="nk-block">
                    <div class="card card-bordered">
                        <div class="card-inner">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Employee</label>
                                        <div class="form-control-wrap">
                                            <select name="employee_id" class="form-select form-control" data-search="on">
                                                <option value="">All Employees</option>
                                                <?php foreach ($employees as $emp): ?>
                                                    <option value="<?= $emp['id'] ?>" <?= ($employee_id == $emp['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($emp['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                     <div class="form-group">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                                     </div>
                                </div>
                                <div class="col-md-3">
                                     <div class="form-group">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                                     </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-control-wrap">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="admin_visit_map.php" class="btn btn-outline-light ml-2">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MAP -->
                 <div class="nk-block">
                    <div class="card card-bordered">
                        <div class="card-inner p-0">
                            <div id="map"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map = L.map('map').setView([17.000, 80.000], 7); // Default Center (AP/Telangana approx)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var markers = <?= json_encode($markers) ?>;
    var group = L.featureGroup();

    markers.forEach(function(m) {
        var lat = parseFloat(m.latitude);
        var lng = parseFloat(m.longitude);

        // Filter invalid
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
            
            // Differentiate color
            var color = (m.type === 'Assigned') ? '#09c2de' : '#6576ff'; 

            var iconHtml = `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`;
            var customIcon = L.divIcon({
                 className: 'custom-marker',
                 html: iconHtml,
                 iconSize: [20, 20],
                 iconAnchor: [10, 10]
            });

            var marker = L.marker([lat, lng], {icon: customIcon})
                .bindPopup(`
                    <div class="text-center">
                         <span class="badge badge-dim badge-outline-${Number(m.type === 'Assigned' ? 'info' : 'primary')} mb-2">${m.type}</span>
                        <h6 class="mb-1 text-primary">${m.clinic_name}</h6>
                        <p class="mb-1 text-xs"><b>${m.member_name}</b></p>
                        <p class="mb-1 text-xs text-muted">By: ${m.emp_name}</p>
                        <p class="mb-1 text-xs text-muted">${m.time}</p>
                        <p class="mb-1 text-[10px] text-gray-400">(${lat.toFixed(5)}, ${lng.toFixed(5)})</p>
                        ${m.photo ? `<a href="${m.photo}" target="_blank"><img src="${m.photo}" style="width:100px; height:auto; margin-top:5px; border-radius:4px;"></a>` : ''}
                        <p class="mt-1 text-sm">${m.description ? m.description : ''}</p>
                    </div>
                `);
            
            group.addLayer(marker);
        }
    });

    group.addTo(map);
    if(markers.length > 0) {
        map.fitBounds(group.getBounds(), {padding: [50, 50]});
    }

</script>
