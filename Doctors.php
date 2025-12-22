<?php include 'header.php';
include_once 'db/connection.php';

// Determine group context (if any) and load group name for heading
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$group_name = '';
if ($group_id) {
    $gSql = "SELECT name FROM `groups` WHERE id = " . intval($group_id) . " LIMIT 1";
    $gRes = mysqli_query($conn, $gSql);
    if ($gRes && mysqli_num_rows($gRes) > 0) {
        $gRow = mysqli_fetch_assoc($gRes);
        $group_name = htmlspecialchars($gRow['name']);
    }
}
?>
<!-- main header @e -->
<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="nk-block">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col">
                                    <h5 class="title">
                                        <?php if (!empty($group_name)) {
                                            echo $group_name. ' group members list';
                                        } else {
                                            echo 'Members';
                                        } ?>
                                    </h5>
                                </div>
                                <div class="col text-right">
                                    <button class="btn btn-outline-secondary mr-2" onclick="window.location.href='group.php'">
                                        <em class="icon ni ni-arrow-left"></em> Back
                                    </button>
                                </div>

                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="false">
                                    <thead>
                                        <tr class="nk-tb-item nk-tb-head">
                                            <th class="nk-tb-col"><span class="sub-text">S.NO</span></th>
                                            <th class="nk-tb-col tb-col-mb"><span class="sub-text">Group Name</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Route</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Name</span></th>
                                            <th class="nk-tb-col tb-col-lg"><span class="sub-text">Qualification</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Clinic Name</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Address</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Village/Town/City</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Mobile</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Alt Mobile</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Status</span></th>
                                            <th class="nk-tb-col tb-col-md"><span class="sub-text">Created At</span></th>
                                            <th class="nk-tb-col nk-tb-col-tools text-right"><span class="sub-text">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
                                        $sql = 'SELECT m.*, g.name as group_name FROM members m LEFT JOIN `groups` g ON g.id = m.group_id' . ($group_id ? ' WHERE m.group_id = ' . $group_id : '') . ' ORDER BY m.id ASC';
                                        $res = mysqli_query($conn, $sql);
                                        $s = 1;
                                        if ($res && mysqli_num_rows($res) > 0) {
                                            while ($row = mysqli_fetch_assoc($res)) {
                                                // Normalize route display: if route_id is numeric, lookup route name, otherwise use stored value
                                                $route_raw = $row['route_id'];
                                                $route = '';
                                                if (is_numeric($route_raw) && intval($route_raw) > 0) {
                                                    $rid = intval($route_raw);
                                                    $rSql = "SELECT name FROM routes WHERE id = $rid LIMIT 1";
                                                    $rRes = mysqli_query($conn, $rSql);
                                                    if ($rRes && mysqli_num_rows($rRes) > 0) {
                                                        $rRow = mysqli_fetch_assoc($rRes);
                                                        $route = htmlspecialchars($rRow['name']);
                                                    }
                                                } else {
                                                    $route = htmlspecialchars($route_raw);
                                                }
                                                $route = ($route === '' || $route === '0' || is_null($route)) ? '-' : $route;
                                                $name = htmlspecialchars($row['name']);
                                                $qual = htmlspecialchars($row['qualification']);
                                                $clinic = htmlspecialchars($row['clinic_name']);
                                                $address = htmlspecialchars($row['address']);
                                                $city = htmlspecialchars($row['village_town_city']);
                                                $mobile = htmlspecialchars($row['mobile_no']);
                                                $alt = htmlspecialchars($row['alternative_no']);
                                                $created = htmlspecialchars($row['created_at']);
                                                $group_name = htmlspecialchars($row['group_name']);
                                                $mid = intval($row['id']);
                                                $status = isset($row['status']) ? intval($row['status']) : 1;
                                                $statusBadge = $status === 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';

                                                echo "<tr class=\"nk-tb-item\" data-member-id=\"{$mid}\">\n";
                                                echo "<td class=\"nk-tb-col\">{$s}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-mb\">{$group_name}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$route}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$name}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-lg\">{$qual}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$clinic}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$address}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$city}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$mobile}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$alt}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$statusBadge}</td>\n";
                                                echo "<td class=\"nk-tb-col tb-col-md\">{$created}</td>\n";
                                                echo "<td class=\"nk-tb-col nk-tb-col-tools text-right\">\n";
                                                echo "<div class=\"dropdown\">\n";
                                                echo "<a href=\"#\" class=\"btn btn-sm btn-icon btn-trigger\" data-toggle=\"dropdown\">\n";
                                                echo "<em class=\"icon ni ni-more-h\"></em>\n";
                                                echo "</a>\n";
                                                echo "<div class=\"dropdown-menu dropdown-menu-right\">\n";
                                                echo "<ul class=\"link-list-opt no-bdr\">\n";
                                                echo "<li><a href=\"#\" class=\"view-member\" data-member-id=\"{$mid}\"><em class=\"icon ni ni-eye\"></em><span>View</span></a></li>\n";
                                                echo "<li><a href=\"#\" class=\"edit-member\" data-member-id=\"{$mid}\"><em class=\"icon ni ni-edit\"></em><span>Edit</span></a></li>\n";
                                                echo "<li><a href=\"#\" class=\"toggle-member-status\" data-member-id=\"{$mid}\" data-current-status=\"{$status}\"><em class=\"icon ni ni-power\"></em><span>Toggle Status</span></a></li>\n";
                                                echo "</ul>\n";
                                                echo "</div>\n";
                                                echo "</div>\n";
                                                echo "</td>\n";
                                                echo "</tr>\n";
                                                $s++;
                                            }
                                        } else { ?>
                                            <tr>
                                                <td colspan="12" style="text-align: center;">No members found for this group.</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
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
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="addEmployeeModal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Member Details</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                    <em class="icon ni ni-cross"></em>
                </a>
            </div>
            <div class="modal-body">
                <form id="memberViewForm">
                    <input type="hidden" id="modalMemberId" value="">
                    <input type="hidden" id="modalGroupId" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalRoute">Route <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <select class="form-control" id="modalRoute">
                                        <option value="">Select Route</option>
                                    </select>
                                    <span id="modalRouteError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalName">Name <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" id="modalName" placeholder="Enter Name">
                                    <span id="modalNameError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalQualification">Qualification <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" id="modalQualification" placeholder="Enter Qualification">
                                    <span id="modalQualificationError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalClinic">Clinic Name <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" id="modalClinic" placeholder="Enter Clinic Name">
                                    <span id="modalClinicError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalAddress">Address <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" id="modalAddress" placeholder="Enter Address">
                                    <span id="modalAddressError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalLocation">Village/town/city <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" id="modalLocation" placeholder="Enter Village/town/city">
                                    <span id="modalLocationError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalMobile">Mobile <span class="text-danger">*</span></label>
                                <div class="form-control-wrap">
                                    <input type="number" class="form-control" id="modalMobile" placeholder="Enter Mobile" min="0" step="1">
                                    <span id="modalMobileError" style="color:red; font-size:12px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-2">
                                <label class="form-label mb-0" for="modalAltMobile">Alternative Mobile number</label>
                                <div class="form-control-wrap">
                                    <input type="number" class="form-control" id="modalAltMobile" placeholder="Enter Alternative Mobile number" min="0" step="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveMemberModal">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 for toasts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select2 for searchable dropdown -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->

<style>
    .modal {
        z-index: 9999 !important;
    }

    .modal-backdrop {
        z-index: 9998 !important;
    }
</style>

<script>
    // Small SweetAlert2 helpers for consistent toasts/errors
    function showToast(icon, title) {
        if (typeof Swal === 'undefined') {
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

    // Load routes from database
    function loadRoutes(selectedRoute) {
        $.get('group_actions.php', { action: 'get_routes' }, function(resp) {
            var r = {};
            try { r = JSON.parse(resp); } catch (err) { return; }

            var html = '<option value="">Select Route</option>';
            if (r.routes && r.routes.length > 0) {
                r.routes.forEach(function(routeEntry) {
                    var name = '';
                    var id = '';

                    // support both shapes: [name,id] (array) or {name,id} (object) or just a string
                    if (Array.isArray(routeEntry)) {
                        name = routeEntry[0];
                        id = routeEntry[1];
                    } else if (routeEntry && typeof routeEntry === 'object') {
                        name = routeEntry.name;
                        id = routeEntry.id;
                    } else {
                        name = routeEntry;
                        id = routeEntry;
                    }

                    // Build option (use id as the value when available so we keep numeric ids)
                    var val = (typeof id !== 'undefined' && id !== null && id !== '') ? id : name;
                    html += '<option value="' + val + '">' + name + '</option>';
                });
            }

            $('#modalRoute').html(html);

            // Destroy existing Select2 instance if present
            if ($('#modalRoute').data('select2')) { $('#modalRoute').select2('destroy'); }

            // Initialize Select2 for modal route select (search enabled)
            // Use dropdownParent so the dropdown renders inside the Bootstrap modal
            $('#modalRoute').select2({
                placeholder: 'Search and select a route',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#addEmployeeModal'),
                language: { noResults: function() { return 'No routes found'; } }
            });

            // Ensure the selected value is displayed properly: support both id or name
            if (selectedRoute && selectedRoute !== '' && selectedRoute !== '0' && selectedRoute !== null) {
                if (!isNaN(selectedRoute)) {
                    // numeric id
                    $('#modalRoute').val(selectedRoute).trigger('change');
                } else {
                    // try to find option by visible text (route name)
                    var selText = ('' + selectedRoute).trim();
                    var $opt = $('#modalRoute option').filter(function() { return $(this).text().trim() === selText; }).first();
                    if ($opt.length) { $('#modalRoute').val($opt.val()).trigger('change'); }
                }
            }
        });
    }

    // View member -> open read-only modal
    $(document).on('click', '.view-member', function(e) {
        e.preventDefault();
        var mid = $(this).data('member-id');
        if (!mid) return;

        $.get('group_actions.php', {
            action: 'get_member',
            id: mid
        }, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (err) {
                // console.error('get_member raw', resp);
                if (typeof Swal !== 'undefined') {
                    showError('Server Error', 'Unexpected server response');
                }
                return;
            }
            if (r.status !== 'success') {
                showError('Error', r.message || 'Member not found');
                return;
            }
            var d = r.data;
            $('#modalMemberId').val(d.id);
            $('#modalGroupId').val(d.group_id);
            $('#modalRoute').prop('disabled', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalName').val(d.name).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalQualification').val(d.qualification).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalClinic').val(d.clinic_name).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalAddress').val(d.address).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalLocation').val(d.village_town_city).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalMobile').val(d.mobile_no).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalAltMobile').val(d.alternative_no).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');

            // Load routes and select current one
            loadRoutes(d.route_id);

            // clear errors and hide save button
            ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(s) {
                $(s).text('');
            });
            $('#saveMemberModal').hide();
            $('#addEmployeeModal .modal-title').text('View Member');
            $('#addEmployeeModal').modal('show');
        });
    });

    // Edit member -> fetch and open editable modal
    $(document).on('click', '.edit-member', function(e) {
        e.preventDefault();
        var mid = $(this).data('member-id');
        if (!mid) return;

        $.get('group_actions.php', {
            action: 'get_member',
            id: mid
        }, function(resp) {
            // console.log('Raw response:', resp);
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (err) {
                // console.error('JSON Parse Error:', err);
                // console.error('Raw response was:', resp);
                showError('JSON Error', 'Invalid server response. Response: ' + resp.substring(0, 100));
                return;
            }
            if (r.status !== 'success') {
                showError('Error', r.message || 'Member not found');
                return;
            }
            var d = r.data;

            // Set hidden fields
            $('#modalMemberId').val(d.id);
            $('#modalGroupId').val(d.group_id);

            // Load routes dropdown and select current route
            loadRoutes(d.route_id);
            $('#modalRoute').prop('disabled', false).css('background-color', '#fff');

            // Set all input fields and make them editable
            $('#modalName').val(d.name).removeAttr('readonly').css('background-color', '#fff');
            $('#modalQualification').val(d.qualification).removeAttr('readonly').css('background-color', '#fff');
            $('#modalClinic').val(d.clinic_name).removeAttr('readonly').css('background-color', '#fff');
            $('#modalAddress').val(d.address).removeAttr('readonly').css('background-color', '#fff');
            $('#modalLocation').val(d.village_town_city).removeAttr('readonly').css('background-color', '#fff');
            $('#modalMobile').val(d.mobile_no).removeAttr('readonly').css('background-color', '#fff');
            $('#modalAltMobile').val(d.alternative_no).removeAttr('readonly').css('background-color', '#fff');

            // clear previous errors and borders
            ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(s) {
                $(s).text('');
            });
            ['#modalRoute', '#modalName', '#modalQualification', '#modalClinic', '#modalAddress', '#modalLocation', '#modalMobile'].forEach(function(sel) {
                $(sel).css('border-color', '#d9dce7');
            });

            // Show Save button and set title
            $('#saveMemberModal').show().text('Update');
            $('#addEmployeeModal .modal-title').text('Edit Member');
            $('#addEmployeeModal').modal('show');
        }).fail(function(err) {
            showError('Error', 'Failed to load member data');
        });
    });

    // Save member modal (update_member or add_member)
    $(document).on('click', '#saveMemberModal', function(e) {
        e.preventDefault();
        var memberId = $('#modalMemberId').val();
        var $saveBtn = $('#saveMemberModal');

        var payload = {
            action: memberId ? 'update_member' : 'add_member',
            member_id: memberId,
            group_id: $('#modalGroupId').val(),
            route: $('#modalRoute').val(),
            name: $('#modalName').val().trim(),
            qualification: $('#modalQualification').val().trim(),
            clinic_name: $('#modalClinic').val().trim(),
            address: $('#modalAddress').val().trim(),
            location: $('#modalLocation').val().trim(),
            mobile: $('#modalMobile').val().trim(),
            alt_mobile: $('#modalAltMobile').val().trim()
        };

        // client-side validation
        var hasError = false;
        ['#modalRoute', '#modalName', '#modalQualification', '#modalClinic', '#modalAddress', '#modalLocation', '#modalMobile'].forEach(function(sel) {
            $(sel).css('border-color', '#d9dce7');
        });
        ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(id) {
            $(id).text('');
        });

        if (!payload.route) {
            $('#modalRoute').css('border-color', 'red');
            $('#modalRouteError').text('This field is required');
            hasError = true;
        }
        if (!payload.name) {
            $('#modalName').css('border-color', 'red');
            $('#modalNameError').text('This field is required');
            hasError = true;
        }
        if (!payload.qualification) {
            $('#modalQualification').css('border-color', 'red');
            $('#modalQualificationError').text('This field is required');
            hasError = true;
        }
        if (!payload.clinic_name) {
            $('#modalClinic').css('border-color', 'red');
            $('#modalClinicError').text('This field is required');
            hasError = true;
        }
        if (!payload.address) {
            $('#modalAddress').css('border-color', 'red');
            $('#modalAddressError').text('This field is required');
            hasError = true;
        }
        if (!payload.location) {
            $('#modalLocation').css('border-color', 'red');
            $('#modalLocationError').text('This field is required');
            hasError = true;
        }
        if (!payload.mobile) {
            $('#modalMobile').css('border-color', 'red');
            $('#modalMobileError').text('This field is required');
            hasError = true;
        }
        if (hasError) {
            return;
        }

        // Show loading state
        $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Saving...');

        $.post('group_actions.php', payload, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (e) {
                $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
                showError('JSON Error', 'Invalid server response. Contact admin.');
                return;
            }

            if (r.status === 'error') {
                $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
                if (r.field) {
                    var fieldMap = {
                        mobile: '#modalMobile',
                        mobile_no: '#modalMobile',
                        clinic_name: '#modalClinic'
                    };
                    var errorMap = {
                        mobile: '#modalMobileError',
                        mobile_no: '#modalMobileError',
                        clinic_name: '#modalClinicError'
                    };
                    if (fieldMap[r.field]) {
                        $(fieldMap[r.field]).css('border-color', 'red');
                        $(errorMap[r.field]).text(r.message);
                    }
                } else {
                    showError('Error', r.message || 'Unable to save');
                }
                return;
            }

            if (r.status === 'success' && r.data) {
                var toastMsg = memberId ? 'Member updated successfully' : 'Member added successfully';

                // Update row in-place without reload
                if (memberId) {
                    var $row = $('tr[data-member-id="' + memberId + '"]');
                    if ($row.length) {
                        // Update cells: Group=1, Route=2, Name=3, Qual=4, Clinic=5, Address=6, Village=7, Mobile=8, AltMobile=9
                        $row.find('td').eq(2).text(payload.route);
                        $row.find('td').eq(3).text(payload.name);
                        $row.find('td').eq(4).text(payload.qualification);
                        $row.find('td').eq(5).text(payload.clinic_name);
                        $row.find('td').eq(6).text(payload.address);
                        $row.find('td').eq(7).text(payload.location);
                        $row.find('td').eq(8).text(payload.mobile);
                        $row.find('td').eq(9).text(payload.alt_mobile);
                    }
                    $('#addEmployeeModal').modal('hide');
                    $saveBtn.prop('disabled', false).text('Update');
                    showToast('success', toastMsg);
                } else {
                    // For new member, hide modal and reload after a short delay
                    $('#addEmployeeModal').modal('hide');
                    showToast('success', toastMsg);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            }
        }).fail(function(err) {
            $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
            showError('Error', 'Failed to save member');
        });
    });

    // View member -> open read-only modal
    $(document).on('click', '.view-member', function(e) {
        e.preventDefault();
        var mid = $(this).data('member-id');
        if (!mid) return;

        $.get('group_actions.php', {
            action: 'get_member',
            id: mid
        }, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (err) {
                // console.error('get_member raw', resp);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Unexpected server response'
                    });
                }
                return;
            }
            if (r.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: r.message || 'Member not found'
                });
                return;
            }
            var d = r.data;
            $('#modalMemberId').val(d.id);
            $('#modalGroupId').val(d.group_id);
            $('#modalRoute').prop('disabled', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalName').val(d.name).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalQualification').val(d.qualification).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalClinic').val(d.clinic_name).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalAddress').val(d.address).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalLocation').val(d.village_town_city).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalMobile').val(d.mobile_no).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');
            $('#modalAltMobile').val(d.alternative_no).prop('readonly', true).css('background-color', '#f5f5f5').css('cursor', 'not-allowed');

            // Load routes and select current one
            loadRoutes(d.route_id);

            // clear errors and hide save button
            ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(s) {
                $(s).text('');
            });
            $('#saveMemberModal').hide();
            $('#addEmployeeModal .modal-title').text('View Member');
            $('#addEmployeeModal').modal('show');
        });
    });

    // Edit member -> fetch and open editable modal
    $(document).on('click', '.edit-member', function(e) {
        e.preventDefault();
        var mid = $(this).data('member-id');
        if (!mid) return;

        $.get('group_actions.php', {
            action: 'get_member',
            id: mid
        }, function(resp) {
            // console.log('Raw response:', resp);
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (err) {
                // console.error('JSON Parse Error:', err);
                // console.error('Raw response was:', resp);
                Swal.fire({
                    icon: 'error',
                    title: 'JSON Error',
                    text: 'Invalid server response. Response: ' + resp.substring(0, 100)
                });
                return;
            }
            if (r.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: r.message || 'Member not found'
                });
                return;
            }
            var d = r.data;

            // Set hidden fields
            $('#modalMemberId').val(d.id);
            $('#modalGroupId').val(d.group_id);

            // Load routes dropdown and select current route
            loadRoutes(d.route_id);
            $('#modalRoute').prop('disabled', false).css('background-color', '#fff');

            // Set all input fields and make them editable
            $('#modalName').val(d.name).removeAttr('readonly').css('background-color', '#fff');
            $('#modalQualification').val(d.qualification).removeAttr('readonly').css('background-color', '#fff');
            $('#modalClinic').val(d.clinic_name).removeAttr('readonly').css('background-color', '#fff');
            $('#modalAddress').val(d.address).removeAttr('readonly').css('background-color', '#fff');
            $('#modalLocation').val(d.village_town_city).removeAttr('readonly').css('background-color', '#fff');
            $('#modalMobile').val(d.mobile_no).removeAttr('readonly').css('background-color', '#fff');
            $('#modalAltMobile').val(d.alternative_no).removeAttr('readonly').css('background-color', '#fff');

            // clear previous errors and borders
            ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(s) {
                $(s).text('');
            });
            ['#modalRoute', '#modalName', '#modalQualification', '#modalClinic', '#modalAddress', '#modalLocation', '#modalMobile'].forEach(function(sel) {
                $(sel).css('border-color', '#d9dce7');
            });

            // Show Save button and set title
            $('#saveMemberModal').show().text('Update');
            $('#addEmployeeModal .modal-title').text('Edit Member');
            $('#addEmployeeModal').modal('show');
        }).fail(function(err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load member data'
            });
        });
    });

    // Save member modal (update_member or add_member)
    $(document).on('click', '#saveMemberModal', function(e) {
        e.preventDefault();
        var memberId = $('#modalMemberId').val();
        var $saveBtn = $('#saveMemberModal');

        var payload = {
            action: memberId ? 'update_member' : 'add_member',
            member_id: memberId,
            group_id: $('#modalGroupId').val(),
            route: $('#modalRoute').val().trim(),
            name: $('#modalName').val().trim(),
            qualification: $('#modalQualification').val().trim(),
            clinic_name: $('#modalClinic').val().trim(),
            address: $('#modalAddress').val().trim(),
            location: $('#modalLocation').val().trim(),
            mobile: $('#modalMobile').val().trim(),
            alt_mobile: $('#modalAltMobile').val().trim()
        };

        // client-side validation
        var hasError = false;
        ['#modalRoute', '#modalName', '#modalQualification', '#modalClinic', '#modalAddress', '#modalLocation', '#modalMobile'].forEach(function(sel) {
            $(sel).css('border-color', '#d9dce7');
        });
        ['#modalRouteError', '#modalNameError', '#modalQualificationError', '#modalClinicError', '#modalAddressError', '#modalLocationError', '#modalMobileError'].forEach(function(id) {
            $(id).text('');
        });

        if (!payload.route) {
            $('#modalRoute').css('border-color', 'red');
            $('#modalRouteError').text('This field is required');
            hasError = true;
        }
        if (!payload.name) {
            $('#modalName').css('border-color', 'red');
            $('#modalNameError').text('This field is required');
            hasError = true;
        }
        if (!payload.qualification) {
            $('#modalQualification').css('border-color', 'red');
            $('#modalQualificationError').text('This field is required');
            hasError = true;
        }
        if (!payload.clinic_name) {
            $('#modalClinic').css('border-color', 'red');
            $('#modalClinicError').text('This field is required');
            hasError = true;
        }
        if (!payload.address) {
            $('#modalAddress').css('border-color', 'red');
            $('#modalAddressError').text('This field is required');
            hasError = true;
        }
        if (!payload.location) {
            $('#modalLocation').css('border-color', 'red');
            $('#modalLocationError').text('This field is required');
            hasError = true;
        }
        if (!payload.mobile) {
            $('#modalMobile').css('border-color', 'red');
            $('#modalMobileError').text('This field is required');
            hasError = true;
        }
        if (hasError) {
            return;
        }

        // Show loading state
        $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Saving...');

        $.post('group_actions.php', payload, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (e) {
                $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
                Swal.fire({
                    icon: 'error',
                    title: 'JSON Error',
                    text: 'Invalid server response. Contact admin.'
                });
                return;
            }

            if (r.status === 'error') {
                $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
                if (r.field) {
                    var fieldMap = {
                        mobile: '#modalMobile',
                        mobile_no: '#modalMobile',
                        clinic_name: '#modalClinic'
                    };
                    var errorMap = {
                        mobile: '#modalMobileError',
                        mobile_no: '#modalMobileError',
                        clinic_name: '#modalClinicError'
                    };
                    if (fieldMap[r.field]) {
                        $(fieldMap[r.field]).css('border-color', 'red');
                        $(errorMap[r.field]).text(r.message);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: r.message || 'Unable to save'
                    });
                }
                return;
            }

            if (r.status === 'success' && r.data) {
                var toastMsg = memberId ? 'Member updated successfully' : 'Member added successfully';

                // Update row in-place without reload
                if (memberId) {
                    var $row = $('tr[data-member-id="' + memberId + '"]');
                    if ($row.length) {
                        // Update cells: Group=1, Route=2, Name=3, Qual=4, Clinic=5, Address=6, Village=7, Mobile=8, AltMobile=9
                        $row.find('td').eq(2).text(payload.route);
                        $row.find('td').eq(3).text(payload.name);
                        $row.find('td').eq(4).text(payload.qualification);
                        $row.find('td').eq(5).text(payload.clinic_name);
                        $row.find('td').eq(6).text(payload.address);
                        $row.find('td').eq(7).text(payload.location);
                        $row.find('td').eq(8).text(payload.mobile);
                        $row.find('td').eq(9).text(payload.alt_mobile);
                    }
                    $('#addEmployeeModal').modal('hide');
                    $saveBtn.prop('disabled', false).text('Update');
                    showToast('success', toastMsg);
                } else {
                    // For new member, hide modal and reload after a short delay
                    $('#addEmployeeModal').modal('hide');
                    showToast('success', toastMsg);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            }
        }).fail(function(err) {
            $saveBtn.prop('disabled', false).text(memberId ? 'Update' : 'Save');
            showError('Error', 'Failed to save member');
        });
    });

    // Toggle member status (no confirmation - direct toggle)
    $(document).on('click', '.toggle-member-status', function(e) {
        e.preventDefault();
        var $el = $(this);
        var mid = $el.data('member-id');
        if (!mid) return;

        var $row = $('tr[data-member-id="' + mid + '"]');
        if (!$row.length) {
            showError('Error', 'Could not find member row');
            return;
        }
        $el.prop('disabled', true);

        $.post('group_actions.php', {
            action: 'toggle_member_status',
            id: mid
        }, function(resp) {
            var r = {};
            try {
                r = JSON.parse(resp);
            } catch (e) {
                showError('Error', 'Failed to update status');
                $el.prop('disabled', false);
                return;
            }

            if (r.status === 'success') {
                var $statusCell = $row.find('td').eq(10);
                if (r.new_status_code == 1) {
                    $statusCell.html('<span class="badge badge-success">Active</span>');
                    $el.data('current-status', 1);
                } else {
                    $statusCell.html('<span class="badge badge-danger">Inactive</span>');
                    $el.data('current-status', 0);
                }
                $el.prop('disabled', false);
                showToast('success', 'Status updated successfully');
            } else {
                $el.prop('disabled', false);
                showError('Error', r.message || 'Unable to update status');
            }
        }).fail(function() {
            $el.prop('disabled', false);
            showError('Error', 'Request failed');
        });
    });

    // (No inline toggle button) - toggle is available inside the dropdown after Edit
</script>

<?php include 'footer.php' ?>