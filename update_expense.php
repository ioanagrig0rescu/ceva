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

// Verifică dacă toate câmpurile necesare sunt prezente
if (!isset($_POST['id']) || !isset($_POST['category_id']) || !isset($_POST['amount']) || !isset($_POST['description']) || !isset($_POST['date'])) {
    echo json_encode(['success' => false, 'message' => 'Toate câmpurile sunt obligatorii']);
    exit;
}

$user_id = $_SESSION['id'];
$expense_id = $_POST['id'];
$category_id = $_POST['category_id'];
$amount = floatval($_POST['amount']);
$description = trim($_POST['description']);
$date = $_POST['date'];

// Validare date
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Suma trebuie să fie mai mare decât 0']);
    exit;
}

if (strlen($description) < 1 || strlen($description) > 255) {
    echo json_encode(['success' => false, 'message' => 'Descrierea trebuie să aibă între 1 și 255 caractere']);
    exit;
}

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

    // Verifică dacă categoria există și aparține utilizatorului sau este predefinită
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Categorie invalidă']);
        exit;
    }

    // Actualizează cheltuiala
    $stmt = $conn->prepare("UPDATE expenses SET category_id = ?, amount = ?, description = ?, date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("idssii", $category_id, $amount, $description, $date, $expense_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cheltuiala a fost actualizată cu succes']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea cheltuielii']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la actualizarea cheltuielii']);
}
?> 