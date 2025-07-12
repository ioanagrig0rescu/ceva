<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    $current_username = $_SESSION['username'];
    
    // Dacă username-ul este același cu cel curent, nu este considerat duplicat
    if ($username === $current_username) {
        echo json_encode(['exists' => false]);
        exit();
    }
    
    // Verifică dacă username-ul există în baza de date
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['exists' => $row['count'] > 0]);
} else {
    echo json_encode(['error' => 'No username provided']);
} 