<?php
session_start();

// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'venituri_error.log');

header('Content-Type: application/json');

require_once 'database/db.php';

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Utilizatorul nu este autentificat'
    ]));
}

// Verifică dacă cererea este de tip POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Metoda HTTP invalidă'
    ]));
}

// Preia și validează datele
$name = trim($_POST['name'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$income_date = $_POST['income_date'] ?? '';

// Validare
if (empty($name)) {
    die(json_encode([
        'success' => false,
        'message' => 'Numele venitului este obligatoriu'
    ]));
}

if ($amount <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'Suma trebuie să fie mai mare decât 0'
    ]));
}

if (empty($income_date) || !strtotime($income_date)) {
    die(json_encode([
        'success' => false,
        'message' => 'Data venitului este invalidă'
    ]));
}

try {
    // Pregătește și execută query-ul
    $stmt = $conn->prepare("INSERT INTO incomes (user_id, name, amount, income_date) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Eroare la pregătirea interogării: " . $conn->error);
    }
    
    $stmt->bind_param("isds", $_SESSION['id'], $name, $amount, $income_date);
    
    if ($stmt->execute()) {
        // Returnează răspunsul de succes
        echo json_encode([
            'success' => true,
            'message' => 'Venitul a fost adăugat cu succes',
            'income_id' => $stmt->insert_id
        ]);
    } else {
        // Returnează eroarea
        throw new Exception("Eroare la executarea interogării: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log the error
    error_log("Error in add_income.php: " . $e->getMessage());
    
    // Returnează eroarea
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la adăugarea venitului: ' . $e->getMessage()
    ]);
}

$conn->close(); 