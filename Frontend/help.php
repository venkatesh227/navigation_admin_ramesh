<?php
session_start();

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit;
}
include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">❓</span> Help & Support
        </h1>
    </div>
</header>
    
<div class="max-w-3xl mx-auto p-4 pb-24">

    <div class="space-y-4">
        
        <!-- CARD 1 -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg text-gray-800 mb-2">📌 How to Check In?</h3>
            <p class="text-sm text-gray-600 leading-relaxed">
                1. Go to the <strong>Home</strong> tab.<br>
                2. Tap the Green <strong>"Check In"</strong> button.<br>
                3. Your live location will now be tracked while you work.
            </p>
        </div>

        <!-- CARD 2 -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg text-gray-800 mb-2">📸 Adding a Visit</h3>
            <p class="text-sm text-gray-600 leading-relaxed">
                1. Go to the <strong>Members</strong> tab.<br>
                2. Tap <strong>"+ Add New"</strong> or select an assigned member from the list.<br>
                3. Fill in the remarks and attach a photo.<br>
                4. Tap <strong>Save</strong>.
            </p>
        </div>

         <!-- CARD 3 -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg text-gray-800 mb-2">📍 Location Issues?</h3>
            <p class="text-sm text-gray-600 leading-relaxed">
                If the app says "GPS Error", please ensure:
                <ul class="list-disc pl-5 mt-2 space-y-1">
                    <li>GPS/Location is turned <strong>ON</strong> in phone settings.</li>
                    <li>You have allowed location permission for the browser.</li>
                </ul>
            </p>
        </div>

    </div>

</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>
