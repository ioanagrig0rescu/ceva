<?php
session_start();
include 'database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['goal_id'])) {
    $goal_id = intval($_POST['goal_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete milestones first (foreign key constraint will handle this automatically,
        // but we do it explicitly for clarity and to ensure proper order)
        $delete_milestones_sql = "DELETE FROM goal_milestones WHERE goal_id = ?";
        $delete_milestones_stmt = $conn->prepare($delete_milestones_sql);
        $delete_milestones_stmt->bind_param("i", $goal_id);
        $delete_milestones_stmt->execute();
        
        // Delete contributions
        $delete_contributions_sql = "DELETE FROM goal_contributions WHERE goal_id = ?";
        $delete_contributions_stmt = $conn->prepare($delete_contributions_sql);
        $delete_contributions_stmt->bind_param("i", $goal_id);
        $delete_contributions_stmt->execute();
        
        // Delete goal
        $delete_goal_sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
        $delete_goal_stmt = $conn->prepare($delete_goal_sql);
        $delete_goal_stmt->bind_param("ii", $goal_id, $_SESSION['id']);
        $delete_goal_stmt->execute();
        
        if ($delete_goal_stmt->affected_rows === 0) {
            throw new Exception("Obiectivul nu a fost găsit sau nu ai permisiunea să îl ștergi!");
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Obiectivul a fost șters cu succes!";
        header("Location: goals.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: goals.php");
        exit;
    }
} else {
    header("Location: goals.php");
    exit;
}
?> 