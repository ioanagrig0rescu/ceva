<?php
// Asigură-te că nu există spații sau linii goale înainte de <?php
session_start();
ini_set('display_errors', 0); // Dezactivăm afișarea erorilor
ini_set('display_startup_errors', 0);
error_reporting(0);

// Set proper headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
mb_internal_encoding('UTF-8');

// Verifică dacă există erori de includere
try {
    require_once 'sesion-check/check.php';
    require_once 'database/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Eroare la încărcarea dependințelor']);
    exit;
}

// Set UTF-8 for database connection
mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in and has a valid ID
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nu sunteți autentificat corect. Vă rugăm să vă reconectați.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    // Validate required fields
    if (!isset($_POST['date'], $_POST['amount'], $_POST['category_id'], $_POST['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lipsesc câmpuri obligatorii']);
        exit;
    }

    // Sanitize and validate input
    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $description = mb_convert_encoding($_POST['description'], 'UTF-8', 'UTF-8');
    
    // Check if the date is in the future
    if (strtotime($date) > strtotime(date('Y-m-d'))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nu puteți adăuga cheltuieli pentru date viitoare']);
        exit;
    }
    
    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Suma invalidă']);
        exit;
    }

    // Insert the expense using prepared statement
    $sql = "INSERT INTO expenses (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Eroare la pregătirea interogării']);
        exit;
    }

    $stmt->bind_param("iidss", $_SESSION['id'], $category_id, $amount, $description, $date);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true, 
            'message' => 'Cheltuiala a fost salvată cu succes',
            'expense_id' => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Eroare la salvarea cheltuielii']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cerere invalidă']);
}

$conn->close();
?>