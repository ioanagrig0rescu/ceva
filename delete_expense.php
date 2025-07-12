<?php
session_start();
include 'database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Nu sunteți autentificat']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID-ul cheltuielii nu a fost specificat']);
    exit;
}

$user_id = $_SESSION['id'];
$expense_id = $_POST['id'];

try {
    // Verifică dacă cheltuiala există și aparține utilizatorului
    $stmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $expense_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Cheltuiala nu a fost găsită sau nu vă aparține']);
        exit;
    }

    // Șterge cheltuiala
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $expense_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cheltuiala a fost ștearsă cu succes']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eroare la ștergerea cheltuielii']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la ștergerea cheltuielii']);
}
?>