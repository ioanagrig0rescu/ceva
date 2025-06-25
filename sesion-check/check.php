<?php
// Disable error display but keep error logging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'A apărut o eroare internă']));
    }
    return false;
});

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        die(json_encode(['success' => false, 'message' => 'Sesiune expirată']));
    } else {
        header("Location: /account/login.php");
        exit;
    }
}
?>