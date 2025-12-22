<?php
session_start();

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit;
}

$employeeName = $_SESSION['employee_name'] ?? 'User';
include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <span class="text-2xl">👤</span> My Profile
        </h1>
        <a href="logout.php" class="text-white text-sm bg-blue-700 px-3 py-1 rounded">Logout</a>
    </div>
</header>
    
<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- INFO CARD -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6 text-center">
        <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl font-bold mx-auto mb-3">
            <?= strtoupper(substr($employeeName, 0, 1)) ?>
        </div>
        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($employeeName) ?></h2>
        <p class="text-gray-500 text-sm">Sales Executive</p>
    </div>

    <!-- SETTINGS LIST -->
    <div class="space-y-3">
        
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
             <span class="text-2xl p-2 bg-gray-50 rounded-full">🔐</span>
             <div class="flex-grow">
                 <h4 class="font-bold text-gray-800">Change Password</h4>
                 <p class="text-xs text-gray-500">Update your login security</p>
             </div>
             <button onclick="togglePasswordModal()" class="text-blue-600 font-bold text-sm">Edit</button>
        </div>

        <a href="help.php" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 group">
             <span class="text-2xl p-2 bg-gray-50 rounded-full">❓</span>
             <div class="flex-grow">
                 <h4 class="font-bold text-gray-800 group-hover:text-blue-600 transition">Help & Support</h4>
                 <p class="text-xs text-gray-500">View detailed guides</p>
             </div>
             <span class="text-gray-300">→</span>
        </a>

         <a href="logout.php" class="bg-red-50 p-4 rounded-xl shadow-sm border border-red-100 flex items-center gap-4 group">
             <span class="text-2xl p-2 bg-white rounded-full">🚪</span>
             <div class="flex-grow">
                 <h4 class="font-bold text-red-600">Logout</h4>
                 <p class="text-xs text-red-400">Sign out of your account</p>
             </div>
        </a>

    </div>

</div>

<!-- PASSWORD MODAL -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold mb-4">Change Password</h3>
        <form id="passForm" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
                <input type="password" id="new_pass" class="w-full border-gray-300 rounded-lg" required minlength="6">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm Password</label>
                <input type="password" id="conf_pass" class="w-full border-gray-300 rounded-lg" required minlength="6">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg">Update</button>
            <button type="button" onclick="togglePasswordModal()" class="w-full text-gray-500 py-2">Cancel</button>
        </form>
    </div>
</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>

<script>
function togglePasswordModal(){
    document.getElementById('passwordModal').classList.toggle('hidden');
}

document.getElementById('passForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const p1 = document.getElementById('new_pass').value;
    const p2 = document.getElementById('conf_pass').value;

    if(p1 !== p2){
        Swal.fire({icon: 'error', text: 'Passwords do not match'});
        return;
    }

    try {
        let formData = new FormData();
        formData.append('password', p1);
        
        let res = await fetch('update_password.php', {
            method: 'POST',
            body: formData
        });
        let out = await res.json();
        
        if(out.status === 'success'){
            Swal.fire({icon: 'success', text: 'Password updated!'});
            togglePasswordModal();
            document.getElementById('passForm').reset();
        } else {
            Swal.fire({icon: 'error', text: out.message});
        }
    } catch(err){
        Swal.fire({icon: 'error', text: 'Failed to update'});
    }
});
</script>
