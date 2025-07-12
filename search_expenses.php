<?php
session_start();
header('Content-Type: application/json');

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once 'database/db.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$category_id = $data['category_id'] ?? '';
$min_amount = $data['min_amount'] ?? '';
$max_amount = $data['max_amount'] ?? '';

// Build query
$sql = "SELECT e.*, c.name as category_name, DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') as created_at 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.user_id = ? AND e.date BETWEEN ? AND ?";
$params = [$_SESSION['id'], $start_date, $end_date];
$types = "iss"; // integer and two strings

// Add optional filters
if (!empty($category_id)) {
    $sql .= " AND e.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($min_amount)) {
    $sql .= " AND e.amount >= ?";
    $params[] = $min_amount;
    $types .= "d";
}

if (!empty($max_amount)) {
    $sql .= " AND e.amount <= ?";
    $params[] = $max_amount;
    $types .= "d";
}

$sql .= " ORDER BY e.date DESC, e.id DESC";

// Prepare and execute statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
$expenses = [];
while ($row = $result->fetch_assoc()) {
    // Add category color class
    $eventColor = '';
    switch(mb_strtolower($row['category_name'], 'UTF-8')) {
        case 'locuință':
            $eventColor = 'primary'; // Albastru
            break;
        case 'alimentație':
            $eventColor = 'success'; // Verde
            break;
        case 'transport':
            $eventColor = 'info'; // Albastru deschis
            break;
        case 'sănătate':
            $eventColor = 'danger'; // Roșu
            break;
        case 'shopping':
            $eventColor = 'warning'; // Galben
            break;
        case 'timp liber':
            $eventColor = 'purple'; // Violet
            break;
        case 'educație':
            $eventColor = 'teal'; // Turcoaz
            break;
        case 'familie':
            $eventColor = 'pink'; // Roz
            break;
        case 'pets':
            $eventColor = 'brown'; // Maro
            break;
        case 'cadouri':
            $eventColor = 'orange'; // Portocaliu
            break;
        case 'călătorii':
            $eventColor = 'cyan'; // Cyan
            break;
        case 'servicii & abonamente':
            $eventColor = 'indigo'; // Indigo
            break;
        case 'diverse':
            $eventColor = 'secondary'; // Gri
            break;
        default:
            $eventColor = 'secondary'; // Gri pentru categorii necunoscute
    }
    $row['color_class'] = $eventColor;
    $expenses[] = $row;
}

// Return results
echo json_encode($expenses);

// Close statement and connection
$stmt->close();
$conn->close(); 