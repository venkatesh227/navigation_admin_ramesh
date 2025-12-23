<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
include 'header.php'
?>
<!-- main header @e -->
<!-- content @s -->
<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col">
                                    <h5 class="title">Groups</h5>
                                </div>
                                <div class="col text-right">
                                    <button id="openAddGroupBtn" class="btn btn-primary" data-toggle="modal"
                                        data-target="#addGroupModal">
                                        <em class="icon ni ni-plus"></em> Add Group
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped datatable-init">
                                    <thead>
                                        <tr>
                                            <th>S.NO</th>
                                            <th>Group Name</th>
                                            <th>Status</th>
                                            <th>Members</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // fetch groups from DB and render dynamically
                                        $gSql = "SELECT * FROM `groups` ORDER BY id DESC";
                                        $gRes = mysqli_query($conn, $gSql);
                                        $sno = 1;
                                        if ($gRes && mysqli_num_rows($gRes) > 0) {
                                            while ($g = mysqli_fetch_assoc($gRes)) {
                                                $gid = intval($g['id']);
                                                $gname = htmlspecialchars($g['name']);
                                                $gstatus = intval($g['status']);
                                                $badge = $gstatus === 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
                                                $statusText = $gstatus === 1 ? 'Active' : 'Inactive';
                                                echo "<tr>\n";
                                                echo "<td>{$sno}</td>\n";
                                                echo "<td>{$gname}</td>\n";
                                                echo "<td>{$badge}</td>\n";
                                                echo "<td>\n";

                                                if ($gstatus === 1) {
                                                    // ACTIVE → enable Add Member
                                                    echo "<button class=\"btn btn-sm btn-outline-primary add-member-btn\" 
            data-toggle=\"modal\" data-target=\"#addMemberModal\" 
            data-group-id=\"{$gid}\">
            <em class=\"icon ni ni-plus\"></em> Add Member
          </button>\n";
                                                } else {
                                                    // INACTIVE → disable Add Member


                                                    echo "<button class=\"btn btn-sm btn-outline-secondary add-member-btn\" disabled>

            <em class=\"icon ni ni-plus\"></em> Add Member
          </button>\n";
                                                }

                                                echo "</td>\n";

                                                echo "<td class=\"text-right\">\n";
                                                echo "<div class=\"dropdown\">\n";
                                                echo "<a href=\"#\" class=\"btn btn-sm btn-icon btn-trigger\" data-toggle=\"dropdown\">\n";
                                                echo "<em class=\"icon ni ni-more-h\"></em>\n";
                                                echo "</a>\n";
                                                echo "<div class=\"dropdown-menu dropdown-menu-right\">\n";
                                                echo "<ul class=\"link-list-opt no-bdr\">\n";
                                                echo "<li><a href=\"./doctors.php?group_id={$gid}\"><em class=\"icon ni ni-eye\"></em><span>View Member</span></a></li>\n";
                                                echo "<li><a href=\"#\" class=\"edit-group\" data-group-id=\"{$gid}\" data-group-name=\"{$gname}\"><em class=\"icon ni ni-edit\"></em><span>Edit Group</span></a></li>\n";
                                                echo "<li><a href=\"#\" class=\"toggleStatus\" data-group-id=\"{$gid}\" data-status=\"{$statusText}\"><em class=\"icon ni ni-power\"></em><span>Change Status</span></a></li>\n";
                                                echo "</ul>\n";
                                                echo "</div>\n";
                                                echo "</div>\n";
                                                echo "</td>\n";
                                                echo "</tr>\n";
                                                $sno++;
                                            }
                                        } else { ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center;">No groups found</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        <?php }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Group Modal -->
                <div class="modal fade" tabindex="-1" id="addGroupModal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Group</h5>
                                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                                    <em class="icon ni ni-cross"></em>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form id="groupForm">
                                    <input type="hidden" id="editGroupId" value="">
                                    <div class="form-group">
                                        <label class="form-label" for="groupName">Group Name <span class="text-danger">*</span></label>
                                        <div class="form-control-wrap">
                                            <input type="text" class="form-control" id="groupName" placeholder="Enter group name" required>
                                            <span id="groupError" style="color:red; font-size:12px;"></span>

                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer bg-light">
                                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" id="saveGroup">Save Group</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Member Modal -->
                <div class="modal fade" tabindex="-1" id="addMemberModal">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add member to Group</h5>
                                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                                    <em class="icon ni ni-cross"></em>
                                </a>
                            </div>
                            <div class="modal-body">
                                <form>
                                    <input type="hidden" id="memberGroupId" value="">
                                    <input type="hidden" id="memberId" value="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="routeSelect">Route <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <select class="form-control" id="routeSelect" required>
                                                        <option value="" disabled selected>Select a place</option>
                                                    </select>
                                                    <span id="routeError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6"></div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="doctorName">Name <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" class="form-control" id="doctorName" required
                                                        placeholder="Enter Full Name">
                                                    <span id="doctorNameError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="doctorQualification">Qualification <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" class="form-control" id="doctorQualification" required
                                                        placeholder="Enter Qualification">
                                                    <span id="doctorQualificationError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="clientName">Clinic Name <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" class="form-control" id="clinicName" required
                                                        placeholder="Enter clinic name">
                                                    <span id="clinicNameError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="address">Address <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" class="form-control" id="address" required
                                                        placeholder="Enter Address">
                                                    <span id="addressError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="location">Village/town/city <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="text" class="form-control" id="location" required
                                                        placeholder="Enter Village/town/city">
                                                    <span id="locationError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="mobile">Mobile <span class="text-danger">*</span></label>
                                                <div class="form-control-wrap">
                                                    <input type="number" class="form-control" id="mobile" required
                                                        placeholder="Enter Mobile" min="0" step="1">
                                                    <span id="mobileError" style="color:red; font-size:12px;"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mt-2">
                                                <label class="form-label mb-0" for="altMobile">Alternative Mobile
                                                    number</label>
                                                <div class="form-control-wrap">
                                                    <input type="number" class="form-control" id="altMobile"
                                                        placeholder="Enter Alternative Mobile number" min="0" step="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="modal-footer bg-light">
                                <button class="btn btn-primary" id="saveMember">Save</button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 (toast notifications) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 for searchable dropdown -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
<?php include 'footer.php' ?>

<!-- **************************** -->
<!--   ADD GROUP AJAX SCRIPT     -->
<!-- **************************** -->
<script>
    // Small SweetAlert2 helpers for consistent toasts/errors
    function showToast(icon, title) {
        if (typeof Swal === 'undefined') {
            console.log(title);
            return;
        }
        // Show centered success/modal with tick mark (auto-close)
        Swal.fire({
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 1400,
            timerProgressBar: true
        });
    }

    function showError(title, text) {
        if (typeof Swal === 'undefined') {
            alert((title || 'Error') + (text ? '\n' + text : ''));
            return;
        }
        Swal.fire({
            icon: 'error',
            title: title || 'Error',
            text: text || ''
        });
    }

    // Load routes from server with Select2
    function loadGroupRoutes(selectedRoute) {
        $.get('group_actions.php', {
            action: 'get_routes'
        }, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (err) {
                console.error('Failed to parse routes response:', resp);
                return;
            }

            var html = '<option value="">-- Select a Route --</option>';
            if (r.routes && r.routes.length > 0) {
                r.routes.forEach(function(route) {
                    var selected = (selectedRoute && route === selectedRoute) ? 'selected' : '';
                    html += '<option value="' + route[1] + '" ' + selected + '>' + route[0] + '</option>';
                });
            } else {
                html += '<option value="" disabled>No active routes available</option>';
            }
            $('#routeSelect').html(html);

            // Destroy existing Select2 instance if it exists
            if ($('#routeSelect').data('select2')) {
                $('#routeSelect').select2('destroy');
            }

            // Initialize Select2 (search enabled)
            $('#routeSelect').select2({
                placeholder: 'Search and select a route',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#addMemberModal'),
                language: {
                    noResults: function() {
                        return 'No routes found';
                    }
                }
            });

            // Set selected value if provided
            if (selectedRoute) {
                $('#routeSelect').val(selectedRoute).trigger('change');
            }
        }).fail(function(err) {
            console.error('Failed to load routes:', err);
        });
    }

    // Load routes when "Add Member" button is clicked
    $(document).on('click', '.add-member-btn', function() {
        loadGroupRoutes();
        var groupId = $(this).data('group-id');
        $('#memberGroupId').val(groupId);
    });

    $("#saveGroup").click(function() {

        let groupName = $("#groupName").val().trim();
        

        // Clear previous errors
        $("#groupError").text("");
        $("#groupName").css("border-color", "#d9dce7");

        // 🔥 VALIDATION: Only alphabets allowed
let alphaOnly = /^[A-Za-z ]+$/;

if (!alphaOnly.test(groupName)) {
    $("#groupName").css("border-color", "red");
    $("#groupError").text("Only alphabets are allowed");
    return; // stop saving
}


        var editId = $("#editGroupId").val();
        var action = editId ? 'update' : 'save';

        var payload = {
            action: action,
            group_name: groupName
        };
        if (editId) payload.group_id = editId;

        // AJAX call to PHP
        $.post("group_actions.php", payload, function(rawResponse) {

            let res = {};

            try {
                res = JSON.parse(rawResponse);
            } catch (e) {
                // show raw server response to help debugging
                $("#groupError").text("Unexpected server response. See console for details.");
                console.error('Raw server response for save/update group:', rawResponse);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server response',
                        html: '<pre style="text-align:left;white-space:pre-wrap;">' + $('<div>').text(rawResponse).html() + '</pre>'
                    });
                } else {
                    console.error(rawResponse);
                }
                return;
            }

            if (res.status === "error") {
                $("#groupName").css("border-color", "red");
                $("#groupError").text(res.message);
                return;
            }

            if (res.status === "success") {
                $("#addGroupModal").modal("hide");

                // Show SweetAlert2 toast (top-right) then reload shortly so table refreshes
                var toastTitle = (action === 'save') ? 'Group added successfully' : 'Group edited successfully';
                showToast('success', toastTitle);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }

        });

    });

    // Open Add Group button clicked -> reset modal state (no edit mode)
    $(document).on('click', '#openAddGroupBtn', function() {
        // clear edit state
        $('#editGroupId').val('');
        $('#groupName').val('');
        $('#groupError').text('');
        $('#groupName').css('border-color', '#d9dce7');
        $('#addGroupModal .modal-title').text('Add New Group');
        $('#saveGroup').text('Save Group');
    });

    // Edit group link -> open Add Group modal prefilled
    $(document).on('click', '.edit-group', function(e) {
        e.preventDefault();
        var gid = $(this).data('group-id') || '';
        var gname = $(this).data('group-name') || '';

        $('#editGroupId').val(gid);
        $('#groupName').val(gname);
        $('#groupError').text('');
        $('#groupName').css('border-color', '#d9dce7');
        $('#addGroupModal .modal-title').text('Edit Group');
        $('#saveGroup').text('Update');
        $('#addGroupModal').modal('show');

    });

    

    // Member edit via actions removed (only Edit Group is shown). Member add still available via Add Member button.

    // Add Member validation
    
 
   $("#saveMember").click(function (e) {
    e.preventDefault();

    // RESET ERRORS
    [
        "#routeSelect", "#doctorName", "#doctorQualification",
        "#clinicName", "#address", "#location", "#mobile", "#altMobile"
    ].forEach(id => $(id).css("border-color", "#d9dce7"));

    [
        "#routeError", "#doctorNameError", "#doctorQualificationError",
        "#clinicNameError", "#addressError", "#locationError", "#mobileError"
    ].forEach(id => $(id).text(""));

    let alphaOnly  = /^[A-Za-z ]+$/;
    let mobileOnly = /^[0-9]{10}$/;
    let hasError = false;

    let route     = $("#routeSelect").val().trim();
    let name      = $("#doctorName").val().trim();
    let qual      = $("#doctorQualification").val().trim();
    let clinic    = $("#clinicName").val().trim();
    let address   = $("#address").val().trim();
    let location  = $("#location").val().trim();
    let mobile    = $("#mobile").val().trim();
    let altMobile = $("#altMobile").val().trim();

    // ROUTE
    if (!route) {
        $("#routeSelect").css("border-color","red");
        $("#routeError").text("Route is required");
        hasError = true;
    }

    // NAME
    if (!name) {
        $("#doctorName").css("border-color","red");
        $("#doctorNameError").text("Name is required");
        hasError = true;
    } else if (!alphaOnly.test(name)) {
        $("#doctorName").css("border-color","red");
        $("#doctorNameError").text("Only alphabets allowed");
        hasError = true;
    }

    // QUALIFICATION
    if (!qual) {
        $("#doctorQualification").css("border-color","red");
        $("#doctorQualificationError").text("Qualification is required");
        hasError = true;
    } else if (!alphaOnly.test(qual)) {
        $("#doctorQualification").css("border-color","red");
        $("#doctorQualificationError").text("Only alphabets allowed");
        hasError = true;
    }

    // CLINIC NAME
    if (!clinic) {
        $("#clinicName").css("border-color","red");
        $("#clinicNameError").text("Clinic Name is required");
        hasError = true;
    } else if (!alphaOnly.test(clinic)) {
        $("#clinicName").css("border-color","red");
        $("#clinicNameError").text("Only alphabets allowed");
        hasError = true;
    }

    // ADDRESS
    if (!address) {
        $("#address").css("border-color","red");
        $("#addressError").text("Address is required");
        hasError = true;
    } else if (!alphaOnly.test(address)) {
        $("#address").css("border-color","red");
        $("#addressError").text("Only alphabets allowed");
        hasError = true;
    }

    // LOCATION
    if (!location) {
        $("#location").css("border-color","red");
        $("#locationError").text("Location is required");
        hasError = true;
    } else if (!alphaOnly.test(location)) {
        $("#location").css("border-color","red");
        $("#locationError").text("Only alphabets allowed");
        hasError = true;
    }

    // MOBILE
    if (!mobile) {
        $("#mobile").css("border-color","red");
        $("#mobileError").text("Mobile number is required");
        hasError = true;
    } else if (!mobileOnly.test(mobile)) {
        $("#mobile").css("border-color","red");
        $("#mobileError").text("Mobile must be 10 digits");
        hasError = true;
    }

    // ALT MOBILE
    if (altMobile && !mobileOnly.test(altMobile)) {
        $("#altMobile").css("border-color","red");
        $("#mobileError").text("Alternative mobile must be 10 digits");
        hasError = true;
    }

    if (altMobile && mobile === altMobile) {
        $("#altMobile").css("border-color","red");
        $("#mobile").css("border-color","red");
        $("#mobileError").text("Mobile & Alternative must be different");
        hasError = true;
    }

    if (hasError) return;

    let payload = {
        action: $("#memberId").val() ? "update_member" : "add_member",
        member_id: $("#memberId").val() || "",
        group_id: $("#memberGroupId").val(),
        route: route,
        name: name,
        qualification: qual,
        clinic_name: clinic,
        address: address,
        location: location,
        mobile: mobile,
        alt_mobile: altMobile
    };

    $.post("group_actions.php", payload, function (resp) {
        let r = {};
        try {
            r = JSON.parse(resp);
        } catch {
            alert("Invalid server response");
            return;
        }

        if (r.status === "error") {
            $("#mobileError").text(r.message);
            return;
        }

        $("#addMemberModal").modal("hide");
        showToast("success","Member saved successfully");
        setTimeout(() => location.reload(), 1200);
    });
});





    // when opening modal via button, populate group id and clear previous values
    $(document).on('click', '.add-member-btn', function() {
        var gid = $(this).data('group-id') || '';
        $('#memberGroupId').val(gid);

        // reset edit mode
        $('#memberId').val('');
        $('#addMemberModal .modal-title').text('Add member to Group');
        $('#saveMember').text('Save');

        // clear inputs and errors
        ['#routeSelect', '#doctorName', '#doctorQualification', '#clinicName', '#address', '#location', '#mobile', '#altMobile'].forEach(function(sel) {
            $(sel).val('');
            $(sel).css('border-color', '#d9dce7');
        });
        ['#routeError', '#doctorNameError', '#doctorQualificationError', '#clinicNameError', '#addressError', '#locationError', '#mobileError'].forEach(function(id) {
            $(id).text('');
        });
    });

    // Toggle group status (Active/Inactive) - update in-place without full reload
    $(document).on('click', '.toggleStatus', function(e) {
        e.preventDefault();
        var $el = $(this);
        var gid = $el.data('group-id') || $el.data('id');
        var status = $el.data('status') || '';
        if (!gid) return;

        // disable the link briefly to prevent double clicks
        $el.prop('disabled', true);

        // send to generic toggle handler, specifying table 'groups'
        $.post('toggle_status.php', {
            id: gid,
            status: status,
            table: 'groups'
        }, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (e) {
                console.error('toggle_status raw:', resp);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Unexpected response toggling status. See console.'
                    });
                }
                $el.prop('disabled', false);
                return;
            }

            if (r.status === 'success') {
                // update the row's badge and the toggle's data-status attribute
                var $row = $el.closest('tr');
                var $statusCell = $row.find('td').eq(2);
                var $badge = $statusCell.find('span.badge');

                if (r.new_status_code == 1) {
                    // Active
                    if ($badge.length) {
                        $badge.removeClass('badge-danger').addClass('badge-success').text('Active');
                    } else {
                        $statusCell.html('<span class="badge badge-success">Active</span>');
                    }
                    $el.data('status', 'Active');
                } else {
                    // Inactive
                    if ($badge.length) {
                        $badge.removeClass('badge-success').addClass('badge-danger').text('Inactive');
                    } else {
                        $statusCell.html('<span class="badge badge-danger">Inactive</span>');
                    }
                    $el.data('status', 'Inactive');
                }
                // Update Add Member button immediately (enable/disable)
                var $addBtn = $row.find('.add-member-btn');

                if (r.new_status_code == 1) {
                    // Enable Add Member
                    $addBtn.prop('disabled', false)
                        .removeClass('btn-outline-secondary')
                        .addClass('btn-outline-primary');
                } else {
                    // Disable Add Member
                    $addBtn.prop('disabled', true)
                        .removeClass('btn-outline-primary')
                        .addClass('btn-outline-secondary');
                }


                // optional: update a data-status-code attribute on the toggle link
                $el.data('status-code', r.new_status_code);

                // re-enable the link
                $el.prop('disabled', false);

                // show toast using SweetAlert2 if available
                showToast('success', 'Status updated successfully');
            } else {
                showError('Error', 'Failed to toggle status');
                $el.prop('disabled', false);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX toggle error', textStatus, errorThrown, jqXHR.responseText);
            showError('Request failed', textStatus + '\n' + (jqXHR.responseText || ''));
            $el.prop('disabled', false);
        });

    });

    
</script>