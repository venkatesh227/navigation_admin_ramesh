<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php'); // Assuming auth check is here or in session-check
include_once('db/session-check.php');

$success_msg = '';
$error_msg = '';

// -------------------- ACTION: ADD --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $created_by = $_SESSION['user_id'] ?? 1;

    if ($title && $message) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $message, $created_by);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement posted successfully!";
            header("Location: admin_announcements.php");
            exit;
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_msg = "Title and Message are required.";
    }
}

// -------------------- ACTION: DELETE --------------------
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("UPDATE announcements SET is_active = 0 WHERE id = $del_id"); // Soft delete
    $_SESSION['success'] = "Announcement removed.";
    header("Location: admin_announcements.php");
    exit;
}

// -------------------- FETCH LIST --------------------
$result = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC");
$announcements = [];
while ($row = $result->fetch_assoc()) $announcements[] = $row;


include 'header.php';
?>

<div class="nk-content">
    <div class="container-fluid">
        <div class="nk-content-inner">
            <div class="nk-content-body">
                
                <div class="nk-block-head nk-block-head-sm">
                    <div class="nk-block-between">
                        <div class="nk-block-head-content">
                            <h3 class="nk-block-title page-title">Announcements</h3>
                            <div class="nk-block-des text-soft">
                                <p>Broadcast messages to all field employees.</p>
                            </div>
                        </div>
                        <div class="nk-block-head-content">
                             <button class="btn btn-primary" data-toggle="modal" data-target="#addModal">
                                 <em class="icon ni ni-plus"></em><span>Post New</span>
                             </button>
                        </div>
                    </div>
                </div>

                <!-- NOTIFICATIONS -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-icon">
                        <em class="icon ni ni-check-circle"></em> 
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-icon">
                        <em class="icon ni ni-cross-circle"></em> <?= $error_msg; ?>
                    </div>
                <?php endif; ?>

                <!-- TABLE -->
                <div class="nk-block">
                    <div class="card card-bordered card-stretch">
                        <div class="card-inner-group">
                            <div class="card-inner p-0">
                                <div class="nk-tb-list nk-tb-ulist">
                                    <div class="nk-tb-item nk-tb-head">
                                        <div class="nk-tb-col"><span class="sub-text">Date</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Title</span></div>
                                        <div class="nk-tb-col"><span class="sub-text">Message</span></div>
                                        <div class="nk-tb-col nk-tb-col-tools text-right"></div>
                                    </div>

                                    <?php if (empty($announcements)): ?>
                                        <div class="p-4 text-center text-muted">No active announcements.</div>
                                    <?php else: ?>
                                        <?php foreach ($announcements as $a): ?>
                                        <div class="nk-tb-item">
                                            <div class="nk-tb-col" style="width: 150px;">
                                                <span class="tb-amount"><?= date('d M, Y', strtotime($a['created_at'])) ?></span>
                                                <span class="sub-text"><?= date('h:i A', strtotime($a['created_at'])) ?></span>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span class="tb-lead"><?= htmlspecialchars($a['title']) ?></span>
                                            </div>
                                            <div class="nk-tb-col">
                                                <span class="tb-amount" style="white-space: pre-wrap; font-weight:normal;"><?= htmlspecialchars($a['message']) ?></span>
                                            </div>
                                            <div class="nk-tb-col nk-tb-col-tools">
                                                <ul class="nk-tb-actions gx-1">
                                                    <li>
                                                        <a href="admin_announcements.php?delete_id=<?= $a['id'] ?>" class="btn btn-trigger btn-icon text-danger" onclick="return confirm('Delete this announcement?')">
                                                            <em class="icon ni ni-trash"></em>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Post Announcement</h5>
                <a href="#" class="close" data-dismiss="modal"><em class="icon ni ni-cross"></em></a>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Monthly Meeting">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" required placeholder="Enter detailed message..."></textarea>
                    </div>
                    <div class="text-right">
                        <button type="submit" name="add_announcement" class="btn btn-primary">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
