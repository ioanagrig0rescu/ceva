<?php
session_start();
include 'database/db.php';

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Utilizatorul nu este autentificat'
    ]));
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Use provided month/year or default to current
    $month = isset($input['month']) ? intval($input['month']) : intval(date('m'));
    $year = isset($input['year']) ? intval($input['year']) : intval(date('Y'));
    
    // Luna selectată
    $monthly_query = "SELECT COALESCE(SUM(amount), 0) as total FROM (
                        SELECT amount FROM incomes 
                        WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                        UNION ALL
                        SELECT amount FROM income_history 
                        WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                    ) as combined_incomes";
    
    $stmt = $conn->prepare($monthly_query);
    $stmt->bind_param("iiiiii", $_SESSION['id'], $month, $year, $_SESSION['id'], $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_total = $result->fetch_assoc()['total'];
    
    // Anul selectat
    $yearly_query = "SELECT COALESCE(SUM(amount), 0) as total FROM (
                        SELECT amount FROM incomes 
                        WHERE user_id = ? AND YEAR(income_date) = ?
                        UNION ALL
                        SELECT amount FROM income_history 
                        WHERE user_id = ? AND YEAR(income_date) = ?
                    ) as combined_incomes";
    
    $stmt = $conn->prepare($yearly_query);
    $stmt->bind_param("iiii", $_SESSION['id'], $year, $_SESSION['id'], $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $yearly_total = $result->fetch_assoc()['total'];
    
    // Media lunară pentru anul selectat
    $monthly_average_query = "SELECT COALESCE(AVG(monthly_total), 0) as average 
                             FROM (
                                 SELECT YEAR(income_date) as year, 
                                        MONTH(income_date) as month,
                                        SUM(amount) as monthly_total 
                                 FROM (
                                     SELECT income_date, amount 
                                     FROM incomes 
                                     WHERE user_id = ? AND YEAR(income_date) = ? 
                                     UNION ALL
                                     SELECT income_date, amount 
                                     FROM income_history 
                                     WHERE user_id = ? AND YEAR(income_date) = ?
                                 ) as combined_incomes
                                 GROUP BY YEAR(income_date), MONTH(income_date)
                             ) as monthly_totals";
    
    $stmt = $conn->prepare($monthly_average_query);
    $stmt->bind_param("iiii", $_SESSION['id'], $year, $_SESSION['id'], $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_average = $result->fetch_assoc()['average'];
    
    // Returnează rezultatele
    echo json_encode([
        'success' => true,
        'monthlyTotal' => number_format($monthly_total, 2),
        'yearlyTotal' => number_format($yearly_total, 2),
        'monthlyAverage' => number_format($monthly_average, 2)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la obținerea statisticilor: ' . $e->getMessage()
    ]);
}

$conn->close(); 