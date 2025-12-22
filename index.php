<?php
session_start();
include_once('db/connection.php');
include_once('db/functions.php');
$error = [];

if (isset($_POST['sign_in'])) {

    $username = trim($_POST['user_name'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $error['user_name'] = "Email is required";
    }

    if (empty($password)) {
        $error['password'] = "Password is required";
    }

    if (empty($error)) {
        // Use prepared statement to fetch user by username ONLY first
        $stmt = $conn->prepare("SELECT id, user_name, password FROM users WHERE user_name = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $user = $res->fetch_assoc();
                
                // Verify password (handles both MD5 legacy and Bcrypt)
                if (verifyUserPassword($password, $user['password'], $user['id'], $conn)) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['user_name'];
                    redirect('dashboard.php');
                } else {
                    $error['login'] = "Invalid username or password";
                }
            } else {
                $error['login'] = "Invalid username or password";
            }
            $stmt->close();
        } else {
            $error['login'] = "Database error. Please try again.";
        }
    }
}
if ($_GET['action'] == 'logout') {
    session_destroy();
    redirect('index.php');
}
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="zxx" class="js">

<head>
    <base href="./">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="images/favicon.png">
    <!-- Page Title  -->
    <title>Health Hospitals FSMS</title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="assets/css/dashlite.css?ver=2.6.0">
    <link id="skin-default" rel="stylesheet" href="assets/css/theme.css?ver=2.6.0">
</head>

<body class="nk-body bg-white npc-general pg-auth">
    <div class="nk-app-root">
        <!-- main @s -->
        <div class="nk-main">
            <!-- wrap @s -->
            <div class="nk-wrap nk-wrap-nosidebar">
                <!-- content @s -->
                <div class="nk-content ">
                    <div class="nk-split nk-split-page nk-split-md">
                        <div class="nk-split-content nk-block-area nk-block-area-column nk-auth-container bg-white">
                            <div class="absolute-top-right d-lg-none p-3 p-sm-5">
                                <a href="#" class="toggle btn-white btn btn-icon btn-light" data-target="athPromo"><em class="icon ni ni-info"></em></a>
                            </div>
                            <div class="nk-block nk-block-middle nk-auth-body">
                                <div class="brand-logo pb-5">
                                    <a href="html/index.php" class="logo-link">
                                        <img class="logo-light logo-img logo-img-lg" src="images/logo.png" srcset="images/logo2x.png 2x" alt="logo">
                                        <img class="logo-dark logo-img logo-img-lg" src="images/logo-dark.png" srcset="images/logo-dark2x.png 1x" alt="logo-dark">
                                    </a>
                                </div>
                                <div class="nk-block-head">
                                    <div class="nk-block-head-content">
                                        <h5 class="nk-block-title">Sign-In</h5>
                                        <div class="nk-block-des">
                                            <p>Access the Admin panel using your email and passcode.</p>
                                        </div>
                                    </div>
                                </div><!-- .nk-block-head -->
                                <?php if (!empty($error['login'])) { ?>
                                    <div class="col-12">
                                        <div class="alert alert-danger">
                                            <?php echo $error['login']; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <div class="form-label-group">
                                            <label class="form-label" for="default-01">Email or Username</label>
                                        </div>
                                        <div class="form-control-wrap">
                                            <input type="text" class="form-control form-control-lg" id="default-01" name="user_name" value="<?php if (isset($_POST['user_name'])) echo $_POST['user_name'] ?>" placeholder="Enter your email address or username">
                                        </div>
                                        <?php if (!empty($error['user_name'])) { ?>
                                            <label for="user_name" generated="true" class="error" style="color: red;"><?php echo $error['user_name']; ?></label>
                                        <?php } ?>
                                    </div><!-- .form-group -->
                                    <div class="form-group">
                                        <div class="form-label-group">
                                            <label class="form-label" for="password">Passcode</label>
                                        </div>
                                        <div class="form-control-wrap">
                                            <a tabindex="-1" href="#" class="form-icon form-icon-right passcode-switch lg" data-target="password">
                                                <em class="passcode-icon icon-show icon ni ni-eye"></em>
                                                <em class="passcode-icon icon-hide icon ni ni-eye-off"></em>
                                            </a>
                                            <input type="password" class="form-control form-control-lg" name="password" id="password" value="<?php if (isset($_POST['password'])) echo $_POST['password'] ?>" placeholder="Enter your passcode">
                                        </div>
                                        <?php if (!empty($error['password'])) { ?>
                                            <label for="password" generated="true" class="error" style="color: red;"><?php echo $error['password']; ?></label>
                                        <?php } ?>
                                    </div><!-- .form-group -->
                                    <div class="form-group">
                                        <button type="submit" name="sign_in" class="btn btn-lg btn-primary btn-block">Sign in</button>
                                    </div>
                                </form><!-- form -->


                            </div><!-- .nk-block -->

                        </div><!-- .nk-split-content -->
                        <div class="nk-split-content nk-split-stretch bg-abstract"></div>
                    </div><!-- .nk-split -->
                </div>
                <!-- wrap @e -->
            </div>
            <!-- content @e -->
        </div>
        <!-- main @e -->
    </div>
    <!-- app-root @e -->
    <!-- JavaScript -->
    <script src="./assets/js/bundle.js?ver=2.6.0"></script>
    <script src="./assets/js/scripts.js?ver=2.6.0"></script>

</html>