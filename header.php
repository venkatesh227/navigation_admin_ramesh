<!DOCTYPE html>
<html lang="zxx" class="js">

<head>
    <base href="./">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Fav Icon  -->
    <link rel="shortcut icon" href="./images/favicon.png">
    <!-- Page Title  -->
    <title>Health Hospitals FSMS</title>
    <!-- StyleSheets  -->
    <link rel="stylesheet" href="./assets/css/dashlite.css?ver=2.6.0">
    <link id="skin-default" rel="stylesheet" href="./assets/css/theme.css?ver=2.6.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> -->
     <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

   <style>
.pin-wrapper {
    position: relative;
    width: 30px;
    height: 50px;
}

.pin-img {
    width: 25px;
    height: 41px;
    position: absolute;
    bottom: 0;
    left: 2px;
}

.pin-badge {
    position: absolute;
    top: -15px;
    left: 5px;
    background: #ff2e2e;
    color: white;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    text-align: center;
    line-height: 22px;
    font-size: 13px;
    font-weight: bold;
    border: 2px solid white;
    box-shadow: 0 0 3px #333;
}
</style>

   
</head>

<body class="nk-body bg-lighter npc-general has-sidebar ">
    <!DOCTYPE html>
    <html lang="zxx" class="js">

    <head>
        <base href="./">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Fav Icon  -->
        <link rel="shortcut icon" href="./images/favicon.png">
        <!-- Page Title  -->
        <title>Health Hospitals FSMS</title>
        <!-- StyleSheets  -->
        <link rel="stylesheet" href="./assets/css/dashlite.css?ver=2.6.0">
        <link id="skin-default" rel="stylesheet" href="./assets/css/theme.css?ver=2.6.0">
    </head>

    <body class="nk-body bg-lighter npc-general has-sidebar ">
        <div class="nk-app-root">
            <!-- main @s -->
            <div class="nk-main ">
                <!-- sidebar @s -->
                <div class="nk-sidebar nk-sidebar-fixed is-dark " data-content="sidebarMenu">
                    <div class="nk-sidebar-element nk-sidebar-head">
                        <div class="nk-menu-trigger">
                            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu"><em
                                    class="icon ni ni-arrow-left"></em></a>
                            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex"
                                data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
                        </div>
                        <div class="nk-sidebar-brand">
                            <a href="dashboard.php" class="logo-link nk-sidebar-logo">
                                <p>Health Hospitals FSMS</p>
                            </a>
                        </div>
                    </div><!-- .nk-sidebar-element -->
                    <?php include 'leftsidebar.php'; ?>
                    <!-- .nk-sidebar-element -->
                </div>
                <!-- sidebar @e -->
                <!-- wrap @s -->
                <div class="nk-wrap ">
                    <!-- main header @s -->
                    <?php include 'navbar.php'; ?>