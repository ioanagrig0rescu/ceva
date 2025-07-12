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
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = "Parolele noi nu se potrivesc!";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php#security");
        exit();
    }
    
    // Validate password length
    if (strlen($new_password) < 6) {
        $_SESSION['message'] = "Noua parolă trebuie să aibă cel puțin 6 caractere!";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php#security");
        exit();
    }
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['message'] = "Parola actuală este incorectă!";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php#security");
        exit();
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $username);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Parola a fost actualizată cu succes!";
        $_SESSION['message_type'] = "success";
        header("Location: ../edit_account.php#security");
    } else {
        $_SESSION['message'] = "A apărut o eroare la actualizarea parolei!";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php#security");
    }
} else {
    header("Location: ../edit_account.php");
}
exit(); 