<?php
session_start();
require_once 'database/db.php';
require_once 'sesion-check/check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal_id = intval($_POST['goal_id']);
    $amount = floatval($_POST['amount']);
    
    // Validare sumă
    if ($amount <= 0) {
        $_SESSION['error_message'] = "Suma trebuie să fie mai mare decât 0!";
        header("Location: goals.php");
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Verificăm dacă obiectivul există și aparține utilizatorului
        $check_sql = "SELECT target_amount, current_amount FROM goals WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $goal_id, $_SESSION['id']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Obiectivul nu a fost găsit sau nu ai permisiunea să adaugi contribuții!");
        }
        
        $goal = $result->fetch_assoc();
        
        // Verificăm dacă obiectivul este deja îndeplinit
        if ($goal['current_amount'] >= $goal['target_amount']) {
            throw new Exception("Nu poți adăuga contribuții la un obiectiv deja îndeplinit!");
        }
        
        $remaining = $goal['target_amount'] - $goal['current_amount'];
        
        // Verificăm dacă suma nu depășește ce mai e necesar
        if ($amount > $remaining) {
            $_SESSION['error_message'] = "Suma introdusă depășește suma rămasă necesară pentru acest obiectiv!";
            header("Location: goals.php");
            exit;
        }
        
        // Adăugăm contribuția
        $insert_sql = "INSERT INTO goal_contributions (goal_id, amount, created_at, type) VALUES (?, ?, NOW(), 'add')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("id", $goal_id, $amount);
        $insert_stmt->execute();
        
        // Actualizăm suma totală din obiectiv
        $update_sql = "UPDATE goals SET current_amount = current_amount + ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $amount, $goal_id);
        $update_stmt->execute();
        
        // Verificăm și actualizăm milestone-urile
        $new_amount = $goal['current_amount'] + $amount;
        $milestone_sql = "UPDATE goal_milestones 
                         SET is_completed = 1, completed_at = NOW() 
                         WHERE goal_id = ? AND target_amount <= ? AND is_completed = 0";
        $milestone_stmt = $conn->prepare($milestone_sql);
        $milestone_stmt->bind_param("id", $goal_id, $new_amount);
        $milestone_stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Contribuția a fost adăugată cu succes!";
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