<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include 'sesion-check/check.php';
require_once 'database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['id']; // Asigură-te că ai user_id în sesiune
    $date = $_POST['expense_date'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO expenses (user_id, expense_date, category, amount, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $user_id, $date, $category, $amount, $notes);

    if ($stmt->execute()) {
        header("Location: new.php?success=1");
        exit;
    } else {
        echo "Eroare la salvare: " . $stmt->error;
    }
}
?>