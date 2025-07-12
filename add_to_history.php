<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['income_id'])) {
        throw new Exception('ID-ul venitului nu a fost specificat.');
    }

    $income_id = $_POST['income_id'];
    
    // First get the current income details before updating
    $get_income_sql = "SELECT name, amount, income_date FROM incomes WHERE id = ? AND user_id = ?";
    $get_stmt = $conn->prepare($get_income_sql);
    if (!$get_stmt) {
        throw new Exception('Eroare la pregătirea interogării: ' . $conn->error);
    }
    
    $get_stmt->bind_param("ii", $income_id, $_SESSION['id']);
    if (!$get_stmt->execute()) {
        throw new Exception('Eroare la obținerea detaliilor venitului: ' . $get_stmt->error);
    }
    
    $result = $get_stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Venitul nu a fost găsit sau nu aveți permisiunea de a-l modifica.');
    }

    $income = $result->fetch_assoc();
    
    // Save to history
    $save_sql = "INSERT INTO income_history (income_id, user_id, name, amount, income_date) 
                 VALUES (?, ?, ?, ?, ?)";
    $save_stmt = $conn->prepare($save_sql);
    if (!$save_stmt) {
        throw new Exception('Eroare la pregătirea salvării: ' . $conn->error);
    }
    
    $save_stmt->bind_param("iisds", 
        $income_id, 
        $_SESSION['id'], 
        $income['name'],
        $income['amount'],
        $income['income_date']
    );
    
    if (!$save_stmt->execute()) {
        // Check if already in history
        if ($save_stmt->errno == 1062) { // Duplicate entry error
            echo json_encode(['success' => true]);
            exit;
        }
        throw new Exception('Eroare la salvarea în istoric: ' . $save_stmt->error);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Error in add_to_history.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 