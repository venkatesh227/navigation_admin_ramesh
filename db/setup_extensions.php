<?php
include 'connection.php';

// 1. EXPENSES TABLE
$sql1 = "CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    expense_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    photo VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql1) === TRUE) {
    echo "Table 'expenses' created successfully.<br>";
} else {
    echo "Error creating table 'expenses': " . $conn->error . "<br>";
}

// 2. ANNOUNCEMENTS TABLE
$sql2 = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2) === TRUE) {
    echo "Table 'announcements' created successfully.<br>";
    
    // Seed one announcement if empty
    $check = $conn->query("SELECT count(*) as c FROM announcements");
    if($check && $check->fetch_assoc()['c'] == 0){
        $conn->query("INSERT INTO announcements (title, message) VALUES ('Welcome to the new FSMS!', 'We have updated the mobile app. Check out the new Expenses feature.')");
        echo "Seeded sample announcement.<br>";
    }
} else {
    echo "Error creating table 'announcements': " . $conn->error . "<br>";
}

echo "Database setup complete.";
?>
