<?php
session_start();
include '../database/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Adresa de email nu este validă.';
        echo json_encode($response);
        exit();
    }
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!password_verify($currentPassword, $row['password'])) {
            $response['message'] = 'Parola actuală este incorectă.';
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = 'Eroare la verificarea parolei.';
        echo json_encode($response);
        exit();
    }
    
    // Update email
    $sql = "UPDATE users SET email = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $username);
    
    if (!$stmt->execute()) {
        $response['message'] = 'Eroare la actualizarea emailului.';
        echo json_encode($response);
        exit();
    }
    
    // Update password if provided
    if (!empty($newPassword)) {
        // Validate new password
        if (strlen($newPassword) < 8) {
            $response['message'] = 'Noua parolă trebuie să aibă cel puțin 8 caractere.';
            echo json_encode($response);
            exit();
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashedPassword, $username);
        
        if (!$stmt->execute()) {
            $response['message'] = 'Eroare la actualizarea parolei.';
            echo json_encode($response);
            exit();
        }
    }
    
    $_SESSION['email'] = $email;
    $response['success'] = true;
    $response['message'] = 'Profilul a fost actualizat cu succes.';
} else {
    $response['message'] = 'Metoda de request invalidă.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 