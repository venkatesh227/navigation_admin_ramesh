 <div class="nk-header nk-header-fixed is-light">
     <div class="container-fluid">
         <div class="nk-header-wrap">
             <div class="nk-menu-trigger d-xl-none ml-n1">
                 <a href="#" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu"><em
                         class="icon ni ni-menu"></em></a>
             </div>
             <div class="nk-header-brand d-xl-none">
                 <a href="html/index.php" class="logo-link">
                     <img class="logo-light logo-img" src="./images/logo.png"
                         srcset="./images/logo2x.png 2x" alt="logo">
                     <img class="logo-dark logo-img" src="./images/logo-dark.png"
                         srcset="./images/logo-dark2x.png 2x" alt="logo-dark">
                 </a>
             </div><!-- .nk-header-brand -->

             <?php
                // Fetch dynamic user info
                $nav_user_id = $_SESSION['user_id'] ?? 0;
                $nav_name = "Administrator";
                $nav_email = "User";

                // We use global $conn from connection.php which should be included by parent page
                if (isset($conn) && $nav_user_id > 0) {
                    $stmtNav = $conn->prepare("SELECT u.user_name, a.name FROM users u LEFT JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
                    if ($stmtNav) {
                        $stmtNav->bind_param("i", $nav_user_id);
                        $stmtNav->execute();
                        $resNav = $stmtNav->get_result();
                        if ($rowNav = $resNav->fetch_assoc()) {
                            $nav_name = !empty($rowNav['name']) ? $rowNav['name'] : 'Administrator';
                            $nav_email = $rowNav['user_name'];
                        }
                        $stmtNav->close();
                    }
                }
             ?>

             <div class="nk-header-tools">
                 <ul class="nk-quick-nav">
                     <li class="dropdown user-dropdown">
                         <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                             <div class="user-toggle">
                                 <div class="user-avatar sm">
                                     <em class="icon ni ni-user-alt"></em>
                                 </div>
                                 <div class="user-info d-none d-md-block">
                                     <div class="user-status">Administrator</div>
                                     <div class="user-name dropdown-indicator"><?php echo htmlspecialchars($nav_name); ?></div>
                                 </div>
                             </div>
                         </a>
                         <div
                             class="dropdown-menu dropdown-menu-md dropdown-menu-right dropdown-menu-s1">
                             <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                 <div class="user-card">
                                     <div class="user-avatar">
                                         <span><?php echo strtoupper(substr($nav_name, 0, 2)); ?></span>
                                     </div>
                                     <div class="user-info">
                                         <span class="lead-text"><?php echo htmlspecialchars($nav_name); ?></span>
                                         <span class="sub-text"><?php echo htmlspecialchars($nav_email); ?></span>
                                     </div>
                                 </div>
                             </div>
                             <div class="dropdown-inner">
                                 <ul class="link-list">

                                     <li><a href="user-profile-setting.php"><em
                                                 class="icon ni ni-setting-alt"></em><span>Account
                                                 Setting</span></a></li>

                             </div>
                             <div class="dropdown-inner">
                                 <ul class="link-list">
                                     <li><a href="index.php?action=logout"><em class="icon ni ni-signout"></em><span>Sign
                                                 out</span></a></li>
                                 </ul>
                             </div>
                         </div>
                     </li><!-- .dropdown -->
                     <!-- .dropdown -->
                 </ul><!-- .nk-quick-nav -->
             </div><!-- .nk-header-tools -->
         </div><!-- .nk-header-wrap -->
     </div><!-- .container-fliud -->
 </div>