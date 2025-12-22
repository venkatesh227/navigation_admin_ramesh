<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');


/**********************************************
   AJAX: LOAD GROUPS BY ROUTE (EDIT + ADD)
**********************************************/

/**********************************************
   AJAX: GET ALL EMPLOYEES (FOR EDIT MODAL)
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "get_all_employees") {

    $arr = [];
    $q = mysqli_query($conn, "SELECT id, name FROM employees ORDER BY name ASC");

    while ($r = mysqli_fetch_assoc($q)) {
        $arr[] = $r;
    }

    echo json_encode($arr);
    exit;
}
/**********************************************
   AJAX: LOAD GROUPS BY ROUTE (EDIT + ADD)
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "load_groups_by_route") {

    $route_id = intval($_POST['route_id']);
    $data = [];

    $q = mysqli_query($conn, "
        SELECT DISTINCT g.id, g.name 
        FROM groups g
        JOIN members m ON m.group_id = g.id
        WHERE m.route_id = '$route_id'
        AND g.status = 1
        ORDER BY g.name ASC
    ");

    while ($row = mysqli_fetch_assoc($q)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

/**********************************************
   AJAX: GET ALL ROUTES (EDIT MODAL)
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "get_all_routes") {

    $arr = [];
    $q = mysqli_query($conn, "SELECT id, name FROM routes ORDER BY name ASC");

    while ($r = mysqli_fetch_assoc($q)) {
        $arr[] = $r;
    }

    echo json_encode($arr);
    exit;
}





/**********************************************
   AJAX: LOAD CLINICS BY MULTIPLE GROUPS
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "load_clinics") {

    if (!isset($_POST['group_id']) || count($_POST['group_id']) == 0) {
        echo json_encode([]);
        exit;
    }

    $group_ids = $_POST['group_id'];
    $grp_str = implode(",", array_map('intval', $group_ids));

    $data = [];

   $route_id = intval($_POST["route_id"]);   // ADD THIS LINE

$q = mysqli_query($conn, "
    SELECT id, clinic_name 
    FROM members
    WHERE group_id IN($grp_str)
    AND route_id = '$route_id'
    AND status = 1
    ORDER BY clinic_name ASC
");


    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }

    echo json_encode($data);
    exit;
}


/**********************************************
   AJAX: SAVE NEW ASSIGNMENT
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "save_assign") {

    $employee_id = intval($_POST['employee_id']);
    $route_id    = intval($_POST['route_id']);
    $group_ids   = $_POST['group_id'];
    $start       = $_POST['start_date'];
    $end         = $_POST['end_date'];
    $clinics     = $_POST['clinics'];

    if (!$employee_id || !$route_id || empty($group_ids)) {
        echo json_encode(["status"=>"error", "msg"=>"Missing required fields"]);
        exit;
    }
    // ---------------- DUPLICATE CHECK -------------------
$group_csv = implode(",", array_map('intval', $group_ids));

$dup = mysqli_query($conn, "
    SELECT id 
    FROM assign_routes 
    WHERE employee_id = '$employee_id'
      AND route_id = '$route_id'
      AND (
          start_date <= '$end' AND end_date >= '$start'
      )
");

if (mysqli_num_rows($dup) > 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "Duplicate Assignment! This Sales Executive already has an assignment for the same Route and date range."
    ]);
    exit;
}


    if (strtotime($end) < strtotime($start)) {
        echo json_encode(["status"=>"error", "msg"=>"End date cannot be earlier"]);
        exit;
    }

    // ---------------- DUPLICATE CHECK FOR UPDATE -------------------
$group_csv = implode(",", array_map('intval', $group_ids));

$dup2 = mysqli_query($conn, "
    SELECT id 
    FROM assign_routes 
    WHERE employee_id = '$employee_id'
      AND route_id = '$route_id'
      AND id != '$id'
      AND (
          start_date <= '$end' AND end_date >= '$start'
      )
");

if (mysqli_num_rows($dup2) > 0) {
    echo json_encode([
        "status" => "error",
        "msg" => "Duplicate Assignment! Another assignment already exists for this Sales Executive with same Route and overlapping date."
    ]);
    exit;
}

    $group_csv = implode(",", array_map('intval', $group_ids));

    mysqli_query($conn, "
        INSERT INTO assign_routes 
        (employee_id, route_id, group_id, start_date, end_date, created_at, created_by)
        VALUES ('$employee_id', '$route_id', '$group_csv', '$start', '$end', NOW(), '{$_SESSION['user_id']}')
    ");

    $assign_id = mysqli_insert_id($conn);

    // Insert clinics
    foreach ($clinics as $c) {
        $c = intval($c);
        mysqli_query($conn, "
            INSERT INTO assigned_members 
            (assigned_route_id, member_id, created_at, created_by)
            VALUES('$assign_id', '$c', NOW(), '{$_SESSION['user_id']}')
        ");
    }

    echo json_encode(["status"=>"success"]);
    exit;
}


/**********************************************
   AJAX: LOAD ASSIGN FOR EDIT
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "get_assign") {

    $id = intval($_POST['id']);

    $q = mysqli_query($conn, "SELECT * FROM assign_routes WHERE id='$id' LIMIT 1");
    $assign = mysqli_fetch_assoc($q);

    if (!$assign) {
        echo json_encode(["status"=>"error", "msg"=>"Assignment not found"]);
        exit;
    }

    // group CSV → array
    $assign['group_ids'] = explode(",", $assign['group_id']);

    // load clinics
    $clinic_ids = [];
    $c = mysqli_query($conn, "SELECT member_id FROM assigned_members WHERE assigned_route_id='$id'");
    while ($r = mysqli_fetch_assoc($c)) {
        $clinic_ids[] = $r['member_id'];
    }

    echo json_encode([
        "assign"  => $assign,
        "clinics" => $clinic_ids
    ]);
    exit;
}


/**********************************************
   AJAX: UPDATE ASSIGNMENT
**********************************************/
if (isset($_POST['ajax']) && $_POST['ajax'] == "update_assign") {

    $id          = intval($_POST['id']);
    $employee_id = intval($_POST['employee_id']);
    $route_id    = intval($_POST['route_id']);
    $group_ids   = $_POST['group_id'];
    $start       = $_POST['start_date'];
    $end         = $_POST['end_date'];
    $clinics     = $_POST['clinics'];

    if (strtotime($end) < strtotime($start)) {
        echo json_encode(["status"=>"error", "msg"=>"End date cannot be earlier"]);
        exit;
    }

    $group_csv = implode(",", array_map('intval', $group_ids));

    mysqli_query($conn, "
        UPDATE assign_routes SET 
        employee_id='$employee_id',
        route_id='$route_id',
        group_id='$group_csv',
        start_date='$start',
        end_date='$end'
        WHERE id='$id'
    ");

    // delete old clinics
    mysqli_query($conn, "DELETE FROM assigned_members WHERE assigned_route_id='$id'");

    // insert new clinics
    foreach ($clinics as $c) {
        $c = intval($c);
        mysqli_query($conn, "
            INSERT INTO assigned_members 
            (assigned_route_id, member_id, created_at, created_by)
            VALUES('$id', '$c', NOW(), '{$_SESSION['user_id']}')
        ");
    }

    echo json_encode(["status"=>"success"]);
    exit;
}
include 'header.php';
?>

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">

                <h3 class="nk-block-title page-title mb-4">Sales Executive Route Assignment</h3>

                <div class="card">
                    <div class="card-inner">

                        <!-- ================= ADD ASSIGNMENT FORM ================= -->
                        <form id="assignForm">

                            <div class="row g-4 mb-4">
                                <!-- Employee -->
                                <div class="col-md-6">
                                    <label class="form-label">Select Sales Executive</label>
                                    <select id="employee" class="form-control">
                                        <option value="">Choose Sales Executive</option>
                                        <?php
                                        $emp = mysqli_query($conn, "SELECT id,name FROM employees ORDER BY name ASC");
                                        while ($e = mysqli_fetch_assoc($emp)) {
                                            echo "<option value='{$e['id']}'>{$e['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div class="col-md-6">
                                    <label class="form-label">Select Date Range</label>
                                    <div class="input-daterange input-group">
                                        <input type="date" id="startDate" class="form-control">
                                        <span class="input-group-addon">TO</span>
                                        <input type="date" id="endDate" class="form-control">
                                    </div>
                                    <div id="date_error" class="text-danger small"></div>
                                </div>
                            </div>


                            <div class="row g-4 mb-4">
                                <!-- Route -->
                                <div class="col-md-6">
                                    <label class="form-label">Select Route</label>
                                    <select id="route" class="form-control">
                                        <option value="">Choose Route</option>
                                        <?php
                                        $rt = mysqli_query($conn, "SELECT id,name FROM routes ORDER BY name ASC");
                                        while ($r = mysqli_fetch_assoc($rt)) {
                                            echo "<option value='{$r['id']}'>{$r['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div id="route_error" class="text-danger small"></div>
                                </div>

                                <!-- Groups -->
                                <div class="col-md-6">
                                    <label class="form-label">Select Groups</label>
                                    <select id="group" class="form-control" multiple></select>
                                    <div id="group_error" class="text-danger small"></div>
                                </div>
                            </div>

                            <!-- Clinics -->
                            <div id="clinicSection" class="mb-3"></div>

                            <div class="text-center mt-3" id="submitBtn" style="display:none;">
                                <button type="button" id="assignSubmitBtn" class="btn btn-primary btn-lg">
                                    Submit
                                </button>
                            </div>

                        </form>

                        <!-- ================= ASSIGNMENTS TABLE ================= -->
                       <!-- ================= ASSIGNMENTS TABLE ================= -->
<div class="mt-5">
    <h4 class="table-title">Assigned Routes</h4>

    <table class="datatable-init nowrap table table-striped">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Sales Executive</th>
                <th>Date Range</th>
                <th>Route</th>
                <th>Groups</th>
                <th>Clinics</th>
                <th data-priority="1">Action</th>
            </tr>
        </thead>

        <tbody>
        <?php
        // ================== ASSIGNED ROUTES QUERY ==================
        $asSql = "
            SELECT a.*, e.name AS emp, r.name AS rt,
                   GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS groups_list,
                   GROUP_CONCAT(DISTINCT m.clinic_name SEPARATOR ', ') AS clinic_list
            FROM assign_routes a
            JOIN employees e ON e.id = a.employee_id
            JOIN routes r    ON r.id = a.route_id
            LEFT JOIN groups g          ON FIND_IN_SET(g.id, a.group_id)
            LEFT JOIN assigned_members am ON am.assigned_route_id = a.id
            LEFT JOIN members m           ON m.id = am.member_id
            GROUP BY a.id
            ORDER BY a.id DESC
        ";
        $as = mysqli_query($conn, $asSql);

        // ================== ⭐ ADD-MEMBER ROUTES QUERY ==================
        $starSql = "
            SELECT 
                mr.id,
                mr.employee_id,
                mr.clinic_name,
                mr.route,
                mr.group_name,
                mr.created_at,
                e.name AS emp
            FROM member_reports mr
            JOIN employees e ON e.id = mr.employee_id
            ORDER BY mr.created_at DESC
        ";
        $starRes = mysqli_query($conn, $starSql);

        $i = 1;

        /* ---------------- NORMAL ASSIGNED ROUTES ---------------- */
        if ($as instanceof mysqli_result && mysqli_num_rows($as) > 0) {

            while ($row = mysqli_fetch_assoc($as)) {
                $sd = date("d-m-Y", strtotime($row["start_date"]));
                $ed = date("d-m-Y", strtotime($row["end_date"]));
                ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($row["emp"]) ?></td>
                    <td><?= $sd . " to " . $ed ?></td>
                    <td><?= htmlspecialchars($row["rt"]) ?></td>
                    <td><?= htmlspecialchars($row["groups_list"]) ?></td>
                    <td><?= htmlspecialchars($row["clinic_list"]) ?></td>
                    <td>
                        <?php
                        $assign_id = $row['id'];

                        // your existing Reported / Edit logic
                        $checkReport = mysqli_query($conn, "
                            SELECT id 
                            FROM assigned_members
                            WHERE assigned_route_id = '$assign_id'
                              AND photo IS NOT NULL 
                              AND photo <> ''
                            LIMIT 1
                        ");

                        $reportExists = $checkReport && mysqli_num_rows($checkReport) > 0;

                        if ($reportExists) {
                            echo '<button class="btn btn-secondary btn-sm" disabled>Reported</button>';
                        } else {
                            echo '<button class="btn btn-primary btn-sm" onclick="openEditModal('.$row['id'].')">Edit</button>';
                        }
                        ?>
                    </td>
                </tr>
                <?php
                $i++;
            }
        }

        /* ---------------- ⭐ ADD-MEMBER ROUTES (member_reports) ---------------- */
        if ($starRes instanceof mysqli_result && mysqli_num_rows($starRes) > 0) {

            while ($s = mysqli_fetch_assoc($starRes)) {
                $d = date("d-m-Y", strtotime($s["created_at"])); // single-day range
                ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($s["emp"]) ?></td>
                    <td><?= $d . " to " . $d ?></td>
                    <td><?= htmlspecialchars($s["route"]) ?></td>
                    <td><?= htmlspecialchars($s["group_name"]) ?></td>
                    <td>⭐ <?= htmlspecialchars($s["clinic_name"]) ?></td>
                    <td>
                        <!-- Always reported, no edit -->
                        <button class="btn btn-secondary btn-sm" disabled>Reported</button>
                    </td>
                </tr>
                <?php
                $i++;
            }
        }

        ?>
        </tbody>
    </table>
</div>


                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<!-- ======================= EDIT MODAL ======================= -->
<div class="modal fade" id="editAssignModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Assignment</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="edit_assign_id">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Employee</label>
                        <select id="edit_employee" class="form-control"></select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date Range</label>
                        <div class="input-daterange input-group">
                            <input type="date" id="edit_start" class="form-control">
                            <span class="input-group-addon">TO</span>
                            <input type="date" id="edit_end" class="form-control">
                        </div>
                        <div id="edit_date_error" class="text-danger small"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Route</label>
                        <select id="edit_route" class="form-control"></select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Groups</label>
                        <select id="edit_group" class="form-control" multiple></select>
                    </div>
                </div>

                <div id="edit_clinic_section" class="mt-3"></div>
                <div id="edit_clinic_error" class="text-danger small"></div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" id="editUpdateBtn">Update</button>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<style>
.dataTables_empty {
    display: none !important;
}
</style>



<!-- SELECT2 + SWEETALERT -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/**************************************************************
   INIT SELECT2
**************************************************************/
$(document).ready(function () {

    // Single select
    $("#employee, #route, #edit_employee, #edit_route").select2({
        width: "100%",
        placeholder: "Choose Option",
        allowClear: true
    });

    // Multi-select (groups)
    $("#group, #edit_group").select2({
        width: "100%",
        placeholder: "Choose Groups",
        allowClear: true,
        multiple: true
    });

});


    

/*******************************************************************
   LOAD GROUPS WHEN ROUTE CHANGES  (ADD MODE)
*******************************************************************/
$("#route").on("change", function () {

    let route_id = $(this).val();

    $("#group").empty().trigger("change");
    $("#clinicSection").html("");
    $("#submitBtn").hide();

    if (!route_id) return;

    $.post("assign-route.php", {
        ajax: "load_groups_by_route",
        route_id: route_id
    }, function (res) {

        let groups = JSON.parse(res);

        $("#group").empty();

        if (groups.length === 0) {
            $("#group").append(`<option value="">No Groups Found</option>`).trigger("change");
            return;
        }

        groups.forEach(g => {
            $("#group").append(`<option value="${g.id}">${g.name}</option>`);
        });

        $("#group").trigger("change");
    });
});


/*******************************************************************
   LOAD CLINICS WHEN GROUPS SELECTED (ADD MODE)
*******************************************************************/
$("#group").on("change", function () {
    loadClinics();
});

function loadClinics() {

    let groups = $("#group").val();

    if (!groups || groups.length === 0) {
        $("#clinicSection").html("");
        $("#submitBtn").hide();
        return;
    }

   $.post("assign-route.php", {
    ajax: "load_clinics",
    group_id: groups,
    route_id: $("#route").val()   // ADD THIS
}, function (res) {


        let clinics = JSON.parse(res);

        if (clinics.length === 0) {
            $("#clinicSection").html("<h5>No Clinics Found</h5>");
            $("#submitBtn").hide();
            return;
        }

        let html = `<h5>Clinics</h5>`;

        clinics.forEach(c => {
            html += `
                <div>
                    <input type="checkbox" class="chk" value="${c.id}">
                    <label>${c.clinic_name}</label>
                </div>`;
        });

        $("#clinicSection").html(html);
        $("#submitBtn").show();
    });
}


/*******************************************************************
   SAVE NEW ASSIGNMENT
*******************************************************************/
$("#assignSubmitBtn").on("click", function () {

    let emp = $("#employee").val();
    let start = $("#startDate").val();
    let end = $("#endDate").val();
    let route = $("#route").val();
    let groups = $("#group").val();

    let clinics = [];
    $(".chk:checked").each(function () { clinics.push($(this).val()); });

    let hasError = false;

    // CLEAR ALL ERROR DIVS
    $("#employee_error").remove();
    $("#route_error").html("");
    $("#group_error").html("");
    $("#date_error").html("");
    $("#clinic_error").remove();

    // Employee required
    if (!emp) {
        $("#employee").parent().append(`
            <div id="employee_error" class="text-danger small">
                * Sales Executive is required
            </div>
        `);
        hasError = true;
    }

    // Route required
    if (!route) {
        $("#route_error").html("* Required");
        hasError = true;
    }

    // Groups required
    if (!groups || groups.length === 0) {
        $("#group_error").html("* Required");
        hasError = true;
    }

    // Date validation
    if (!start || !end) {
        $("#date_error").html("* Date range is required");
        hasError = true;
    } else if (new Date(end) < new Date(start)) {
        $("#date_error").html("* End date cannot be earlier than start date");
        hasError = true;
    }

    // Clinics
    if (clinics.length === 0) {
        $("#clinicSection").append(`
            <div id="clinic_error" class="text-danger small">
                * Select at least one clinic
            </div>
        `);
        hasError = true;
    }

    if (hasError) return;

    // AJAX SAVE
    $.post("assign-route.php", {
        ajax: "save_assign",
        employee_id: emp,
        route_id: route,
        group_id: groups,
        start_date: start,
        end_date: end,
        clinics: clinics
    }, function (res) {

        let data = JSON.parse(res);

        if (data.status === "success") {
            Swal.fire({
                icon: "success",
                title: "Assigned Successfully!",
                timer: 1500,
                showConfirmButton: false
            });

            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire({
                icon: "error",
                title: data.msg
            });
        }
    });
});

function loadEditEmployees(selectedID) {

    $("#edit_employee").empty();

    $.ajax({
        url: "assign-route.php",
        type: "POST",
        data: { ajax: "get_all_employees" },
        success: function (res) {

            let data = JSON.parse(res);

            data.forEach(e => {
                $("#edit_employee").append(
                    `<option value="${e.id}">${e.name}</option>`
                );
            });

            // auto select employee
            $("#edit_employee").val(selectedID).trigger("change");
        }
    });
}
function loadEditRoutes(selectedID) {

    $("#edit_route").empty();

    $.ajax({
        url: "assign-route.php",
        type: "POST",
        data: { ajax: "get_all_routes" },
        success: function (res) {

            let data = JSON.parse(res);

            data.forEach(r => {
                $("#edit_route").append(
                    `<option value="${r.id}">${r.name}</option>`
                );
            });

            // auto select route
            $("#edit_route").val(selectedID).trigger("change");
        }
    });
}




/*******************************************************************
   OPEN EDIT MODAL  (FULLY FIXED)
*******************************************************************/

function loadEditRoutes(selectedID, callback) {

    $("#edit_route").empty();

    $.post("assign-route.php", { ajax: "get_all_routes" }, function (res) {

        let routes = JSON.parse(res);

        routes.forEach(r => {
            $("#edit_route").append(
                `<option value="${r.id}">${r.name}</option>`
            );
        });

        $("#edit_route").val(selectedID).trigger("change");

        if (callback) callback();
    });
}

function openEditModal(id) {

    $("#editAssignModal").modal("show");

    $.post("assign-route.php", { ajax: "get_assign", id: id }, function (res) {

        let data = JSON.parse(res);
        let a = data.assign;
        let selectedClinics = data.clinics;

        $("#edit_assign_id").val(a.id);

        // 🔥 First load Employee list then select
        loadEditEmployees(a.employee_id);

        // 🔥 First load Route list then select
        loadEditRoutes(a.route_id);

        // Load groups based on route AFTER route loaded
        setTimeout(() => {

            $.post("assign-route.php", {
                ajax: "load_groups_by_route",
                route_id: a.route_id
            }, function (res2) {

                let groups = JSON.parse(res2);

                $("#edit_group").empty();

                groups.forEach(g => {
                    $("#edit_group").append(`<option value="${g.id}">${g.name}</option>`);
                });

                $("#edit_group").val(a.group_ids).trigger("change");

                loadEditClinics(a.group_ids, selectedClinics);

            });

        }, 300);

        $("#edit_start").val(a.start_date);
        $("#edit_end").val(a.end_date);
    });
}



/*******************************************************************
   LOAD CLINICS IN EDIT MODAL
*******************************************************************/
function loadEditClinics(groups, selectedClinics) {

 $.post("assign-route.php", {
    ajax: "load_clinics",
    group_id: groups,
    route_id: $("#edit_route").val()  // ADD THIS
}, function (res) {


        let clinics = JSON.parse(res);

        let html = "<h5>Clinics</h5>";

        clinics.forEach(c => {

            let checked = selectedClinics.includes(c.id) ? "checked" : "";

            html += `
                <div>
                    <input type="checkbox" class="edit_chk" value="${c.id}" ${checked}>
                    <label>${c.clinic_name}</label>
                </div>`;
        });

        $("#edit_clinic_section").html(html);
    });
}

/*******************************************************************
   WHEN ROUTE CHANGES IN EDIT MODAL → RELOAD GROUPS + CLINICS
*******************************************************************/
$("#edit_route").on("change", function () {

    let route_id = $(this).val();

    if (!route_id) return;

    // Load groups for the selected new route
    $.post("assign-route.php", {
        ajax: "load_groups_by_route",
        route_id: route_id
    }, function (res) {

        let groups = JSON.parse(res);

        $("#edit_group").empty();

        groups.forEach(g => {
            $("#edit_group").append(`<option value="${g.id}">${g.name}</option>`);
        });

        // Clear old selected groups
        $("#edit_group").val([]).trigger("change");

        // After groups load → load clinics
        $("#edit_route").on("change", function () {

    let route_id = $(this).val();

    if (!route_id) return;

    // Load groups for the selected new route
    $.post("assign-route.php", {
        ajax: "load_groups_by_route",
        route_id: route_id
    }, function (res) {

        let groups = JSON.parse(res);

        $("#edit_group").empty();

        groups.forEach(g => {
            $("#edit_group").append(`<option value="${g.id}">${g.name}</option>`);
        });

        // Clear groups on route change
        $("#edit_group").val([]).trigger("change");

        // ❌ No clinics loaded here
        // Clinics will load only when user selects groups
        $("#edit_clinic_section").html("");
    });
});

    });
});

/*******************************************************************
   WHEN GROUPS CHANGE IN EDIT MODAL → LOAD CLINICS
*******************************************************************/
$("#edit_group").on("change", function () {

    let groups = $(this).val();

    if (!groups || groups.length === 0) {
        $("#edit_clinic_section").html("");
        return;
    }

    loadEditClinics(groups, []); // no pre-selected clinics when changing group
});





/*******************************************************************
   UPDATE ASSIGNMENT (FINAL SAVE)
*******************************************************************/
$("#editUpdateBtn").on("click", function () {

    let id = $("#edit_assign_id").val();
    let emp = $("#edit_employee").val();
    let route = $("#edit_route").val();
    let groups = $("#edit_group").val();
    let start = $("#edit_start").val();
    let end = $("#edit_end").val();

    let clinics = [];
    $(".edit_chk:checked").each(function () {
        clinics.push($(this).val());
    });

    $("#edit_clinic_error").html("");

    if (clinics.length === 0) {
        $("#edit_clinic_error").html("* Select at least one clinic");
        return;
    }

    $.post("assign-route.php", {
        ajax: "update_assign",
        id: id,
        employee_id: emp,
        route_id: route,
        group_id: groups,
        start_date: start,
        end_date: end,
        clinics: clinics
    }, function (res) {

        let data = JSON.parse(res);

        if (data.status === "success") {
            Swal.fire({
                icon: "success",
                title: "Updated Successfully!",
                timer: 1500,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1500);
        }
    });

});
</script>
<!-- <script>
document.addEventListener("DOMContentLoaded", function() {
    // Get today's date in YYYY-MM-DD format
    let today = new Date().toISOString().split("T")[0];

    // Set min date
    document.getElementById("startDate").setAttribute("min", today);
    document.getElementById("endDate").setAttribute("min", today);

    document.getElementById("edit_start").setAttribute("min", today);
    document.getElementById("edit_end").setAttribute("min", today);
});
</script> -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const todayStr = new Date().toISOString().split("T")[0];

    const ids = ["startDate", "endDate", "edit_start", "edit_end"];

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        el.setAttribute("min", todayStr);

        // VALIDATE ONLY AFTER TYPING → on blur
        el.addEventListener("blur", function () {

            const v = this.value; // yyyy-mm-dd or empty

            if (!v || v.length < 10) return;  // user still typing or empty

            // simple string comparison
            if (v < todayStr) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Date",
                    text: "Past dates are not allowed!",
                });
                this.value = "";
            }
        });
    });
});
</script>




     