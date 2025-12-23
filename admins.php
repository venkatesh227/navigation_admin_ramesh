<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
include 'header.php';
?>

<div class="nk-content ">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                <div class="components-preview mx-auto">

                    <div class="nk-block nk-block-lg">
                        <div class="nk-block-head">
                            <div class="nk-block-head-content">
                                <h4 class="nk-block-title">List of Sales Managers
                                    <span class="float-right">
                                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalForm">Add Sales Manager</button>
                                    </span>
                                </h4>
                            </div>
                        </div>

                        <div class="card card-preview">
                            <div class="card-inner">
                                <table class="datatable-init nk-tb-list nk-tb-ulist">
                                    <thead>
                                        <tr class="nk-tb-item nk-tb-head">
                                            <th class="nk-tb-col"><span class="sub-text">Name</span></th>
                                            <th class="nk-tb-col"><span class="sub-text">Branch</span></th>
                                            <th class="nk-tb-col"><span class="sub-text">Phone No</span></th>
                                            <th class="nk-tb-col"><span class="sub-text">Designation</span></th>
                                            <th class="nk-tb-col"><span class="sub-text">Created At</span></th>
                                            <th class="nk-tb-col"><span class="sub-text">Status</span></th>
                                            <th class="nk-tb-col nk-tb-col-tools text-right"><span class="sub-text">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="admins-tbody">
                                        <!-- dynamic rows -->
                                    </tbody>
                                </table>

                            </div>
                        </div>

                    </div><!-- nk-block -->
                </div><!-- .components-preview -->
            </div>
        </div>
    </div>
</div>

<!-- ===================
 ADD ADMIN MODAL
==================== -->
<div class="modal fade" tabindex="-1" id="modalForm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Sales Manager</h5>
                <a href="#" class="close" data-dismiss="modal">
                    <em class="icon ni ni-cross"></em>
                </a>
            </div>

            <div class="modal-body">
                <form id="adminForm" novalidate>

                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="full-name" class="form-control">
                        <small id="err-full-name" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" id="designation" class="form-control">
                        <small id="err-designation" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select id="Branch" class="form-control">
                            <option value="">Select Branch</option>
                            <?php
                            // Show only Active branches
                            $branches = mysqli_query($conn, "SELECT * FROM branches WHERE status = 1 ORDER BY name ASC");
                            while ($b = mysqli_fetch_assoc($branches)) {
                                echo '<option value="' . $b['id'] . '">' . $b['name'] . '</option>';
                            }
                            ?>
                        </select>

                        <small id="err-Branch" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone No <span class="text-danger">*</span></label>
                        <input type="text" id="phone-no" class="form-control">
                        <small id="err-phone-no" class="text-danger d-none"></small>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light">
                <button id="btnAddAdmin" type="button" class="btn btn-primary btn-lg">Submit</button>
            </div>

        </div>
    </div>
</div>

<!-- ===================
 EDIT ADMIN MODAL
==================== -->
<div class="modal fade" tabindex="-1" id="editModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Sales Manager</h5>
                <a href="#" class="close" data-dismiss="modal">
                    <em class="icon ni ni-cross"></em>
                </a>
            </div>

            <div class="modal-body">
                <form id="editAdminForm" novalidate>

                    <input type="hidden" id="edit-admin-id">
                    <input type="hidden" id="edit-user-id">

                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" id="edit-full-name" class="form-control">
                        <small id="err-edit-full-name" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Designation *</label>
                        <input type="text" id="edit-designation" class="form-control">
                        <small id="err-edit-designation" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label>Branch *</label>
                        <select id="branch_id" name="branch_id" class="form-control">
                            <option value="">Select Branch</option>

                            <small id="branch_error" class="text-danger"></small>

                            <?php
                            $branches2 = mysqli_query($conn, "SELECT * FROM branches ORDER BY name ASC");
                            while ($b2 = mysqli_fetch_assoc($branches2)) {
                                echo '<option value="' . $b2['id'] . '">' . $b2['name'] . '</option>';
                            }
                            ?>
                        </select>
                        <small id="err-edit-branch" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="text" id="edit-phone-no" class="form-control">
                        <small id="err-edit-phone-no" class="text-danger d-none"></small>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light">
                <button id="btnEditAdmin" class="btn btn-primary btn-lg">Save</button>
            </div>

        </div>
    </div>
