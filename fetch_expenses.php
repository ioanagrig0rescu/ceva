<?php
session_start();
include 'sesion-check/check.php';
include 'database/db.php';
if (!isset($_SESSION['id']) || !isset($_POST['date'])) {
    echo json_encode([]);
    exit;
}
$date = $_POST['date'];
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? AND expense_date = ?");
$stmt->bind_param("ss", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($expenses);
?>