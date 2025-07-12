<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['income_id']) || !isset($_POST['income_date'])) {
        throw new Exception('Toate câmpurile sunt obligatorii.');
    }

    $income_id = $_POST['income_id'];
    $income_date = $_POST['income_date'];
    
    // Validate that the date is not in the past
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Set time to start of day
    
    $new_date = new DateTime($income_date);
    $new_date->setTime(0, 0, 0);
    
    if ($new_date < $today) {
        throw new Exception('Nu puteți seta o dată anterioară zilei curente.');
    }
    
    // Update the income date
    $sql = "UPDATE incomes SET income_date = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Eroare la pregătirea actualizării: ' . $conn->error);
    }
    
    $stmt->bind_param("sii", $income_date, $income_id, $_SESSION['id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Eroare la actualizarea datei: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Venitul nu a fost găsit sau nu aveți permisiunea de a-l modifica.');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Error in quick_edit_income.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 