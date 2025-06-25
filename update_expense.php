<?php
session_start();
require_once 'database/db.php';
require_once 'sesion-check/check.php';

header('Content-Type: application/json');

try {
    // Verificăm dacă avem toate datele necesare
    if (!isset($_POST['id'], $_POST['category_id'], $_POST['amount'], $_POST['description'], $_POST['date'])) {
        throw new Exception('Toate câmpurile sunt obligatorii');
    }

    // Sanitizăm și validăm datele
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);

    // Validări suplimentare
    if (!$id || !$category_id || !$amount || !$description || !$date) {
        throw new Exception('Date invalide');
    }

    if ($amount <= 0) {
        throw new Exception('Suma trebuie să fie mai mare decât 0');
    }

    if (!strtotime($date)) {
        throw new Exception('Data invalidă');
    }

    // Verificăm dacă cheltuiala aparține utilizatorului curent
    $check_stmt = $conn->prepare("SELECT user_id FROM expenses WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Cheltuiala nu a fost găsită');
    }

    $expense = $result->fetch_assoc();
    if ($expense['user_id'] != $_SESSION['id']) {
        throw new Exception('Nu aveți permisiunea să modificați această cheltuială');
    }

    // Actualizăm cheltuiala
    $update_stmt = $conn->prepare("
        UPDATE expenses 
        SET category_id = ?, amount = ?, description = ?, date = ? 
        WHERE id = ? AND user_id = ?
    ");
    $update_stmt->bind_param("idssii", $category_id, $amount, $description, $date, $id, $_SESSION['id']);

    if (!$update_stmt->execute()) {
        throw new Exception('Eroare la actualizarea cheltuielii');
    }

    // Returnăm răspuns de succes
    echo json_encode([
        'success' => true,
        'message' => 'Cheltuiala a fost actualizată cu succes'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 