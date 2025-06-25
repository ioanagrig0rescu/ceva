<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Set UTF-8 encoding for all output
mb_internal_encoding('UTF-8');

include '../database/db.php';

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilizatorul nu este autentificat']);
    exit;
}

// Preia și validează datele
$user_id = $_SESSION['user_id'];
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
$date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);

// Validare
if (!$category_id || !$amount || !$date) {
    echo json_encode(['success' => false, 'message' => 'Date invalide']);
    exit;
}

// Pregătește și execută query-ul
$sql = "INSERT INTO expenses (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iidss", $user_id, $category_id, $amount, $description, $date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cheltuiala a fost salvată cu succes']);
} else {
    echo json_encode(['success' => false, 'message' => 'Eroare la salvarea cheltuielii: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 