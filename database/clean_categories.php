<?php
require_once 'db.php';

// Set proper UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mysqli_set_charset($conn, "utf8mb4");

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

try {
    // Start transaction
    $conn->begin_transaction();

    // Get all expenses and their categories
    $sql = "SELECT e.id, e.category_id, c.name as category_name 
            FROM expenses e 
            LEFT JOIN categories c ON e.category_id = c.id";
    $result = $conn->query($sql);
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }

    // Clear the categories table
    $conn->query("TRUNCATE TABLE categories");

    // Insert the new categories
    $categories = [
        ['Locuință', 'Cheltuieli legate de chirie, întreținere, utilități, mobilier și amenajări'],
        ['Alimentație', 'Cheltuieli pentru mâncare, băutură și produse alimentare'],
        ['Transport', 'Cheltuieli pentru transport public, combustibil, întreținere mașină'],
        ['Sănătate', 'Cheltuieli medicale, medicamente, analize și consultații'],
        ['Shopping', 'Cheltuieli pentru haine, încălțăminte și accesorii'],
        ['Timp liber', 'Cheltuieli pentru activități recreative, hobby-uri și divertisment'],
        ['Educație', 'Cheltuieli pentru cursuri, cărți și materiale educaționale'],
        ['Familie', 'Cheltuieli legate de familie și copii'],
        ['Pets', 'Cheltuieli pentru animale de companie'],
        ['Cadouri', 'Cheltuieli pentru cadouri și ocazii speciale'],
        ['Călătorii', 'Cheltuieli pentru vacanțe și călătorii'],
        ['Servicii & abonamente', 'Cheltuieli pentru servicii și abonamente lunare'],
        ['Diverse', 'Alte cheltuieli care nu se încadrează în categoriile de mai sus']
    ];

    // Insert categories
    foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
        $stmt->close();
    }

    // Update all expenses to use the first category (we'll fix them manually later)
    $stmt = $conn->prepare("UPDATE expenses SET category_id = 1");
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Verify the updated categories
    $sql = "SELECT id, name FROM categories ORDER BY id";
    $result = $conn->query($sql);

    echo "Updated categories:\n";
    while ($row = $result->fetch_assoc()) {
        echo "{$row['id']}: {$row['name']}\n";
    }

    echo "\nCategories cleanup complete!";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
} finally {
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    $conn->close();
}
?> 