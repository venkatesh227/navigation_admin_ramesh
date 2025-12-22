<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
include_once('db/session-check.php');
include 'header.php';
?>

<div class="nk-content">
   <div class="container-fluid">
      <div class="nk-content-inner">
         <div class="nk-content-body">
            <div class="nk-block">

               <div class="card">
                  <div class="card-header d-flex justify-content-between align-items-center">
                     <h5 class="title">List Route</h5>
                     <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalForm">Add Route</button>
                  </div>

                  <div class="card-body">

                    

                     <!-- 🔹 TABLE -->
                     <table class="datatable-init nowrap nk-tb-list nk-tb-ulist table table-striped" data-auto-responsive="false">
                        <thead>
                           <tr class="nk-tb-item nk-tb-head">
                              <th class="nk-tb-col"><span class="sub-text">S.No</span></th>
                              <th class="nk-tb-col"><span class="sub-text">Route Name</span></th>
                              <th class="nk-tb-col tb-col-md"><span class="sub-text">Status</span></th>
                              <th class="nk-tb-col nk-tb-col-tools text-right"><span class="sub-text">Actions</span></th>
                           </tr>
                        </thead>

                        <tbody id="routeBody">
                        <?php
                        $rRes = mysqli_query($conn, "SELECT * FROM routes ORDER BY id DESC");
                        if($rRes && mysqli_num_rows($rRes) > 0){
                            $sno = 1;
                            while($r = mysqli_fetch_assoc($rRes)){
                                $statusText = $r['status']==1 ? 'Active' : 'Inactive';
                                $badgeClass = $r['status']==1 ? 'badge-success' : 'badge-danger';
                        ?>

                        <tr class="nk-tb-item">
                            <td class="nk-tb-col"><?php echo $sno++; ?></td>
                            <td class="nk-tb-col route-name"><?php echo htmlspecialchars($r['name']); ?></td>
                            <td class="nk-tb-col tb-col-md">
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td class="nk-tb-col nk-tb-col-tools text-right">
                                <div class="dropdown">
                                    <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-toggle="dropdown">
                                        <em class="icon ni ni-more-h"></em>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <ul class="link-list-opt no-bdr">
                                            <li><a href="#" class="editRoute"
                                                   data-id="<?php echo $r['id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($r['name']); ?>">
                                                <em class="icon ni ni-edit"></em><span>Edit</span></a>
                                            </li>

                                            <li><a href="#" class="toggleStatus"
                                                   data-id="<?php echo $r['id']; ?>"
                                                   data-status="<?php echo $statusText; ?>">
                                                <em class="icon ni ni-power"></em><span>Change Status</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <?php } } else { ?>
                        
                        <?php } ?>
                        </tbody>
                     </table>

                                        <!-- records info and pagination controls -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div id="pagination" class="btn-toolbar"></div>
                                        </div>
                                    </div>
                                </div><!-- .card-preview -->
                                <!-- =========================
                                     Modified table block ends
                                     ========================= -->

                            </div> <!-- nk-block -->
                        </div><!-- .components-preview -->
                    </div>
                </div><!-- row -->

            </div>
        </div>
    </div>
</div>

<!-- 🔹 Add Route Modal -->
<div class="modal fade" tabindex="-1" id="modalForm">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Route</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                    <em class="icon ni ni-cross"></em>
                </a>
            </div>
            <div class="modal-body">
                <form id="addRouteForm" class="form-validate is-alter">
                    <div class="form-group">
                        <label class="form-label" for="full-name">Route Name</label>
                        <div class="form-control-wrap">
                            <input type="text" class="form-control" id="full-name" name="route_name" required>
                        </div>
                        <small id="error" style="color:red;display:none;">Please fill this field.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <div class="form-group">
                    <button type="submit" class="btn btn-lg btn-primary" id="addRouteBtn">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 🔹 Edit Route Modal -->
