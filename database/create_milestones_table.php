<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS goal_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Tabela goal_milestones a fost creată cu succes!";
} else {
    echo "Eroare la crearea tabelei: " . $conn->error;
}
?> 