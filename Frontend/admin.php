<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Field Staff Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        #adminMap {
            width: 100%;
            height: 500px;
            border-radius: 1rem;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">

    <header class="bg-blue-600 text-white p-3 flex justify-between items-center">
        <button onclick="toggleSidebar()" class="text-white text-2xl focus:outline-none">&#9776;</button>
        <h1 class="text-lg font-semibold">Field Staff Tracker</h1>
    </header>


    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" onclick="closeSidebar()"></div>
    <div id="sidebar" class="fixed inset-y-0 left-0 bg-indigo-900 text-white w-64 transform -translate-x-full transition-transform duration-300 z-50">
        <div class="p-3 flex justify-between items-center border-b border-gray-600">
            <span class="font-bold text-lg">Menu</span>
            <button onclick="closeSidebar()" class="text-white text-xl font-bold">&times;</button>
        </div>
        <?php include 'navbar.php'; ?>


    </div>

    <div class='p-3 mt-2'>








    </div>

    <div class='p-3 mt-2'>








    </div>

    <div class='p-3 mt-2'>







        <div class='container mt-2'>







        </div>

        <div class='p-3 mt-2'>

            <div class="max-w-4xl mx-auto p-3">
                <h2 class="text-2xl font-bold mb-4">🗺️ Admin Dashboard</h2>
                <p class="mb-2 text-sm text-gray-700">Below map shows the last known locations of all active field staff.</p>
                <div id="adminMap" class="shadow"></div>
            </div>


            <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initAdminMap">
            </script>
<?php include 'footer.php'?>