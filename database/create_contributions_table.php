<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS goal_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('add', 'withdraw') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Tabela goal_contributions a fost creată cu succes!";
} else {
    echo "Eroare la crearea tabelei: " . $conn->error;
}
?> 