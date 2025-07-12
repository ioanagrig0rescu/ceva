<?php
session_start();
include 'database/db.php';

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    $_SESSION['error_message'] = "Trebuie să fii autentificat pentru a efectua această acțiune!";
    header("Location: goals.php");
    exit;
}

// Verificăm dacă avem un ID valid
if (!isset($_POST['goal_id']) || !is_numeric($_POST['goal_id'])) {
    $_SESSION['error_message'] = "ID obiectiv invalid!";
    header("Location: goals.php");
    exit;
}

$goal_id = intval($_POST['goal_id']);

// Start transaction
$conn->begin_transaction();

try {
    // Mai întâi verificăm dacă obiectivul există și aparține utilizatorului curent
    $check_sql = "SELECT id FROM goals WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $goal_id, $_SESSION['id']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Obiectivul nu a fost găsit sau nu ai permisiunea să îl ștergi!");
    }

    // Ștergem milestone-urile
    $delete_milestones_sql = "DELETE FROM goal_milestones WHERE goal_id = ?";
    $delete_milestones_stmt = $conn->prepare($delete_milestones_sql);
    $delete_milestones_stmt->bind_param("i", $goal_id);
    $delete_milestones_stmt->execute();

    // Ștergem contribuțiile
    $delete_contributions_sql = "DELETE FROM goal_contributions WHERE goal_id = ?";
    $delete_contributions_stmt = $conn->prepare($delete_contributions_sql);
    $delete_contributions_stmt->bind_param("i", $goal_id);
    $delete_contributions_stmt->execute();

    // Ștergem obiectivul
    $delete_goal_sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
    $delete_goal_stmt = $conn->prepare($delete_goal_sql);
    $delete_goal_stmt->bind_param("ii", $goal_id, $_SESSION['id']);
    $delete_goal_stmt->execute();

    // Verificăm dacă s-a șters cu succes
    if ($delete_goal_stmt->affected_rows === 0) {
        throw new Exception("Nu s-a putut șterge obiectivul!");
    }

    // Commit transaction
    $conn->commit();
    $_SESSION['success_message'] = "Obiectivul a fost șters cu succes!";

} catch (Exception $e) {
    // Rollback la eroare
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
} finally {
    // Închide toate statement-urile
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($delete_milestones_stmt)) $delete_milestones_stmt->close();
    if (isset($delete_contributions_stmt)) $delete_contributions_stmt->close();
    if (isset($delete_goal_stmt)) $delete_goal_stmt->close();
}

// Redirecționăm înapoi la pagina cu obiective
header("Location: goals.php");
exit;
?> 