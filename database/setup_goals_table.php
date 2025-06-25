<?php
// Conectare la baza de date
$conn = new mysqli('localhost', 'root', '', 'budgetmaster');

// Verificăm conexiunea
if ($conn->connect_error) {
    die("Conexiune eșuată: " . $conn->connect_error);
}

// Setăm caracterele pentru UTF-8
$conn->set_charset("utf8mb4");

// SQL pentru crearea tabelei
$sql = "CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    deadline DATE NOT NULL,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
)";

if ($conn->query($sql)) {
    echo "Tabela goals a fost creată cu succes!";
} else {
    echo "Eroare la crearea tabelei: " . $conn->error;
}

$conn->close();
?> 