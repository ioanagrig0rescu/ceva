<?php
// Disable error display but keep error logging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'A apărut o eroare internă']));
});

// Set exception handler
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'A apărut o eroare internă']));
});

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
mb_internal_encoding('UTF-8');

try {
    require_once 'sesion-check/check.php';
    require_once 'database/db.php';
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Eroare la încărcarea dependințelor']));
}

// Set UTF-8 for database connection
mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Nu sunteți autentificat']));
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Metoda HTTP invalidă']));
}

// Validate required fields
if (!isset($_POST['id'], $_POST['date'], $_POST['amount'], $_POST['category_id'], $_POST['description'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Lipsesc câmpuri obligatorii']));
}

// Sanitize and validate input
$id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
$date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
$amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

// Validate amount
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Suma invalidă']));
}

// Verify that the expense belongs to the current user
$check_sql = "SELECT user_id FROM expenses WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Cheltuiala nu a fost găsită']));
}

$expense = $result->fetch_assoc();
if ($expense['user_id'] != $_SESSION['id']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Nu aveți permisiunea să modificați această cheltuială']));
}

$check_stmt->close();

// Update the expense
$sql = "UPDATE expenses SET category_id = ?, amount = ?, description = ?, date = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Eroare la pregătirea interogării']));
}

$stmt->bind_param("idssii", $category_id, $amount, $description, $date, $id, $_SESSION['id']);

if ($stmt->execute()) {
    // Get the updated expense data
    $select_sql = "SELECT e.*, c.name as category_name 
                  FROM expenses e 
                  LEFT JOIN categories c ON e.category_id = c.id 
                  WHERE e.id = ?";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("i", $id);
    $select_stmt->execute();
    $updated_expense = $select_stmt->get_result()->fetch_assoc();
    
    http_response_code(200);
    die(json_encode([
        'success' => true,
        'message' => 'Cheltuiala a fost actualizată cu succes',
        'data' => [
            'id' => $id,
            'date' => $date,
            'amount' => $amount,
            'description' => $description,
            'category_id' => $category_id,
            'category_name' => $updated_expense['category_name']
        ]
    ]));
} else {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Eroare la actualizarea cheltuielii']));
}

$stmt->close();
$conn->close();
?>