<?php
require_once 'db.php';

// Set UTF-8
header('Content-Type: text/html; charset=utf-8');
mysqli_set_charset($conn, "utf8mb4");

// Check for duplicates
$sql = "SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids 
        FROM categories 
        GROUP BY name 
        HAVING COUNT(*) > 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found duplicate categories:\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "{$row['name']}: {$row['count']} occurrences (IDs: {$row['ids']})\n";
        
        // Get the lowest ID for this category name
        $ids = explode(',', $row['ids']);
        $keep_id = min($ids);
        
        // Update expenses to use the lowest ID
        $update_sql = "UPDATE expenses SET category_id = ? WHERE category_id IN (" . implode(',', array_diff($ids, [$keep_id])) . ")";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $keep_id);
        
        if ($stmt->execute()) {
            echo "Updated expenses for {$row['name']} to use ID $keep_id\n";
        } else {
            echo "Error updating expenses: " . $stmt->error . "\n";
        }
        
        // Delete duplicate categories
        $delete_sql = "DELETE FROM categories WHERE name = ? AND id != ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("si", $row['name'], $keep_id);
        
        if ($stmt->execute()) {
            echo "Deleted duplicate categories for {$row['name']}\n";
        } else {
            echo "Error deleting duplicates: " . $stmt->error . "\n";
        }
    }
} else {
    echo "No duplicates found\n";
}

// Verify categories
$sql = "SELECT id, name FROM categories ORDER BY name";
$result = $conn->query($sql);

echo "\nCurrent categories:\n";
while ($row = $result->fetch_assoc()) {
    echo "{$row['id']}: {$row['name']}\n";
}

$conn->close();
?> 