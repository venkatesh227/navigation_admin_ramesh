<?php
session_start();

$employee_id = $_SESSION['employee_id'] ?? $_SESSION['emp_id'] ?? null;
if (!$employee_id) {
    header('Location: index.php');
    exit;
}
$employee_id = (int)$employee_id;

include_once('../db/connection.php');

// Reset Notifications
// Reset Notifications (Employee View)
$conn->query("UPDATE expenses SET notification_seen = 1 WHERE employee_id = $employee_id AND notification_seen = 0");

// HANDLE FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    
    $date = $_POST['date'] ?? date('Y-m-d');
    $type = $_POST['type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $desc = $_POST['description'] ?? '';
    
    // Handle Photo
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
         $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
         $newName = uniqid('exp_') . '.' . $ext;
         $target = dirname(__DIR__) . '/uploads/expenses/';
         if (!is_dir($target)) mkdir($target, 0777, true);
         
         if (move_uploaded_file($_FILES['photo']['tmp_name'], $target . $newName)) {
             $photoPath = 'uploads/expenses/' . $newName;
         }
    }
    
    if ($type && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO expenses (employee_id, date, expense_type, amount, description, photo, admin_seen) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("issdss", $employee_id, $date, $type, $amount, $desc, $photoPath);
        if ($stmt->execute()) {
            header("Location: expenses.php?success=1");
            exit;
        }
        $stmt->close();
    }
}

// FETCH HISTORY
$expenses = [];
$res = $conn->query("SELECT * FROM expenses WHERE employee_id = $employee_id ORDER BY date DESC, id DESC LIMIT 50");
if($res) {
    while($row = $res->fetch_assoc()) $expenses[] = $row;
}

include 'header.php';
?>

<!-- HEADER -->
<header class="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-40">
    <div class="flex justify-between items-center max-w-3xl mx-auto">
        <h1 class="text-lg font-bold flex items-center gap-2">
            <a href="dashboard.php" class="text-white opacity-80 hover:opacity-100 mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <span class="text-xl">💰 My Expenses</span>
        </h1>
        <button onclick="toggleModal()" class="bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-bold shadow">+ Add</button>
    </div>
</header>

<div class="max-w-3xl mx-auto p-4 pb-24">

    <!-- SUMMARY CARD -->
    <?php 
       $totalPending = 0; $totalApproved = 0;
       foreach($expenses as $e){
           if($e['status'] == 'Pending') $totalPending += $e['amount'];
           if($e['status'] == 'Approved') $totalApproved += $e['amount'];
       }
    ?>
    <div class="grid grid-cols-2 gap-3 mb-6">
        <div class="bg-yellow-50 p-3 rounded-xl border border-yellow-100 text-center">
            <span class="block text-2xl font-bold text-yellow-700">₹<?= $totalPending ?></span>
            <span class="text-xs font-bold uppercase text-yellow-600">Pending</span>
        </div>
        <div class="bg-green-50 p-3 rounded-xl border border-green-100 text-center">
            <span class="block text-2xl font-bold text-green-700">₹<?= $totalApproved ?></span>
            <span class="text-xs font-bold uppercase text-green-600">Approved</span>
        </div>
    </div>

    <!-- LIST -->
    <?php if(empty($expenses)): ?>
        <div class="bg-white p-8 rounded-xl shadow-sm border-dashed border-2 text-center">
            <span class="text-4xl block mb-2">🧾</span>
            <p class="text-gray-400">No expenses recorded.</p>
        </div>
    <?php else: ?>
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Recent History</h3>
        <div class="space-y-3">
            <?php foreach($expenses as $ex): ?>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                 <div class="flex justify-between items-start mb-1">
                     <div>
                         <span class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($ex['expense_type']) ?></span>
                         <p class="text-xs text-gray-400"><?= date('d M Y', strtotime($ex['date'])) ?></p>
                     </div>
                     <span class="font-bold text-lg text-gray-800">₹<?= $ex['amount'] ?></span>
                 </div>
                 
                 <?php if($ex['description']): ?>
                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($ex['description']) ?></p>
                 <?php endif; ?>

                 <div class="flex justify-between items-center pt-2 border-t border-gray-50">
                     <?php if($ex['photo']): ?>
                        <a href="../<?= $ex['photo'] ?>" target="_blank" class="text-blue-500 text-xs flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            View Receipt
                        </a>
                     <?php else: ?>
                        <span></span>
                     <?php endif; ?>
                     
                     <?php 
                        $statusColor = 'bg-gray-100 text-gray-600';
                        if($ex['status'] == 'Approved') $statusColor = 'bg-green-100 text-green-700';
                        if($ex['status'] == 'Rejected') $statusColor = 'bg-red-100 text-red-700';
                        if($ex['status'] == 'Pending') $statusColor = 'bg-yellow-100 text-yellow-700';
                     ?>
                     <span class="text-xs px-2 py-0.5 rounded font-bold uppercase <?= $statusColor ?>"><?= $ex['status'] ?></span>
                 </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ADD MODAL -->
<div id="expenseModal" class="fixed inset-0 bg-black bg-opacity-60 hidden z-[60] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-gray-700">Add Expense</h3>
            <button onclick="toggleModal()" class="text-2xl leading-none">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" class="p-4 space-y-4">
            <input type="hidden" name="add_expense" value="1">
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                    <input type="date" name="date" class="w-full border-gray-300 rounded-lg text-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                     <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Amount (₹)</label>
                    <input type="number" name="amount" class="w-full border-gray-300 rounded-lg text-sm" placeholder="0.00" step="0.01" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label>
                <select name="type" class="w-full border-gray-300 rounded-lg text-sm" required>
                    <option value="Fuel">Fuel / Petrol</option>
                    <option value="Food">Food / Meals</option>
                    <option value="Travel">Travel / Bus / Train</option>
                    <option value="Lodging">Lodging / Hotel</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Brief details..."></textarea>
            </div>

            <div>
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Receipt Photo</label>
                 <input type="file" name="photo" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow hover:bg-blue-700">Submit</button>
        </form>
    </div>
</div>

<?php include 'bottom_nav.php'; ?>
<?php include 'footer.php'; ?>

<script>
function toggleModal() {
    const m = document.getElementById('expenseModal');
    m.classList.toggle('hidden');
}
</script>
