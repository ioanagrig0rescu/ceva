<?php
session_start();
include 'database/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header for JSON response
header('Content-Type: application/json');

// Log the request data
error_log("POST data received: " . print_r($_POST, true));

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Nu sunteți autentificat']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă']);
    exit;
}

// Verifică dacă toate câmpurile necesare sunt prezente
if (!isset($_POST['category_id']) || !isset($_POST['amount']) || !isset($_POST['description']) || !isset($_POST['date'])) {
    error_log("Missing fields: " . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Toate câmpurile sunt obligatorii']);
    exit;
}

$user_id = $_SESSION['id'];
$category_id = intval($_POST['category_id']);
$amount = floatval($_POST['amount']);
$description = trim($_POST['description']);
$date = $_POST['date'];

// Log the processed data
error_log("Processed data: user_id=$user_id, category_id=$category_id, amount=$amount, description=$description, date=$date");

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
    // Verifică dacă categoria există și aparține utilizatorului sau este o categorie predefinită
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Invalid category: category_id=$category_id, user_id=$user_id");
        echo json_encode(['success' => false, 'message' => 'Categorie invalidă']);
        exit;
    }

    // Inserează cheltuiala
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $user_id, $category_id, $amount, $description, $date);
    
    if ($stmt->execute()) {
        error_log("Expense added successfully: " . $stmt->insert_id);
        echo json_encode(['success' => true, 'message' => 'Cheltuiala a fost salvată cu succes']);
    } else {
        error_log("Database error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Eroare la salvarea cheltuielii: ' . $stmt->error]);
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare la salvarea cheltuielii: ' . $e->getMessage()]);
} 