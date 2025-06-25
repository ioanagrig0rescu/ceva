<?php
require 'db.php';

// Drop the existing table
$drop_sql = "DROP TABLE IF EXISTS goal_contributions";
if ($conn->query($drop_sql)) {
    echo "Tabela veche a fost ștearsă cu succes.\n";
} else {
    echo "Eroare la ștergerea tabelei: " . $conn->error . "\n";
}

// Create the table with the correct structure
$create_sql = "CREATE TABLE goal_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('add', 'withdraw') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
)";

if ($conn->query($create_sql)) {
    echo "Tabela goal_contributions a fost recreată cu succes!";
} else {
    echo "Eroare la crearea tabelei: " . $conn->error;
}
?> 