</div>


<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>

    function isOnlyLetters(str) {
    return /^[A-Za-z ]+$/.test(str);
}

    /* ------------------------
  FRONTEND HELPERS
---------------------------*/
    function showError(id, msg) {
        let el = document.querySelector(id);
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function clearError(id) {
        let el = document.querySelector(id);
        el.textContent = "";
        el.classList.add('d-none');
    }

    function isValidPhone(p) {
        return /^\d{10}$/.test(p);
    }


    /* ------------------------
      ADD ADMIN 
    ---------------------------*/
    document.getElementById('btnAddAdmin').addEventListener('click', function() {

        clearError('#err-full-name');
        clearError('#err-designation');
        clearError('#err-Branch');
        clearError('#err-phone-no');

        let name = document.getElementById('full-name').value.trim();
        let desig = document.getElementById('designation').value.trim();
        let branch = document.getElementById('Branch').value.trim();
        let phone = document.getElementById('phone-no').value.trim();

        let hasError = false;

   if (name === "") {
    showError('#err-full-name', 'Full name required');
    hasError = true;
} else if (!isOnlyLetters(name)) {
    showError('#err-full-name', 'Only alphabets are allowed');
    hasError = true;
}

if (desig === "") {
    showError('#err-designation', 'Designation required');
    hasError = true;
} else if (!isOnlyLetters(desig)) {
    showError('#err-designation', 'Only alphabets are allowed');
    hasError = true;
}


        if (branch === "") {
            showError('#err-Branch', 'Branch required');
            hasError = true;
        }
        if (!isValidPhone(phone)) {
            showError('#err-phone-no', 'Enter valid 10 digit phone');
            hasError = true;
        }

        if (hasError) return;

        let fd = new FormData();
        fd.append("full_name", name);
        fd.append("designation", desig);
        fd.append("phone_no", phone);
        fd.append("branch_id", branch);

        fetch("add-admin.php", {
                method: "POST",
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === "success") {

                    $('#modalForm').modal('hide');
                    Swal.fire("Added!", "Sales Manager created successfully", "success")
                        .then(() => location.reload());
                } else {

                    // Clear existing errors
                    clearError('#err-Branch');
                    clearError('#err-phone-no');

                    // Branch already has a manager
                    if (res.message.includes("Sales Manager")) {
                        showError('#err-Branch', res.message);
                    }
                    // Phone related errors
                    else if (res.message.includes("Phone")) {
                        showError('#err-phone-no', res.message);
                    }
                    // Invalid branch
                    // else if (res.message.includes("branch")) {
                    //     showError('#err-Branch', res.message);
                    // }
                    // Fallback
                    else {
                        showError('#err-Branch', res.message);
                    }
                }
            });

    });



    /* ------------------------
      FETCH ADMINS
    ---------------------------*/
    function loadAdmins() {
        fetch("get_admins.php")
            .then(r => r.json())
            .then(list => {
                let tbody = document.getElementById("admins-tbody");
                tbody.innerHTML = "";

                list.forEach(a => {
                    let statusClass = a.status === "Active" ? "badge-success" : "badge-danger";
                    let row = `
<tr class="nk-tb-item">

    <td class="nk-tb-col">
        <div class="user-card">
            <div class="user-avatar bg-dim-primary d-none d-sm-flex">
                <span>${a.name.slice(0,2).toUpperCase()}</span>
            </div>
            <div class="user-info">
                <span class="tb-lead">${a.name}</span>
            </div>
        </div>
    </td>

    <td class="nk-tb-col tb-col-mb">${a.branch_name || '-'}</td>
    <td class="nk-tb-col tb-col-md">${a.phone_no}</td>
    <td class="nk-tb-col tb-col-md">${a.designation}</td>
    <td class="nk-tb-col tb-col-lg">${a.created_at}</td>

    <td class="nk-tb-col tb-col-md">
        <span class="badge ${statusClass}">${a.status}</span>
    </td>

    <td class="nk-tb-col nk-tb-col-tools text-right">
        <div class="dropdown">
            <a href="#" class="dropdown-toggle btn btn-sm btn-icon btn-trigger" data-toggle="dropdown">
                <em class="icon ni ni-more-h"></em>
            </a>
             <div class="dropdown-menu dropdown-menu-right">        
                 <ul class="link-list-opt no-bdr">

    <!-- EDIT -->
    <li>
        <a href="#" class="btn-open-edit"
            data-admin-id="${a.admin_id}"
            data-user-id="${a.user_id}"
            data-name="${a.name}"
            data-designation="${a.designation}"
            data-phone="${a.phone_no}"
            data-branch="${a.branch_id}">
            <em class="icon ni ni-edit"></em>
            <span>Edit</span>
        </a>
    </li>

    <!-- CHANGE STATUS -->
    <li>
        <a href="#" class="btn-toggle-status"
            data-user-id="${a.user_id}"
            data-current="${a.status_int}">
            <em class="icon ni ni-power"></em>
            <span>${a.status === "Active" ? "Change Status" : "Change Status"}</span>
        </a>
    </li>

</ul>


            </div>
        </div>
    </td>

</tr>
`;



                    tbody.innerHTML += row;
                });

                bindRowActions();
            });
    }

    loadAdmins();



    /* ------------------------
      ROW ACTION HANDLERS
    ---------------------------*/
    function bindRowActions() {

        // Edit buttons
        document.querySelectorAll('.btn-open-edit').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();

                document.getElementById('edit-admin-id').value = this.dataset.adminId;
                document.getElementById('edit-user-id').value = this.dataset.userId;
                document.getElementById('edit-full-name').value = this.dataset.name;
                document.getElementById('edit-designation').value = this.dataset.designation;
                document.getElementById('edit-phone-no').value = this.dataset.phone;

                // Branch select
                document.getElementById('branch_id').value = this.dataset.branch;

                $('#editModal').modal('show');
            }
        });


        // Toggle status
        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();

                let uid = this.dataset.userId;
                let current = this.dataset.current;

                let fd = new FormData();
                fd.append("user_id", uid);
                fd.append("current_status", current);

                fetch("toggle_admin_status.php", {
                        method: "POST",
                        body: fd
                    })
                    .then(r => r.json())
                    .then(res => {
                        Swal.fire("Updated!", "Status changed", "success")
                            .then(() => location.reload());
                    });
            }
        });
    }



    /* ------------------------
      EDIT ADMIN SAVE
    ---------------------------*/
    document.getElementById('btnEditAdmin').onclick = function() {

        clearError('#err-edit-full-name');
        clearError('#err-edit-designation');
        clearError('#err-edit-branch');
        clearError('#err-edit-phone-no');

        let id = document.getElementById('edit-admin-id').value;
        let user_id = document.getElementById('edit-user-id').value;
        let name = document.getElementById('edit-full-name').value.trim();
        let desig = document.getElementById('edit-designation').value.trim();
        let phone = document.getElementById('edit-phone-no').value.trim();
        let branch = document.getElementById('branch_id').value.trim();

        let hasError = false;

        if (name === "") {
    showError('#err-edit-full-name', 'Required');
    hasError = true;
} else if (!isOnlyLetters(name)) {
    showError('#err-edit-full-name', 'Only alphabets are allowed');
    hasError = true;
}

if (desig === "") {
    showError('#err-edit-designation', 'Required');
    hasError = true;
} else if (!isOnlyLetters(desig)) {
    showError('#err-edit-designation', 'Only alphabets are allowed');
    hasError = true;
}

        if (branch === "") {
            showError('#err-edit-branch', 'Required');
            hasError = true;
        }
        if (!isValidPhone(phone)) {
            showError('#err-edit-phone-no', 'Invalid');
            hasError = true;
        }

        if (hasError) return;

        let fd = new FormData();
        fd.append("admin_id", id);
        fd.append("user_id", user_id);
        fd.append("full_name", name);
        fd.append("designation", desig);
        fd.append("phone_no", phone);
        fd.append("branch_id", branch);

        fetch("edit-admin.php", {
                method: "POST",
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === "success") {
                    $('#editModal').modal('hide');
                    Swal.fire("Updated!", "Sales Manager updated", "success")
                        .then(() => location.reload());
                } else {
                   if (res.message.includes("Branch")) {
    showError('#err-edit-branch', res.message);
}
else if (res.message.includes("Phone")) {
    showError('#err-edit-phone-no', res.message);
}
else {
    showError('#err-edit-phone-no', res.message);
}

                }
            });
    }
</script>

<?php include 'footer.php'; ?>