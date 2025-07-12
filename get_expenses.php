<?php
session_start();
include 'database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Nu sunteți autentificat']);
    exit;
}

if (!isset($_GET['date'])) {
    echo json_encode(['error' => 'Data nu a fost specificată']);
    exit;
}

$user_id = $_SESSION['id'];
$date = $_GET['date'];

try {
    $sql = "SELECT e.*, c.name as category_name 
            FROM expenses e 
            LEFT JOIN categories c ON e.category_id = c.id 
            WHERE e.user_id = ? AND e.date = ?
            ORDER BY e.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        // Adaugă clasa de culoare pentru categorie
        $color_class = '';
        switch(mb_strtolower($row['category_name'], 'UTF-8')) {
            case 'locuință':
                $color_class = 'primary';
                break;
            case 'alimentație':
                $color_class = 'success';
                break;
            case 'transport':
                $color_class = 'info';
                break;
            case 'sănătate':
                $color_class = 'danger';
                break;
            case 'shopping':
                $color_class = 'warning';
                break;
            case 'timp liber':
                $color_class = 'purple';
                break;
            case 'educație':
                $color_class = 'teal';
                break;
            case 'familie':
                $color_class = 'pink';
                break;
            case 'pets':
                $color_class = 'brown';
                break;
            case 'cadouri':
                $color_class = 'orange';
                break;
            case 'călătorii':
                $color_class = 'cyan';
                break;
            case 'servicii & abonamente':
                $color_class = 'indigo';
                break;
            case 'diverse':
                $color_class = 'secondary';
                break;
            default:
                $color_class = 'secondary';
        }
        
        $row['color_class'] = $color_class;
        $expenses[] = $row;
    }
    
    echo json_encode($expenses);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'A apărut o eroare la încărcarea cheltuielilor']);
} 