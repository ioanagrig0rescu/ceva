<?php
include 'db.php';

// Drop existing table if it exists
$conn->query("DROP TABLE IF EXISTS incomes");

// Create table with correct structure
$create_table_sql = "CREATE TABLE incomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    income_date DATE NOT NULL,
    category VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($create_table_sql)) {
    echo "Tabelul 'incomes' a fost recreat cu succes!";
} else {
    echo "Eroare la recrearea tabelului: " . $conn->error;
}

$conn->close();
?> 