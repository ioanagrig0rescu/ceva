<?php
session_start();
include 'sesion-check/check.php';
include 'database/db.php';
if (!isset($_GET['id'])) exit('Lipsește ID-ul');
$id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['id']);
$stmt->execute();
header("Location: new.php");
exit;