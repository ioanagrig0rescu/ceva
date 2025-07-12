<?php
session_start();
include '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_username = $_SESSION['username'];
    $new_username = trim($_POST['username']);
    
    // Verifică formatul username-ului
    if (!preg_match('/^[a-z0-9_]+$/', $new_username)) {
        $_SESSION['message'] = "Numele de utilizator poate conține doar litere mici, cifre și caracterul underscore (_).";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php");
        exit();
    }
    
    // If username wasn't changed, redirect back
    if ($new_username === $old_username) {
        $_SESSION['message'] = "Numele de utilizator nu a fost modificat.";
        $_SESSION['message_type'] = "info";
        header("Location: ../edit_account.php");
        exit();
    }
    
    // Check if new username is already taken
    $check_sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $new_username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $_SESSION['message'] = "Acest nume de utilizator este deja folosit!";
        $_SESSION['message_type'] = "danger";
        header("Location: ../edit_account.php");
        exit();
    }
    
    // Update username in database
    $sql = "UPDATE users SET username = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_username, $old_username);
    
    if ($stmt->execute()) {
        // Update display names file
        $names_file = '../data/display_names.json';
        $display_names = file_exists($names_file) ? json_decode(file_get_contents($names_file), true) : [];
        $display_names = is_array($display_names) ? $display_names : [];
        
        if (isset($display_names[$old_username])) {
            $display_names[$new_username] = $display_names[$old_username];
            unset($display_names[$old_username]);
            file_put_contents($names_file, json_encode($display_names, JSON_PRETTY_PRINT));
        }
        
        // Update session
        $_SESSION['username'] = $new_username;
        
        $_SESSION['message'] = "Numele de utilizator a fost actualizat cu succes!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "A apărut o eroare la actualizarea numelui de utilizator!";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: ../edit_account.php");
    exit();
} else {
    header("Location: ../edit_account.php");
    exit();
} 