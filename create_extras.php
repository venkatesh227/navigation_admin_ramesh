<?php
include 'db/connection.php';

// 1. Employee Targets Table
$sql1 = "CREATE TABLE IF NOT EXISTS employee_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    visit_target INT DEFAULT 0,
    achieved INT DEFAULT 0, -- Cache field optional, but we can calc dynamically
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_target (employee_id, month_year)
)";

if ($conn->query($sql1)) {
    echo "Targets Table created.\n";
} else {
    echo "Error Targets: " . $conn->error . "\n";
}

// 2. Leaves Table
$sql2 = "CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50), -- Sick, Casual, etc
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_remark TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql2)) {
    echo "Leaves Table created.\n";
} else {
    echo "Error Leaves: " . $conn->error . "\n";
}
?>
