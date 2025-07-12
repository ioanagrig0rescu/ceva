<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['year']) || !isset($_GET['month'])) {
        throw new Exception('Anul și luna sunt obligatorii.');
    }

    $year = intval($_GET['year']);
    $month = intval($_GET['month']);
    
    // Validate year and month
    if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
        throw new Exception('An sau lună invalidă.');
    }
    
    // Get both current and historical incomes for the specified month
    $sql = "SELECT name, amount, income_date, 0 as is_historical 
            FROM incomes 
            WHERE user_id = ? 
            AND YEAR(income_date) = ? 
            AND MONTH(income_date) = ?
            UNION ALL
            SELECT name, amount, income_date, 1 as is_historical 
            FROM income_history 
            WHERE user_id = ? 
            AND YEAR(income_date) = ? 
            AND MONTH(income_date) = ?
            ORDER BY income_date ASC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Eroare la pregătirea interogării: ' . $conn->error);
    }
    
    $stmt->bind_param("iiiiii", 
        $_SESSION['id'], $year, $month,
        $_SESSION['id'], $year, $month
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Eroare la executarea interogării: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $incomes = [];
    
    while ($row = $result->fetch_assoc()) {
        $incomes[] = [
            'name' => $row['name'],
            'amount' => $row['amount'],
            'income_date' => $row['income_date'],
            'is_historical' => (bool)$row['is_historical']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'incomes' => $incomes
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_calendar_incomes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 