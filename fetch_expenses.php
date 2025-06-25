<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set UTF-8 encoding for all output
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

include 'sesion-check/check.php';
require_once 'database/db.php';

// Set UTF-8 for database connection
mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['error' => 'Nu sunteți autentificat']);
    exit;
}

// Get date from query string
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch expenses for the given date using prepared statement
$sql = "SELECT e.id, e.amount, e.description, e.date, e.category_id, c.name as category_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.user_id = ? AND e.date = ?
        ORDER BY e.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id'], $date);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = [
        'id' => $row['id'],
        'amount' => $row['amount'],
        'description' => mb_convert_encoding($row['description'], 'UTF-8', 'UTF-8'),
        'category_name' => mb_convert_encoding($row['category_name'], 'UTF-8', 'UTF-8'),
        'category_id' => $row['category_id'],
        'date' => $row['date']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($expenses);
?>