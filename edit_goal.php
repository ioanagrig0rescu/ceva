<?php
session_start();
include 'database/db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug log for request method and POST data
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));
error_log("Session Data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_goal') {
    $goal_id = intval($_POST['goal_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $target_amount = floatval($_POST['target_amount']);
    $current_amount = floatval($_POST['current_amount']);
    $deadline = $conn->real_escape_string($_POST['deadline']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    // Debug log
    error_log("Processing goal edit - Details:");
    error_log("Goal ID: $goal_id");
    error_log("User ID: {$_SESSION['id']}");
    error_log("Name: $name");
    error_log("Target Amount: $target_amount");
    error_log("Current Amount: $current_amount");
    error_log("Deadline: $deadline");
    error_log("Category ID: " . ($category_id ?? 'null'));
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First check if the goal exists and belongs to the user
        $check_sql = "SELECT id FROM goals WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $goal_id, $_SESSION['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        error_log("Goal check result rows: " . $check_result->num_rows);
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Obiectivul nu a fost găsit sau nu ai permisiunea să îl editezi!");
        }
        
        // Update goal
        $sql = "UPDATE goals 
                SET name = ?, target_amount = ?, current_amount = ?, deadline = ?, category_id = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddsiii", $name, $target_amount, $current_amount, $deadline, $category_id, $goal_id, $_SESSION['id']);
        $stmt->execute();

        // Delete existing milestones
        $delete_milestones = "DELETE FROM goal_milestones WHERE goal_id = ?";
        $delete_stmt = $conn->prepare($delete_milestones);
        $delete_stmt->bind_param("i", $goal_id);
        $delete_stmt->execute();

        // Add new milestones
        if (isset($_POST['milestone_amounts']) && is_array($_POST['milestone_amounts'])) {
            error_log("Processing milestones: " . print_r($_POST['milestone_amounts'], true));
            
            $insert_milestone = "INSERT INTO goal_milestones (goal_id, target_amount, description) VALUES (?, ?, ?)";
            $milestone_stmt = $conn->prepare($insert_milestone);

            foreach ($_POST['milestone_amounts'] as $key => $amount) {
                if (!empty($amount)) {
                    $amount = floatval($amount);
                    $description = isset($_POST['milestone_descriptions'][$key]) ? 
                                 $conn->real_escape_string($_POST['milestone_descriptions'][$key]) : '';
                    
                    error_log("Adding milestone - Amount: $amount, Description: $description");
                    
                    $milestone_stmt->bind_param("ids", $goal_id, $amount, $description);
                    $milestone_stmt->execute();
                }
            }

            // Update milestone completion status
            $update_completion = "UPDATE goal_milestones 
                                SET is_completed = CASE 
                                    WHEN target_amount <= ? THEN 1 
                                    ELSE 0 
                                END,
                                completed_at = CASE 
                                    WHEN target_amount <= ? AND is_completed = 0 THEN CURRENT_TIMESTAMP
                                    WHEN target_amount > ? THEN NULL
                                    ELSE completed_at
                                END
                                WHERE goal_id = ?";
            $completion_stmt = $conn->prepare($update_completion);
            $completion_stmt->bind_param("dddi", $current_amount, $current_amount, $current_amount, $goal_id);
            $completion_stmt->execute();
        }
        
        $conn->commit();
        error_log("Goal edit completed successfully");
        header("Location: goals.php?success=2");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Goal edit error - " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        header("Location: goals.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    error_log("Invalid request to edit_goal.php");
    header("Location: goals.php");
    exit;
}
?> 