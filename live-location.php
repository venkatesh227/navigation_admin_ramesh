<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');

$locations = [];

// ************ FIXED SQL ************
// No parameters. No bind_param.
// Shows ALL employees + ALL members visited TODAY.

$sql = "
    SELECT 
        lt.latitude,
        lt.longitude,
        lt.last_updated as created_at,
        'Current Location' as clinic_name,
        e.id AS employee_id,
        e.name AS employee_name
    FROM live_tracking lt
    INNER JOIN employees e ON lt.user_id = e.user_id
    WHERE lt.last_updated >= DATE_SUB(NOW(), INTERVAL 12 HOUR)
";


$stmt = $conn->prepare($sql);
$stmt->execute();
$res = $stmt->get_result();

// ************ FETCH DATA ************
while ($row = $res->fetch_assoc()) {
    $locations[] = [
        'lat' => (float)$row['latitude'],
        'lng' => (float)$row['longitude'],
        'clinic' => $row['clinic_name'] ?: 'No Clinic Name',
        'employee' => $row['employee_name'],
        'employee_id' => (int)$row['employee_id'],
        'time' => $row['created_at']
    ];
}
?>

<?php include 'header.php'; ?>

<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">

                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Live Location</h3>
                        </div>
                    </div>
                </div>

                <div class="nk-block">
                    <div class="card card-bordered">
                        <div class="card-inner">

                            <h6 class="title mb-2">Sales Persons Live Location</h6>
                            <div id="map" class="p-2 mt-3" style="width:100%; height:450px;"></div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function getEmployeeColor(empId) {
    const colors = [
        "#ff2e2e", "#2e8bff", "#29c46b", "#ff9900",
        "#9b59b6", "#e91e63", "#00bfa5", "#8d6e63"
    ];
    return colors[empId % colors.length];
}

function getOffset(index) {
    const offsets = [
        [0,0],[10,0],[-10,0],[0,10],[0,-10],
        [10,10],[-10,10],[10,-10],[-10,-10]
    ];
    return offsets[index % offsets.length];
}

document.addEventListener('DOMContentLoaded', function() {

    const locations = <?php echo json_encode($locations); ?>;
    console.log("LIVE DATA:", locations);

    if (!locations || locations.length === 0) {
        document.getElementById("map").innerHTML =
            "<p style='padding:20px;text-align:center;'>No Location Found for Today</p>";
        return;
    }

    var map = L.map('map').setView([locations[0].lat, locations[0].lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    let markers = [];

    locations.forEach((loc, index) => {

        const pinNumber = index + 1;
        const color = getEmployeeColor(loc.employee_id);
        const offset = getOffset(index);

        const customIcon = L.divIcon({
            className: "custom-pin",
            html: `
                <div class="pin-wrapper" style="transform: translate(${offset[0]}px, ${offset[1]}px);">
                    <div class="pin-badge" style="background:${color}">${pinNumber}</div>
                    <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png"
                        style="width:35px;">
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });

        let marker = L.marker([loc.lat, loc.lng], { icon: customIcon }).addTo(map);

        marker.bindTooltip(
            `<b>Employee:</b> ${loc.employee}<br>
             <b>Clinic:</b> ${loc.clinic}<br>
             <b>Time:</b> ${loc.time}`,
            { direction: "top", offset: [0, -20], sticky: true }
        );

        markers.push(marker);
    });

    let group = L.featureGroup(markers);
    map.fitBounds(group.getBounds(), { padding: [40, 40] });

});
</script>

<?php include 'footer.php'; ?>
