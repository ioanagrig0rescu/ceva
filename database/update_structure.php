<?php
require_once 'db.php';

// Verifică dacă tabelul expenses există
$result = $conn->query("SHOW TABLES LIKE 'expenses'");
if ($result->num_rows == 0) {
    // Creează tabelul expenses dacă nu există
    $sql = "CREATE TABLE expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT NOT NULL,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabelul expenses a fost creat cu succes\n";
    } else {
        echo "Eroare la crearea tabelului expenses: " . $conn->error . "\n";
    }
}

// Verifică dacă tabelul categories există
$result = $conn->query("SHOW TABLES LIKE 'categories'");
if ($result->num_rows == 0) {
    // Creează tabelul categories dacă nu există
    $sql = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tabelul categories a fost creat cu succes\n";
    } else {
        echo "Eroare la crearea tabelului categories: " . $conn->error . "\n";
    }

    // Adaugă categoriile predefinite
    $predefined_categories = [
        'Locuință',
        'Alimentație',
        'Transport',
        'Sănătate',
        'Shopping',
        'Timp liber',
        'Educație',
        'Familie',
        'Pets',
        'Cadouri',
        'Călătorii',
        'Servicii & Abonamente',
        'Diverse'
    ];

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    foreach ($predefined_categories as $category) {
        $stmt->bind_param("s", $category);
        if ($stmt->execute()) {
            echo "Categoria '$category' a fost adăugată\n";
        } else {
            echo "Eroare la adăugarea categoriei '$category': " . $stmt->error . "\n";
        }
    }
    $stmt->close();
} else {
    // Verifică și adaugă coloanele necesare în tabelul expenses
    $columns_expenses = [
        'user_id' => 'INT NOT NULL',
        'category_id' => 'INT NOT NULL',
        'amount' => 'DECIMAL(10,2) NOT NULL',
        'description' => 'TEXT NOT NULL',
        'date' => 'DATE NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];

    foreach ($columns_expenses as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM expenses LIKE '$column'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE expenses ADD COLUMN $column $definition";
            if ($conn->query($sql) === TRUE) {
                echo "Coloana '$column' a fost adăugată în tabelul expenses\n";
            } else {
                echo "Eroare la adăugarea coloanei '$column': " . $conn->error . "\n";
            }
        }
    }

    // Verifică și adaugă coloanele necesare în tabelul categories
    $columns_categories = [
        'user_id' => 'INT NULL',
        'name' => 'VARCHAR(255) NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];

    foreach ($columns_categories as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM categories LIKE '$column'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE categories ADD COLUMN $column $definition";
            if ($conn->query($sql) === TRUE) {
                echo "Coloana '$column' a fost adăugată în tabelul categories\n";
            } else {
                echo "Eroare la adăugarea coloanei '$column': " . $conn->error . "\n";
            }
        }
    }
}

echo "Actualizarea structurii bazei de date s-a încheiat\n"; 