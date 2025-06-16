<?php
session_start();
include 'sesion-check/check.php';
include 'database/db.php';
if (!isset($_GET['id'])) exit('Lipsește ID-ul');
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();
if (!$expense) exit('Cheltuială inexistentă');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $notes = $_POST['notes'];
    $stmt = $conn->prepare("UPDATE expenses SET category=?, amount=?, notes=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sdssi", $category, $amount, $notes, $id, $_SESSION['id']);
    $stmt->execute();
    header("Location: new.php");
    exit;
}
?>
<form method="POST">
  <label>Categorie: <input name="category" value="<?= htmlspecialchars($expense['category']) ?>"></label><br>
  <label>Suma: <input name="amount" type="number" step="0.01" value="<?= $expense['amount'] ?>"></label><br>
  <label>Notițe: <textarea name="notes"><?= htmlspecialchars($expense['notes']) ?></textarea></label><br>
  <button type="submit">Salvează</button>
</form>