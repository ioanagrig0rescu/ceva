<?php
session_start();
header('Content-Type: application/json');

include 'database/db.php';
include 'sesion-check/check.php';

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Preluăm obiectivele cu deadline apropiat
    $sql = "SELECT id, name, target_amount, current_amount, deadline,
            DATEDIFF(deadline, CURDATE()) as days_remaining
            FROM goals 
            WHERE user_id = ? 
            AND current_amount < target_amount 
            AND DATEDIFF(deadline, CURDATE()) <= 14 
            AND DATEDIFF(deadline, CURDATE()) >= 0
            ORDER BY days_remaining ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $warnings = [];
    while ($goal = $result->fetch_assoc()) {
        $warnings[] = [
            'id' => $goal['id'],
            'name' => $goal['name'],
            'target_amount' => (float)$goal['target_amount'],
            'current_amount' => (float)$goal['current_amount'],
            'days_remaining' => (int)$goal['days_remaining'],
            'deadline' => $goal['deadline']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'warnings' => $warnings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'A apărut o eroare la verificarea deadline-urilor'
    ]);
} 