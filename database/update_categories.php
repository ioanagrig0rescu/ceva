<?php
require_once 'db.php';

// Set proper UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');
mysqli_set_charset($conn, "utf8mb4");

// Define the new categories
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

// Get existing categories
$sql = "SELECT id, name FROM categories ORDER BY id";
$result = $conn->query($sql);
$existing_categories = [];
while ($row = $result->fetch_assoc()) {
    $existing_categories[] = $row;
}

// Update existing categories
for ($i = 0; $i < count($categories) && $i < count($existing_categories); $i++) {
    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $categories[$i][0], $categories[$i][1], $existing_categories[$i]['id']);
    if (!$stmt->execute()) {
        echo "Error updating category {$categories[$i][0]}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

// Add any remaining new categories
if (count($categories) > count($existing_categories)) {
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    for ($i = count($existing_categories); $i < count($categories); $i++) {
        $stmt->bind_param("ss", $categories[$i][0], $categories[$i][1]);
        if (!$stmt->execute()) {
            echo "Error inserting category {$categories[$i][0]}: " . $stmt->error . "\n";
        }
    }
    $stmt->close();
}

// Verify the updated categories
$sql = "SELECT id, name, description FROM categories ORDER BY id";
$result = $conn->query($sql);

echo "Updated categories:\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}: {$row['name']}\n";
}

$conn->close();

echo "\nCategories update complete!";
?> 