<div class="modal fade" tabindex="-1" id="editModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Route</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                    <em class="icon ni ni-cross"></em>
                </a>
            </div>
            <div class="modal-body">
                <form id="editRouteForm">
                    <input type="hidden" id="edit-id">
                    <div class="form-group">
                        <label class="form-label" for="edit-name">Route Name</label>
                        <div class="form-control-wrap">
                            <input type="text" class="form-control" id="edit-name" name="route_name" required>
                        </div>
                        <small id="edit-error" style="color:red;display:none;">Please fill this field.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-lg btn-primary" id="updateRouteBtn">Update</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function(){
    let routesData = [];
    let filteredData = [];
    let currentPage = 1;
    const pageSize = 10;

    loadRoutes();

    function loadRoutes(){
        $.ajax({
            url: "get_routes.php",
            type: "GET",
            dataType: "json",
            success: function(data){
                routesData = data || [];
                filteredData = routesData;
                currentPage = 1;
                //renderPage(currentPage);
            },
            error: function(){
                routesData = [];
                filteredData = [];
                //renderPage(1);
            }
        });
    }

   
    
   

    // 🔹 Add Route (no SweetAlert for duplicate)
    $("#addRouteBtn").click(function(e){
        e.preventDefault();
        let routeName = $("#full-name").val().trim();
        $("#error").hide();

        if(routeName === ""){
            $("#error").show().text("Enter the Route.");
            return;
        }

        $.ajax({
            url: "add_route.php",
            type: "POST",
            data: { route_name: routeName },
            dataType: "json",
            success: function(response){
                if(response.status === "duplicate"){
                    $("#error").show().text("Route already exists.");
                } else if(response.status === "success"){
                    $("#modalForm").modal('hide');
                    $("#full-name").val('');
                    Swal.fire({
                        icon: 'success',
                        title: 'Route added',
                        text: 'Route added successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // loadRoutes();
                    window.location.reload(true);
                    });
                } else {
                    $("#error").show().text("Something went wrong.");
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong while adding the route.' });
                }
            },
            error: function(){
                $("#error").show().text("Something went wrong.");
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong while adding the route.' });
            }
        });
    });

    // 🔹 Edit Route (no SweetAlert for duplicate)
    $(document).on("click", ".editRoute", function(e){
        e.preventDefault();
        $("#edit-id").val($(this).data("id"));
        $("#edit-name").val($(this).data("name"));
        $("#edit-error").hide();
        $("#editModal").modal("show");
    });

    $("#updateRouteBtn").click(function(e){
        e.preventDefault();
        let id = $("#edit-id").val();
        let routeName = $("#edit-name").val().trim();
        $("#edit-error").hide();

        if(routeName === ""){
            $("#edit-error").show().text("Please fill this field.");
            return;
        }

        $.ajax({
            url: "edit_route.php",
            type: "POST",
            data: { id:id, route_name:routeName },
            dataType: "json",
            success: function(res){
                if(res.status === "duplicate"){
                    $("#edit-error").show().text("Route already exists.");
                } else if(res.status === "success"){
                    $("#editModal").modal("hide");
                    Swal.fire({
                        icon: 'success',
                        title: 'Route updated',
                        text: 'Route updated successfully.',
                        timer: 1400,
                        showConfirmButton: false
                    }).then(() => {
                        // loadRoutes();
                    window.location.reload(true);
                    });
                } else {
                    $("#edit-error").show().text("Error updating route.");
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error updating route.' });
                }
            },
            error: function(){
                $("#edit-error").show().text("Error updating route.");
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error updating route.' });
            }
        });
    });

    // 🔹 Toggle Status
    $(document).on("click", ".toggleStatus", function(e){
        e.preventDefault();
        let id = $(this).data("id");
        let status = $(this).data("status");

        $.ajax({
            url: "toggle_status.php",
            type: "POST",
            data: { id:id, status:status },
            dataType: "json",
            success: function(res){
                if(res.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Status updated',
                        text: 'Status updated successfully.',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        // loadRoutes();
                    window.location.reload(true);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Unable to update status.' });
                }
            },
            error: function(){
                Swal.fire({ icon: 'error', title: 'Error', text: 'Unable to update status.' });
            }
        });
    });

    $('#modalForm, #editModal').on('hidden.bs.modal', function(){
        $(this).find('input[type=text]').val('');
        $(this).find('small').hide();
    });

    function escapeHtml(text) {
        if(!text && text !== 0) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
});
</script>


<!-- Add this style somewhere (e.g., in your page head or a small style block) -->
<style>
    /* add spacing between route name text and card edge */
    td.route-name { padding-left: 12px; word-break: break-word; }
    /* ensure serial cell is narrow */
    table td:first-child { width: 60px; }
</style>

<?php include 'footer.php'; ?>
