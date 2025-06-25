<?php
require_once 'db.php';

// Set proper UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mysqli_set_charset($conn, "utf8mb4");

// First, update existing expenses to use a temporary category
$sql = "UPDATE expenses SET category_id = 1 WHERE category_id > 1";
$conn->query($sql);

// Then truncate the categories table
$sql = "TRUNCATE TABLE categories";
if (!$conn->query($sql)) {
    die("Error truncating table: " . $conn->error);
}

// Insert the categories with proper encoding
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

// Prepare the insert statement
$stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

// Insert each category
foreach ($categories as $category) {
    $stmt->bind_param("ss", $category[0], $category[1]);
    if (!$stmt->execute()) {
        echo "Error inserting category {$category[0]}: " . $stmt->error . "\n";
    }
}

$stmt->close();

// Verify the inserted categories
$sql = "SELECT id, name, description FROM categories ORDER BY id";
$result = $conn->query($sql);

echo "Inserted categories:\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}: {$row['name']}\n";
}

$conn->close();

echo "\nCategories reset complete!";
?> 