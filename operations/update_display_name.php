<?php
session_start();
include '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    $display_name = trim($_POST['display_name']);
    
    // Update display name in JSON file
    $names_file = '../data/display_names.json';
    $display_names = file_exists($names_file) ? json_decode(file_get_contents($names_file), true) : [];
    $display_names = is_array($display_names) ? $display_names : [];
    
    // If display name wasn't changed, redirect back
    if (isset($display_names[$username]) && $display_names[$username] === $display_name) {
        $_SESSION['message'] = "Numele afișat nu a fost modificat.";
        $_SESSION['message_type'] = "info";
        header("Location: ../edit_account.php");
        exit();
    }
    
    $display_names[$username] = $display_name;
    
    if (file_put_contents($names_file, json_encode($display_names, JSON_PRETTY_PRINT))) {
        $_SESSION['display_name'] = $display_name;
        $_SESSION['message'] = "Numele afișat a fost actualizat cu succes!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "A apărut o eroare la actualizarea numelui afișat!";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: ../edit_account.php");
    exit();
} else {
    header("Location: ../edit_account.php");
    exit();
} 