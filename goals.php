<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
include 'database/db.php';

// Array cu iconițe pentru fiecare categorie
$category_icons = [
    'transport' => ['icon' => 'fa-car', 'color' => '#4CAF50'],
    'calatorii' => ['icon' => 'fa-plane', 'color' => '#2196F3'],
    'familie' => ['icon' => 'fa-users', 'color' => '#E91E63'],
    'casa' => ['icon' => 'fa-home', 'color' => '#795548'],
    'locuinta' => ['icon' => 'fa-home', 'color' => '#795548'],
    'educatie' => ['icon' => 'fa-graduation-cap', 'color' => '#9C27B0'],
    'sanatate' => ['icon' => 'fa-heartbeat', 'color' => '#F44336'],
    'tehnologie' => ['icon' => 'fa-laptop', 'color' => '#607D8B'],
    'investitii' => ['icon' => 'fa-chart-line', 'color' => '#FF9800'],
    'divertisment' => ['icon' => 'fa-smile', 'color' => '#00BCD4'],
    'hobby' => ['icon' => 'fa-palette', 'color' => '#8BC34A'],
    'urgente' => ['icon' => 'fa-exclamation-circle', 'color' => '#f44336'],
    'vacanta' => ['icon' => 'fa-umbrella-beach', 'color' => '#2196F3'],
    'default' => ['icon' => 'fa-piggy-bank', 'color' => '#9E9E9E']
];

// Funcție pentru a obține iconița și culoarea pentru o categorie
function getCategoryInfo($category_name, $category_icons) {
    if (empty($category_name)) {
        return $category_icons['default'];
    }

    // Convertim numele categoriei la lowercase și eliminăm diacriticele
    $normalized_name = strtolower(trim($category_name));
    $normalized_name = str_replace(
        ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
        ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
        $normalized_name
    );
    
    // Verificăm dacă există o potrivire exactă
    foreach ($category_icons as $key => $info) {
        if (strpos($normalized_name, $key) !== false) {
            return $info;
        }
    }
    
    // Dacă nu găsim o potrivire, returnăm valorile default
    return $category_icons['default'];
}

// Funcție pentru a obține statisticile generale ale obiectivelor
function getGoalsStats($conn, $user_id, $year) {
    $stats = [
        'total' => 0,
        'completed' => 0,
        'inactive' => []
    ];
    
    // Obținem toate obiectivele pentru anul specificat
    $sql = "SELECT id, name, current_amount, target_amount, 
            DATEDIFF(NOW(), (SELECT MAX(created_at) FROM goal_contributions 
                            WHERE goal_id = goals.id AND type = 'add')) as days_since_last_contribution
            FROM goals 
            WHERE user_id = ? AND YEAR(deadline) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($goal = $result->fetch_assoc()) {
        $stats['total']++;
        if ($goal['current_amount'] >= $goal['target_amount']) {
            $stats['completed']++;
        }
        
        // Verificăm dacă nu s-a contribuit în ultimele 2 săptămâni
        if ($goal['days_since_last_contribution'] > 14 || $goal['days_since_last_contribution'] === null) {
            $stats['inactive'][] = [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'days' => $goal['days_since_last_contribution'] ?? 'Nicio contribuție'
            ];
        }
    }
    
    return $stats;
}

// Funcție pentru a verifica obiectivele cu deadline apropiat și fără progres
function getApproachingDeadlines($conn, $user_id) {
    $warnings = [];
    
    // Obiective cu deadline apropiat sau depășit
    $sql = "SELECT id, name, target_amount, current_amount, deadline,
            DATEDIFF(deadline, CURDATE()) as days_remaining,
            CASE 
                WHEN current_amount = 0 THEN 'no_progress'
                WHEN DATEDIFF(deadline, CURDATE()) < 0 THEN 'overdue'
                WHEN DATEDIFF(deadline, CURDATE()) <= 14 THEN 'deadline_soon'
                ELSE 'normal'
            END as warning_type
            FROM goals 
            WHERE user_id = ? 
            AND (
                (current_amount < target_amount AND DATEDIFF(deadline, CURDATE()) <= 14)
                OR current_amount = 0
                OR (current_amount < target_amount AND DATEDIFF(deadline, CURDATE()) < 0)
            )
            ORDER BY 
                CASE 
                    WHEN DATEDIFF(deadline, CURDATE()) < 0 THEN 0
                    WHEN current_amount = 0 THEN 1 
                    ELSE 2 
                END,
                days_remaining ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($goal = $result->fetch_assoc()) {
        $remaining_amount = $goal['target_amount'] - $goal['current_amount'];
        $progress_percent = ($goal['current_amount'] / $goal['target_amount']) * 100;
        
        $warnings[] = [
            'id' => $goal['id'],
            'name' => $goal['name'],
            'days_remaining' => $goal['days_remaining'],
            'remaining_amount' => $remaining_amount,
            'progress_percent' => $progress_percent,
            'deadline' => $goal['deadline'],
            'warning_type' => $goal['warning_type']
        ];
    }
    
    return $warnings;
}

// Obținem anul curent și statisticile
$current_year = date('Y');
$goals_stats = getGoalsStats($conn, $_SESSION['id'], $current_year);

// Obținem avertismentele pentru deadline-uri apropiate
$deadline_warnings = getApproachingDeadlines($conn, $_SESSION['id']);

// Verificăm dacă există tabela goals
$sql = "SHOW TABLES LIKE 'goals'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // Creăm tabela doar dacă nu există
    $sql = "CREATE TABLE IF NOT EXISTS goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        target_amount DECIMAL(10,2) NOT NULL,
        current_amount DECIMAL(10,2) DEFAULT 0.00,
        deadline DATE NOT NULL,
        category_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )";
    $conn->query($sql);
}

// Procesăm adăugarea unui nou obiectiv
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_goal') {
    $name = $conn->real_escape_string($_POST['name']);
    $target_amount = floatval($_POST['target_amount']);
    $deadline = $conn->real_escape_string($_POST['deadline']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert goal
        $sql = "INSERT INTO goals (user_id, name, target_amount, deadline, category_id) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdsi", $_SESSION['id'], $name, $target_amount, $deadline, $category_id);
        $stmt->execute();
        $goal_id = $conn->insert_id;
        
        // Process milestones if any
        if (isset($_POST['milestone_amounts']) && is_array($_POST['milestone_amounts'])) {
            $milestone_sql = "INSERT INTO goal_milestones (goal_id, target_amount, description) VALUES (?, ?, ?)";
            $milestone_stmt = $conn->prepare($milestone_sql);
            
            foreach ($_POST['milestone_amounts'] as $key => $amount) {
                if (!empty($amount)) {
                    $amount = floatval($amount);
                    $description = isset($_POST['milestone_descriptions'][$key]) ? 
                                 $conn->real_escape_string($_POST['milestone_descriptions'][$key]) : null;
                    
                    $milestone_stmt->bind_param("ids", $goal_id, $amount, $description);
                    $milestone_stmt->execute();
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success_message'] = 'Obiectivul a fost adăugat cu succes!';
        header("Location: goals.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Eroare la salvarea obiectivului: " . $e->getMessage();
        header("Location: goals.php");
        exit;
    }
}

// Obținem toate obiectivele utilizatorului - optimizat cu LIMIT
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql = "SELECT g.*, c.name as category_name,
        (SELECT GROUP_CONCAT(
            CONCAT(m.target_amount, ':', COALESCE(m.description, ''), ':', m.is_completed)
            ORDER BY m.target_amount ASC
            SEPARATOR '|'
        ) FROM goal_milestones m WHERE m.goal_id = g.id) as milestones,
        CASE WHEN g.current_amount >= g.target_amount THEN 1 ELSE 0 END as is_completed
        FROM goals g 
        LEFT JOIN categories c ON g.category_id = c.id 
        WHERE g.user_id = ? 
        ORDER BY is_completed ASC, g.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $_SESSION['id'], $per_page, $offset);
$stmt->execute();
$goals = $stmt->get_result();

// Obținem numărul total de obiective pentru paginare
$count_sql = "SELECT COUNT(*) as total FROM goals WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $_SESSION['id']);
$count_stmt->execute();
$total_goals = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_goals / $per_page);

// Obținem toate categoriile pentru formular - folosim un singur query
$categories = [];
$categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);
while ($row = $categories_result->fetch_assoc()) {
    $categories[$row['id']] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .content-wrapper {
            flex: 1 0 auto;
            width: 100%;
            margin: 0 !important;
            padding: 80px 0 0 0 !important; /* Increased top padding for better spacing */
            display: flex;
            flex-direction: column;
        }

        .content-wrapper > .container {
            flex: 1 0 auto;
            padding-bottom: 0 !important;
            margin-bottom: 0 !important;
        }

        .content-wrapper > *:last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        footer {
            flex-shrink: 0;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            position: relative;
            left: 50%;
            right: 50%;
            transform: translateX(-50%);
            background-color: #f8f9fa;
            padding: 0;
            margin-top: auto;
            margin-bottom: 0;
        }

        footer .container-fluid {
            width: 100%;
            padding: 1rem;
            margin: 0;
        }

        footer .row {
            margin: 0;
            width: 100%;
        }

        footer .col {
            padding: 0.5rem;
        }

        /* Widget Styles */
        .widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .widget-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .widget-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2d3748;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .widget-body {
            padding: 1.5rem;
        }

        /* Statistics Grid */
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .statistic-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .statistic-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .notification-item.no_progress .notification-icon {
            color: #ff9800;
        }

        .notification-item.overdue .notification-icon {
            color: #f44336;
        }

        .notification-item.deadline_soon .notification-icon {
            color: #FFD700;
        }

        .notification-item.no_progress .notification-title,
        .notification-item.no_progress .notification-text {
            color: #e65100;
        }

        .notification-item.overdue .notification-title,
        .notification-item.overdue .notification-text {
            color: #d32f2f;
        }

        .notification-item.deadline_soon .notification-title,
        .notification-item.deadline_soon .notification-text {
            color: #f57f17;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .notification-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.2;
        }

        /* Notifications Container */
        .notifications-container {
            margin-top: 1.5rem;
        }

        .notifications-title {
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-item {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            gap: 0.75rem;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: rgba(255, 255, 255, 0.8);
        }

        .notification-item.no_progress {
            border-left: 4px solid #ff9800;
            background: rgba(255, 152, 0, 0.1);
        }

        .notification-item.overdue {
            border-left: 4px solid #f44336;
            background: rgba(244, 67, 54, 0.1);
        }

        .notification-item.deadline_soon {
            border-left: 4px solid #FFD700;
            background: rgba(255, 215, 0, 0.2);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .notification-date {
            font-size: 0.875rem;
            color: #64748b;
        }

        .notification-details {
            font-size: 0.875rem;
            color: #64748b;
        }

        .amount-info {
            margin-bottom: 0.75rem;
        }

        .progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #42a5f5;
            transition: width 0.6s ease;
        }

        .card {
            border: none;
            margin-bottom: 24px;
            -webkit-box-shadow: 0 0 13px 0 rgba(236,236,241,.44);
            box-shadow: 0 0 13px 0 rgba(236,236,241,.44);
            transition: transform 0.3s ease;
        }

        .goal-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 1rem;
            gap: 1.5rem;
        }

        .goal-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f8f9fa;
            color: #007bff;
            flex-shrink: 0;
        }

        .goal-content {
            flex-grow: 1;
        }

        .goal-content h5.card-title {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .amount {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .deadline, .category {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.3rem;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            margin-top: 0.5rem;
            background-color: #e9ecef;
        }

        .progress-bar {
            transition: width 0.6s ease;
            background-color: #007bff;
        }

        .add-goal-card {
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.05) 0%, rgba(66, 165, 245, 0.1) 100%);
            border: 2px dashed rgba(66, 165, 245, 0.3);
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 1.5rem;
            gap: 1.5rem;
            min-height: 100px;
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 1100px;
            margin: 2rem auto;
        }

        .add-goal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 165, 245, 0.15);
            border-color: rgba(66, 165, 245, 0.5);
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.1) 0%, rgba(66, 165, 245, 0.15) 100%);
        }

        .add-goal-card:hover .add-goal-icon {
            transform: scale(1.1) rotate(90deg);
            background: rgba(66, 165, 245, 0.15);
            color: #42a5f5;
        }

        .add-goal-card:hover .goal-content h5 {
            color: #42a5f5;
        }

        .add-goal-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(66, 165, 245, 0.1);
            color: #42a5f5B3;
            flex-shrink: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .add-goal-card .goal-content {
            flex: 1;
        }

        .add-goal-card .goal-content h5 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d3748;
            transition: color 0.3s ease;
        }

        .add-goal-card .goal-content p {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0;
            max-width: 80%;
        }

        .goal-actions {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .goal-actions button {
            padding: 0.4rem;
            border: none;
            background: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .goal-actions button i {
            font-size: 1.1rem;
        }

        .goal-actions .contribute-btn {
            color: #198754;
        }

        .goal-actions .contribute-btn:hover {
            color: #146c43;
            transform: scale(1.1);
        }

        .goal-actions .edit-btn {
            color: #0d6efd;
        }

        .goal-actions .edit-btn:hover {
            color: #0a58ca;
            transform: scale(1.1);
        }

        .goal-actions .delete-btn {
            color: #dc3545;
        }

        .goal-actions .delete-btn:hover {
            color: #bb2d3b;
            transform: scale(1.1);
        }

        .goal-actions .withdraw-btn {
            color: #ffc107;
        }

        .goal-actions .withdraw-btn:hover {
            color: #e0a800;
            transform: scale(1.1);
        }

        .goal-summary {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .contribution-form .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        .withdrawal-form .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        /* Form Styles */
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        /* Optimizări pentru performanță */
        .card {
            will-change: transform;
            transform: translateZ(0);
        }
        
        .progress-bar {
            will-change: width;
            transform: translateZ(0);
        }
        
        /* Stil pentru paginare */
        .pagination {
            margin-top: 2rem;
            justify-content: center;
        }
        
        .pagination .page-link {
            color: #007bff;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #0056b3;
        }

        .history-section {
            border-top: 1px solid rgba(0,0,0,.1);
        }

        .history-toggle {
            text-decoration: none;
            color: #6c757d;
            padding: 0.5rem 0;
            position: relative;
        }

        .history-toggle:hover {
            color: #0d6efd;
            background-color: rgba(13,110,253,.05);
        }

        .history-toggle:focus {
            box-shadow: none;
        }

        .history-toggle .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        .history-toggle.collapsed .fa-chevron-down {
            transform: rotate(0deg);
        }

        .history-toggle:not(.collapsed) .fa-chevron-down {
            transform: rotate(180deg);
        }

        .history-content {
            padding: 1rem;
            background-color: rgba(0,0,0,.01);
        }

        .history-content .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .history-content .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }

        .history-content .badge {
            font-weight: 500;
            font-size: 0.85rem;
        }

        .progress-container {
            position: relative;
            margin: 20px 0;
            overflow: visible;
        }

        .progress-wrapper {
            position: relative;
            height: 25px;
            margin-bottom: 25px;
            overflow: visible;
        }

        .progress {
            position: relative;
            width: 100%;
            background-color: #f8f9fa;
            border-radius: 15px;
            overflow: hidden !important;
            height: 25px;
        }

        .progress-bar {
            transition: width 0.6s ease;
            background-color: #007bff;
            height: 100%;
            border-radius: 15px;
        }

        .milestone-marker {
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            transform: translate(-50%, -50%);
            z-index: 2;
        }

        .milestone-dot {
            width: 12px;
            height: 12px;
            background-color: #6c757d;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }

        .milestone-marker.completed .milestone-dot {
            background-color: #ffc107;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }

        .milestone-marker:hover::after {
            content: attr(data-amount);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 3;
        }

        .milestone-messages {
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .milestone-message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .milestone-message i {
            font-size: 1.2rem;
        }

        .milestone-entries {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-top: 10px;
        }

        .milestone-entry {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .milestone-entry:last-child {
            margin-bottom: 0;
        }

        .milestone-entry .input-group {
            flex: 1;
        }

        .milestone-entry .form-control {
            border-radius: 4px;
        }

        .milestone-entry .btn-outline-danger {
            border-radius: 4px;
            padding: 0.375rem 0.75rem;
        }

        .remaining-amount-message {
            font-size: 0.9rem;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e9ecef;
            margin-top: 30px;
        }

        .remaining-amount-message > div {
            padding: 4px 0;
        }

        .remaining-amount-message i {
            font-size: 1.1rem;
        }

        .remaining-amount-message strong {
            color: inherit;
            font-weight: 600;
        }

        .goals-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .goals-summary h4 {
            color: #495057;
            margin-bottom: 15px;
        }

        .progress-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .stat-item {
            background: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .alerts-section {
            margin-top: 20px;
        }

        .alert-item {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert-item i {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .alert-item .alert-text {
            flex: 1;
            color: #856404;
        }

        .alert-item .alert-link {
            color: #0056b3;
            text-decoration: none;
            font-weight: 500;
        }

        .alert-item .alert-link:hover {
            text-decoration: underline;
        }

        .highlight-goal {
            animation: highlight-pulse 2s ease-in-out;
        }

        @keyframes highlight-pulse {
            0% { background-color: #fff3cd; }
            100% { background-color: transparent; }
        }

        .warning-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 4px;
            opacity: 1;
            transition: opacity 0.5s ease-in;
        }
        
        .warning-alert.no-progress {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .warning-alert.overdue {
            background-color: #fff0f0;
            border-left-color: #dc3545;
            color: #842029;
        }
        
        .warning-alert .progress {
            height: 10px;
            margin-top: 10px;
        }
        
        .warning-alert .deadline-info {
            color: #856404;
            font-weight: bold;
        }
        
        .warning-alert.no-progress .deadline-info {
            color: #721c24;
        }

        .warning-alert.overdue .deadline-info {
            color: #842029;
        }

        .warning-alert.overdue .progress-bar {
            background-color: #dc3545;
        }

        .warning-alert.overdue .warning-icon {
            color: #dc3545;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .warning-alert {
            animation: fadeIn 0.5s ease-in-out;
        }

        .warning-icon {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .no-progress .warning-icon {
            color: #dc3545;
        }
        
        .deadline-soon .warning-icon {
            color: #ffc107;
        }

        /* Stiluri pentru iconițe */
        .goal-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .goal-card:hover .goal-icon {
            transform: scale(1.1);
        }

        .card-header[role="button"] {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .card-header[role="button"]:hover {
            background-color: #198754 !important;
        }

        .card-header .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        .collapse.show + .card-header .fa-chevron-down {
            transform: rotate(180deg);
        }

        .completed-goals-section {
            margin-top: 2rem;
        }

        .completed-goals-header {
            background-color: #198754 !important;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .completed-goals-header:hover {
            background-color: #157347 !important;
        }

        .completed-goals-header .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        [data-bs-toggle="collapse"][aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .completed-goal-card {
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .completed-goal-card:hover {
            opacity: 1;
        }

        .completed-goal-card .goal-actions {
            opacity: 0.7;
        }

        .completed-goal-card:hover .goal-actions {
            opacity: 1;
        }

        /* Stil pentru paginare */
        // ... existing code ...

        .transition-transform {
            transition: transform 0.3s ease;
        }
        [aria-expanded="true"] .transition-transform {
            transform: rotate(180deg);
        }

        .completed-goals-header {
            background-color: #198754 !important;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .completed-goals-header:hover {
            background-color: #157347 !important;
        }

        .completed-goals-header .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        [data-bs-toggle="collapse"][aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .collapse-section {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .collapse-section.show {
            max-height: 2000px; /* valoare mare pentru a permite tot conținutul */
            transition: max-height 0.3s ease-in;
        }

        .rotate-icon {
            transform: rotate(180deg) !important;
            transition: transform 0.3s ease;
        }

        .history-toggle .fa-chevron-down,
        .completed-goals-header .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        /* Stiluri pentru footer în pagina goals */
        footer {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            position: relative;
            left: 50%;
            right: 50%;
            transform: translateX(-50%);
        }

        .content-wrapper {
            min-height: calc(100vh - 60px); /* ajustează valoarea în funcție de înălțimea footer-ului */
            padding-bottom: 60px; /* spațiu pentru footer */
            position: relative;
            overflow-x: hidden;
        }

        /* ... existing styles ... */

        /* Stiluri pentru footer în pagina goals */
        body {
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1 0 auto;
        }

        footer {
            flex-shrink: 0;
            width: 100%;
            background-color: #f8f9fa;
            margin-top: auto;
        }

        footer .container {
            max-width: 100%;
            padding-left: 0;
            padding-right: 0;
        }

        footer .row {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }

        footer .col {
            padding: 1rem;
        }

        /* Stiluri pentru footer */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1;
        }

        footer {
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #f8f9fa;
        }

        footer .container-fluid {
            margin: 0;
            padding: 0;
            width: 100%;
        }

        footer .row {
            margin: 0;
            padding: 1rem;
            width: 100%;
        }

        footer .col {
            padding: 0 1rem;
        }

        /* Adăugăm stilurile necesare pentru animație */
        .collapse-section {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .collapse-section.show {
            max-height: 2000px;
            transition: max-height 0.3s ease-in;
        }

        .history-toggle .fa-chevron-down {
            transition: transform 0.3s ease;
        }

        .history-toggle.collapsed .fa-chevron-down {
            transform: rotate(0deg);
        }

        .history-toggle:not(.collapsed) .fa-chevron-down,
        .fa-chevron-down.rotate-icon {
            transform: rotate(180deg);
        }

        /* Stiluri pentru navbar spacing */
        body {
            padding-top: 56px; /* Height of the navbar */
        }

        .content-wrapper {
            min-height: calc(100vh - 60px);
            padding-bottom: 60px;
            position: relative;
            overflow-x: hidden;
        }

        .content-wrapper > .container {
            padding-top: 2rem; /* Additional spacing from navbar */
        }

        .statistics-section {
            margin-top: 1rem;
            padding-top: 1rem;
        }

        /* ... existing styles ... */

        /* Form Floating Styles */
        .form-floating > label {
            padding: 0.5rem 0.75rem;
        }

        .form-floating > .form-control {
            padding: 0.5rem 0.75rem;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }

        .form-floating > .form-control::placeholder {
            color: transparent;
        }

        .form-floating > .form-control:focus,
        .form-floating > .form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            background-color: white;
            height: auto;
            padding: 0 0.5rem;
            margin-left: 0.5rem;
        }

        /* Modal Header Style */
        .modal-header.bg-primary {
            background-color: #42a5f5 !important;
        }

        /* Milestone Container Style */
        .milestone-entries {
            max-height: 250px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border-color: #dee2e6 !important;
        }

        .milestone-entry {
            background-color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 0.75rem !important;
        }

        .milestone-entry:last-child {
            margin-bottom: 0 !important;
        }

        /* Custom Button Styles */
        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
        }

        /* Form Select Style */
        .form-select {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            height: calc(3.5rem + 2px);
        }

        .form-floating > .form-select ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        /* Improved Focus States */
        .form-control:focus,
        .form-select:focus {
            border-color: #42a5f5;
            box-shadow: 0 0 0 0.25rem rgba(66, 165, 245, 0.25);
        }

        /* ... existing styles ... */

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            font-family: 'Poppins', sans-serif;
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        .modal-header .modal-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.25rem;
        }

        .modal-header .btn-close {
            background-color: #e9ecef;
            border-radius: 50%;
            padding: 0.75rem;
            opacity: 1;
            transition: all 0.2s ease;
        }

        .modal-header .btn-close:hover {
            background-color: #dee2e6;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
            border-radius: 0 0 15px 15px;
        }

        .modal .form-label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .modal .form-control, .modal .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .modal .form-control:focus, .modal .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .modal textarea.form-control {
            min-height: 100px;
        }

        .milestone-entries {
            max-height: 250px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border-color: #dee2e6 !important;
        }

        .milestone-entry {
            background-color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 0.75rem !important;
        }

        .milestone-entry:last-child {
            margin-bottom: 0 !important;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
        }

        /* ... existing styles ... */

        /* Button Animation Styles */
        .btn-animate {
            position: relative;
            overflow: hidden;
            transform: translate3d(0, 0, 0);
            border-radius: 15px !important;
        }

        .btn-animate::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease-out, height 0.6s ease-out;
        }

        .btn-animate:active::before {
            width: 200%;
            height: 200%;
            transition: width 0s, height 0s;
        }

        .btn-animate-primary {
            background-color: #42a5f5 !important;
            color: white !important;
            border: 1px solid #42a5f5 !important;
            transition: all 0.3s ease !important;
        }

        .btn-animate-primary:hover {
            background-color: #1e88e5 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(66, 165, 245, 0.2) !important;
        }

        .btn-animate-primary:active {
            transform: translateY(0) !important;
        }

        .btn-animate-secondary {
            background-color: white !important;
            color: #6c757d !important;
            border: 1px solid #dee2e6 !important;
            transition: all 0.3s ease !important;
        }

        .btn-animate-secondary:hover {
            background-color: #f8f9fa !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            border-color: #42a5f5 !important;
        }

        .btn-animate-secondary:active {
            transform: translateY(0) !important;
        }

        .btn-animate-milestone {
            background-color: #42a5f5B3 !important;
            color: white !important;
            border: 1px solid #42a5f5 !important;
            transition: all 0.3s ease !important;
            border-radius: 15px !important;
        }

        .btn-animate-milestone:hover {
            background-color: #42a5f5CC !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(66, 165, 245, 0.2) !important;
        }

        .btn-animate-milestone:active {
            transform: translateY(0) !important;
        }

        /* Updated Goal Actions - Modern Button Style */
        .goal-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .goal-actions .action-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.8);
            color: #64748b;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .goal-actions .action-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .goal-actions .action-btn:active {
            transform: translateY(0) scale(0.98);
        }

        .goal-actions .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .goal-actions .action-btn:active::before {
            width: 100%;
            height: 100%;
        }

        .goal-actions .contribute-btn {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.2) 100%);
            border-color: rgba(34, 197, 94, 0.2);
            color: #059669;
        }

        .goal-actions .contribute-btn:hover {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.3) 100%);
            border-color: rgba(34, 197, 94, 0.4);
            color: #047857;
        }

        .goal-actions .withdraw-btn {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%);
            border-color: rgba(245, 158, 11, 0.2);
            color: #d97706;
        }

        .goal-actions .withdraw-btn:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.3) 100%);
            border-color: rgba(245, 158, 11, 0.4);
            color: #b45309;
        }

        .goal-actions .edit-btn {
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.1) 0%, rgba(66, 165, 245, 0.2) 100%);
            border-color: rgba(66, 165, 245, 0.2);
            color: #2563eb;
        }

        .goal-actions .edit-btn:hover {
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.2) 0%, rgba(66, 165, 245, 0.3) 100%);
            border-color: rgba(66, 165, 245, 0.4);
            color: #1d4ed8;
        }

        .goal-actions .delete-btn {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.2) 100%);
            border-color: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        .goal-actions .delete-btn:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.3) 100%);
            border-color: rgba(239, 68, 68, 0.4);
            color: #b91c1c;
        }

        /* Enhanced Goal Card Styling */
        .card {
            border: none;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        /* Ensure all goal cards have consistent width */
        .card.mb-4,
        .card.mb-3,
        .completed-goal-card {
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Completed goals section container */
        .card.mt-4 {
            width: 100%;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .goal-card {
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 1.5rem;
            gap: 1.5rem;
            position: relative;
        }

        .goal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #42a5f5 0%, #667eea 100%);
            border-radius: 16px 16px 0 0;
        }

        .goal-icon {
            font-size: 2.2rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 249, 250, 0.9) 100%);
            color: #42a5f5;
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .goal-card:hover .goal-icon {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 4px 15px rgba(66, 165, 245, 0.2);
        }

        .goal-content {
            flex-grow: 1;
        }

        .goal-content h5.card-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .amount {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .deadline, .category {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .deadline i, .category i {
            color: #94a3b8;
        }

        .progress {
            height: 10px;
            border-radius: 8px;
            margin-top: 0.75rem;
            background: linear-gradient(90deg, #f1f5f9 0%, #e2e8f0 100%);
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(90deg, #42a5f5 0%, #667eea 100%);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* History Section Enhanced */
        .history-section {
            border-top: 1px solid rgba(226, 232, 240, 0.6);
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.5) 0%, rgba(255, 255, 255, 0.8) 100%);
        }

        .history-toggle {
            text-decoration: none;
            color: #64748b;
            padding: 1rem 1.5rem;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 0 0 16px 16px;
            font-weight: 500;
        }

        .history-toggle:hover {
            color: #42a5f5;
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.05) 0%, rgba(66, 165, 245, 0.1) 100%);
        }

        .history-toggle:focus {
            box-shadow: none;
        }

        .history-toggle .fa-chevron-down {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .history-toggle.collapsed .fa-chevron-down {
            transform: rotate(0deg);
        }

        .history-toggle:not(.collapsed) .fa-chevron-down {
            transform: rotate(180deg);
        }

        .history-content {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
        }

        .history-content .table {
            margin-bottom: 0;
            font-size: 0.9rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .history-content .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-color: rgba(226, 232, 240, 0.6);
        }

        .history-content .badge {
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar/navbar.php'; ?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container py-4">
        <!-- Statistici și Alerte -->
        <div class="widget">
            <div class="widget-header">
                <h2><i class="fas fa-chart-line me-2"></i>Statistici și Alerte</h2>
            </div>
            <div class="widget-body">
                <!-- Statistici -->
                <div class="statistics-grid mb-4">
                    <div class="statistic-item">
                        <div class="notification-icon" style="color: #42a5f5;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Total Obiective</div>
                            <div class="notification-text" style="color: #42a5f5;"><?php echo $goals_stats['total']; ?></div>
                        </div>
                    </div>
                    <div class="statistic-item">
                        <div class="notification-icon" style="color: #4CAF50;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Obiective Completate</div>
                            <div class="notification-text" style="color: #4CAF50;"><?php echo $goals_stats['completed']; ?></div>
                        </div>
                    </div>
                    <div class="statistic-item">
                        <div class="notification-icon" style="color: #9C27B0;">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Obiective Active</div>
                            <div class="notification-text" style="color: #9C27B0;"><?php echo $goals_stats['total'] - $goals_stats['completed']; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Avertismente -->
                <?php if (!empty($deadline_warnings)): ?>
                    <div id="deadline-warnings" class="notifications-container">
                        <h3 class="notifications-title">
                            <i class="fas fa-bell me-2"></i>
                            Avertismente și Notificări
                        </h3>
                        
                        <?php foreach ($deadline_warnings as $warning): ?>
                            <div class="notification-item <?php echo $warning['warning_type']; ?>" data-goal-id="<?php echo $warning['id']; ?>">
                                <div class="notification-icon">
                                    <?php if ($warning['warning_type'] === 'no_progress'): ?>
                                        <i class="fas fa-exclamation-circle"></i>
                                    <?php elseif ($warning['warning_type'] === 'overdue'): ?>
                                        <i class="fas fa-exclamation-triangle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <div class="notification-title">
                                            <?php if ($warning['warning_type'] === 'no_progress'): ?>
                                                Nu ai contribuit la obiectivul "<?php echo htmlspecialchars($warning['name']); ?>" încă
                                            <?php elseif ($warning['warning_type'] === 'overdue'): ?>
                                                Termen depășit pentru "<?php echo htmlspecialchars($warning['name']); ?>"
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($warning['name']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-date">
                                            <?php if ($warning['warning_type'] === 'no_progress'): ?>
                                                Niciun progres
                                            <?php elseif ($warning['warning_type'] === 'overdue'): ?>
                                                Depășit cu <?php echo abs($warning['days_remaining']); ?> zile
                                            <?php else: ?>
                                                <?php echo $warning['days_remaining']; ?> zile rămase
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="notification-details">
                                        <div class="amount-info">
                                            <?php if ($warning['warning_type'] === 'no_progress'): ?>
                                                Nu ai făcut nicio contribuție încă
                                            <?php elseif ($warning['warning_type'] === 'overdue'): ?>
                                                Mai sunt necesari <?php echo number_format($warning['remaining_amount'], 2); ?> RON
                                                (termen limită depășit: <?php echo date('d.m.Y', strtotime($warning['deadline'])); ?>)
                                            <?php else: ?>
                                                Mai sunt necesari <?php echo number_format($warning['remaining_amount'], 2); ?> RON
                                                până la <?php echo date('d.m.Y', strtotime($warning['deadline'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="notification-item">
                        <div class="notification-icon info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-text">Nu există alerte sau sugestii în acest moment.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <!-- Add New Goal Card -->
        <div class="card add-goal-card" data-bs-toggle="modal" data-bs-target="#addGoalModal">
            <div class="add-goal-icon">
                <i class="fas fa-plus"></i>
            </div>
            <div class="goal-content">
                <h5 class="mb-0">Adaugă Obiectiv Nou</h5>
                <p class="text-muted mb-0">Creează un nou obiectiv financiar și urmărește progresul tău către succes</p>
            </div>
        </div>

        <!-- Goals List -->
        <?php 
        if ($goals->num_rows > 0):
            // Separăm obiectivele în active și finalizate
            $active_goals = [];
            $completed_goals = [];
            
            while ($goal = $goals->fetch_assoc()) {
                if ($goal['current_amount'] >= $goal['target_amount']) {
                    $completed_goals[] = $goal;
                } else {
                    $active_goals[] = $goal;
                }
            }

            // Afișăm obiectivele active
            foreach ($active_goals as $goal):
                $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                $progress = min(100, max(0, $progress));
                
                $deadline = new DateTime($goal['deadline']);
                $today = new DateTime();
                $days_remaining = $today->diff($deadline)->days;
                $is_overdue = $today > $deadline;

                // Obținem informațiile despre iconița și culoare
                $category_info = getCategoryInfo($goal['category_name'] ?? '', $category_icons);
        ?>
        <div class="card mb-4" id="goal-card-<?php echo $goal['id']; ?>">
            <div class="goal-card">
                <div class="goal-icon" style="color: <?php echo $category_info['color']; ?>">
                    <i class="fas <?php echo $category_info['icon']; ?>"></i>
                </div>
                <div class="goal-content">
                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($goal['name']); ?></h5>
                    <div class="amount">
                        <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?> RON
                        din 
                        <?php echo number_format($goal['target_amount'], 2, ',', '.'); ?> RON
                    </div>
                    <div class="deadline">
                        <i class="fa fa-calendar"></i>
                        <?php if ($is_overdue): ?>
                            <span class="text-danger">Termen depășit (<?php echo date('d.m.Y', strtotime($goal['deadline'])); ?>)</span>
                        <?php else: ?>
                            <?php echo $days_remaining; ?> zile rămase
                            (<?php echo date('d.m.Y', strtotime($goal['deadline'])); ?>)
                        <?php endif; ?>
                    </div>
                    <?php if ($goal['category_name']): ?>
                    <div class="category">
                        <i class="fa fa-tag"></i>
                        <?php echo htmlspecialchars($goal['category_name']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="progress-container mb-3">
                        <div class="progress-wrapper">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $progress; ?>%" 
                                     aria-valuenow="<?php echo $progress; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo number_format($progress, 1); ?>%
                                </div>
                                <?php
                                $next_milestone_amount = null;
                                $next_milestone_description = '';
                                
                                if (!empty($goal['milestones'])) {
                                    $milestones = array_map(function($milestone_str) {
                                        list($amount, $description, $is_completed) = explode(':', $milestone_str);
                                        return [
                                            'amount' => floatval($amount),
                                            'description' => $description,
                                            'is_completed' => $is_completed == '1'
                                        ];
                                    }, explode('|', $goal['milestones']));

                                    // Sortăm milestone-urile după sumă
                                    usort($milestones, function($a, $b) {
                                        return $a['amount'] <=> $b['amount'];
                                    });

                                    // Găsim următorul milestone necompletat
                                    foreach ($milestones as $milestone) {
                                        if ($milestone['amount'] > $goal['current_amount']) {
                                            $next_milestone_amount = $milestone['amount'];
                                            $next_milestone_description = $milestone['description'];
                                            break;
                                        }
                                    }

                                    foreach ($milestones as $milestone) {
                                        $milestone_percent = ($milestone['amount'] / $goal['target_amount']) * 100;
                                        $milestone_class = $milestone['is_completed'] ? 'completed' : '';
                                        echo '<div class="milestone-marker ' . $milestone_class . '" 
                                                  style="left: ' . $milestone_percent . '%" 
                                                  data-amount="' . number_format($milestone['amount'], 2, ',', '.') . ' RON"
                                                  data-description="' . htmlspecialchars($milestone['description']) . '"
                                                  data-toggle="tooltip" 
                                                  title="' . number_format($milestone['amount'], 2, ',', '.') . ' RON">
                                                  <div class="milestone-dot"></div>
                                             </div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Remaining Amount Message -->
                        <div class="remaining-amount-message mt-2">
                            <?php
                            if ($goal['current_amount'] < $goal['target_amount']) {
                                $total_remaining = $goal['target_amount'] - $goal['current_amount'];
                                
                                if ($next_milestone_amount !== null) {
                                    $milestone_remaining = $next_milestone_amount - $goal['current_amount'];
                                    echo '<div class="d-flex align-items-center text-primary">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span>Mai ai nevoie de <strong>' . number_format($milestone_remaining, 2, ',', '.') . ' RON</strong> 
                                            până la următorul milestone' . 
                                            (!empty($next_milestone_description) ? ' (' . htmlspecialchars($next_milestone_description) . ')' : '') .
                                            '</span>
                                          </div>';
                                }
                                
                                echo '<div class="d-flex align-items-center text-info mt-1">
                                        <i class="fas fa-flag-checkered me-2"></i>
                                        <span>Mai ai nevoie de <strong>' . number_format($total_remaining, 2, ',', '.') . ' RON</strong> 
                                        până la obiectivul final</span>
                                      </div>';
                            } else {
                                echo '<div class="d-flex align-items-center text-success">
                                        <i class="fas fa-trophy me-2"></i>
                                        <span>Felicitări! Ți-ai atins obiectivul!</span>
                                      </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="goal-actions">
                    <button class="action-btn contribute-btn" data-bs-toggle="modal" data-bs-target="#addContributionModal<?php echo $goal['id']; ?>" title="Adaugă contribuție">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="action-btn withdraw-btn" data-bs-toggle="modal" data-bs-target="#withdrawContributionModal<?php echo $goal['id']; ?>" title="Retrage contribuție">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button class="action-btn edit-btn" data-bs-toggle="modal" data-bs-target="#editGoal<?php echo $goal['id']; ?>" title="Editează">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="action-btn delete-btn" onclick="confirmDelete(<?php echo $goal['id']; ?>)" title="Șterge">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            
            <!-- Istoric Contribuții -->
            <div class="history-section">
                <button class="btn btn-link history-toggle w-100 text-start ps-4" 
                        type="button" 
                        data-history-target="#history<?php echo $goal['id']; ?>">
                    <i class="fas fa-history me-2"></i>
                    Istoric Contribuții
                    <i class="fas fa-chevron-down float-end me-3"></i>
                </button>
                
                <div class="collapse-section" id="history<?php echo $goal['id']; ?>">
                    <div class="history-content">
                        <?php
                        // Obținem istoricul contribuțiilor pentru acest obiectiv
                        $history_sql = "SELECT amount, type, created_at 
                                      FROM goal_contributions 
                                      WHERE goal_id = ? 
                                      ORDER BY created_at DESC 
                                      LIMIT 10";
                        $history_stmt = $conn->prepare($history_sql);
                        $history_stmt->bind_param("i", $goal['id']);
                        $history_stmt->execute();
                        $history_result = $history_stmt->get_result();
                        
                        if ($history_result->num_rows > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tip</th>
                                        <th class="text-end">Sumă</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($entry = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($entry['created_at'])); ?></td>
                                        <td>
                                            <?php if ($entry['type'] === 'add'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus-circle me-1"></i>
                                                Contribuție
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                Retragere
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="<?php echo $entry['type'] === 'add' ? 'text-success' : 'text-warning'; ?>">
                                                <?php echo $entry['type'] === 'add' ? '+' : '-'; ?>
                                                <?php echo number_format($entry['amount'], 2, ',', '.'); ?> RON
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center py-3">Nu există încă nicio contribuție înregistrată.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pentru adăugare contribuție -->
        <div class="modal fade" id="addContributionModal<?php echo $goal['id']; ?>" tabindex="-1" aria-labelledby="addContributionModalLabel<?php echo $goal['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addContributionModalLabel<?php echo $goal['id']; ?>">Adaugă contribuție pentru <?php echo htmlspecialchars($goal['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="add_contribution.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                            
                            <div class="goal-summary mb-4">
                                <div class="text-muted">
                                    Progres actual: <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?> RON
                                    din <?php echo number_format($goal['target_amount'], 2, ',', '.'); ?> RON
                                </div>
                                <div class="text-muted">
                                    Rămas de contribuit: <?php echo number_format($goal['target_amount'] - $goal['current_amount'], 2, ',', '.'); ?> RON
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="amount<?php echo $goal['id']; ?>" class="form-label">Sumă Contribuție (RON)</label>
                                <input type="number" 
                                    class="form-control" 
                                    id="amount<?php echo $goal['id']; ?>" 
                                    name="amount" 
                                    step="0.01" 
                                    min="0.01" 
                                    max="<?php echo $goal['target_amount'] - $goal['current_amount']; ?>"
                                    required>
                                <div class="form-text">
                                    Suma maximă permisă: <?php echo number_format($goal['target_amount'] - $goal['current_amount'], 2, ',', '.'); ?> RON
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" 
                                    data-bs-dismiss="modal"
                                    class="btn btn-animate btn-animate-secondary">
                                <i class="fas fa-times me-2"></i>Anulează
                            </button>
                            <button type="submit" 
                                    class="btn btn-animate btn-animate-primary">
                                <i class="fas fa-plus me-2"></i>Adaugă
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal pentru retragere -->
        <div class="modal fade" id="withdrawContributionModal<?php echo $goal['id']; ?>" tabindex="-1" aria-labelledby="withdrawContributionModalLabel<?php echo $goal['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="withdrawContributionModalLabel<?php echo $goal['id']; ?>">Retrage din <?php echo htmlspecialchars($goal['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="withdraw_contribution.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                            
                            <div class="goal-summary mb-4">
                                <div class="text-muted">
                                    Sumă acumulată: <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?> RON
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="withdrawAmount<?php echo $goal['id']; ?>" class="form-label">Sumă Retragere (RON)</label>
                                <input type="number" 
                                    class="form-control" 
                                    id="withdrawAmount<?php echo $goal['id']; ?>" 
                                    name="amount" 
                                    step="0.01" 
                                    min="0.01" 
                                    max="<?php echo $goal['current_amount']; ?>"
                                    required>
                                <div class="form-text">
                                    Suma maximă disponibilă: <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?> RON
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" 
                                    data-bs-dismiss="modal"
                                    class="btn btn-animate btn-animate-secondary">
                                <i class="fas fa-times me-2"></i>Anulează
                            </button>
                            <button type="submit" 
                                    class="btn btn-animate btn-animate-primary">
                                <i class="fas fa-minus me-2"></i>Retrage
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Goal Modal -->
        <div class="modal fade" id="editGoal<?php echo $goal['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editează Obiectiv</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="edit_goal.php" onsubmit="return validateEditForm(this);">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit_goal">
                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="name<?php echo $goal['id']; ?>" class="form-label">Nume Obiectiv</label>
                                <input type="text" class="form-control" id="name<?php echo $goal['id']; ?>" 
                                       name="name" value="<?php echo htmlspecialchars($goal['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="target_amount<?php echo $goal['id']; ?>" class="form-label">Sumă Țintă (RON)</label>
                                <input type="number" class="form-control" id="target_amount<?php echo $goal['id']; ?>" 
                                       name="target_amount" value="<?php echo $goal['target_amount']; ?>" 
                                       step="0.01" min="0" required>
                            </div>

                            <div class="mb-3">
                                <label for="current_amount<?php echo $goal['id']; ?>" class="form-label">Sumă Acumulată (RON)</label>
                                <input type="number" class="form-control" id="current_amount<?php echo $goal['id']; ?>" 
                                       name="current_amount" value="<?php echo $goal['current_amount']; ?>" 
                                       step="0.01" min="0" required>
                            </div>

                            <!-- Milestone Management Section -->
                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Milestone-uri</span>
                                    <button type="button" class="btn btn-animate btn-animate-milestone" 
                                            onclick="addMilestoneEdit(<?php echo $goal['id']; ?>)">
                                        <i class="fas fa-plus me-1"></i>Adaugă Milestone
                                    </button>
                                </label>
                                <div id="milestones-container-<?php echo $goal['id']; ?>" class="milestone-entries">
                                    <?php
                                    if (!empty($goal['milestones'])) {
                                        $milestones = array_map(function($milestone_str) {
                                            list($amount, $description, $is_completed) = explode(':', $milestone_str);
                                            return [
                                                'amount' => floatval($amount),
                                                'description' => $description,
                                                'is_completed' => $is_completed == '1'
                                            ];
                                        }, explode('|', $goal['milestones']));

                                        foreach ($milestones as $index => $milestone) {
                                            ?>
                                            <div class="milestone-entry d-flex gap-2 mb-2 align-items-center">
                                                <div class="input-group">
                                                    <input type="number" class="form-control" 
                                                           name="milestone_amounts[]" 
                                                           value="<?php echo $milestone['amount']; ?>"
                                                           placeholder="Sumă (RON)" step="0.01" min="0">
                                                    <input type="text" class="form-control" 
                                                           name="milestone_descriptions[]" 
                                                           value="<?php echo htmlspecialchars($milestone['description']); ?>"
                                                           placeholder="Descriere (opțional)">
                                                    <button type="button" 
                                                            class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 hover:shadow-md hover:translate-y-[-2px] hover:shadow-red-200/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-200 transition-all duration-300 ease-in-out border border-red-200" 
                                                            onclick="removeMilestoneEdit(this, <?php echo $goal['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deadline<?php echo $goal['id']; ?>" class="form-label">Termen Limită</label>
                                <input type="date" class="form-control" id="deadline<?php echo $goal['id']; ?>" 
                                       name="deadline" value="<?php echo $goal['deadline']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id<?php echo $goal['id']; ?>" class="form-label">Categorie</label>
                                <select class="form-select" id="category_id<?php echo $goal['id']; ?>" name="category_id">
                                    <option value="">Fără categorie</option>
                                    <?php foreach ($categories as $cat_id => $cat_name): ?>
                                    <option value="<?php echo $cat_id; ?>" 
                                            <?php echo $cat_id == $goal['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat_name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" 
                                    data-bs-dismiss="modal"
                                    class="btn btn-animate btn-animate-secondary">
                                <i class="fas fa-times me-2"></i>Anulează
                            </button>
                            <button type="submit" 
                                    class="btn btn-animate btn-animate-primary">
                                <i class="fas fa-save me-2"></i>Salvează
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
        <?php 
            endforeach;
            
            // Secțiunea pentru obiective finalizate
            if (!empty($completed_goals)):
        ?>
        <div class="card mt-4">
            <div class="card-header completed-goals-header" id="completedGoalsHeader">
                <h5 class="mb-0">
                    <button class="btn btn-link text-white w-100 text-start text-decoration-none p-0" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#completedGoals" 
                            aria-expanded="false" 
                            aria-controls="completedGoals">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-trophy me-2"></i>
                                Obiective Finalizate (<?php echo count($completed_goals); ?>)
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </button>
                </h5>
            </div>

            <div id="completedGoals" class="collapse" aria-labelledby="completedGoalsHeader">
                <div class="card-body">
                    <?php foreach ($completed_goals as $goal):
                        $category_info = getCategoryInfo($goal['category_name'] ?? '', $category_icons);
                    ?>
                        <div class="card mb-3 completed-goal-card" id="goal-card-<?php echo $goal['id']; ?>">
                            <div class="goal-card">
                                <div class="goal-icon" style="color: <?php echo $category_info['color']; ?>">
                                    <i class="fas <?php echo $category_info['icon']; ?>"></i>
                                </div>
                                <div class="goal-content">
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                    <div class="amount">
                                        <?php echo number_format($goal['current_amount'], 2, ',', '.'); ?> RON
                                        din 
                                        <?php echo number_format($goal['target_amount'], 2, ',', '.'); ?> RON
                                    </div>
                                    <div class="deadline">
                                        <i class="fa fa-calendar"></i>
                                        Finalizat la: <?php echo date('d.m.Y', strtotime($goal['deadline'])); ?>
                                    </div>
                                    <?php if ($goal['category_name']): ?>
                                    <div class="category">
                                        <i class="fa fa-tag"></i>
                                        <?php echo htmlspecialchars($goal['category_name']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: 100%" 
                                             aria-valuenow="100" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            100%
                                        </div>
                                    </div>
                                </div>
                                <div class="goal-actions">
                                    <button type="button" class="action-btn delete-btn" onclick="confirmDelete(<?php echo $goal['id']; ?>)" title="Șterge">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Istoric Contribuții -->
                            <div class="history-section">
                                <button class="btn btn-link history-toggle w-100 text-start ps-4" 
                                        type="button" 
                                        data-history-target="#history<?php echo $goal['id']; ?>">
                                    <i class="fas fa-history me-2"></i>
                                    Istoric Contribuții
                                    <i class="fas fa-chevron-down float-end me-3"></i>
                                </button>
                                
                                <div class="collapse-section" id="history<?php echo $goal['id']; ?>">
                                    <div class="history-content">
                                        <?php
                                        // Obținem istoricul contribuțiilor pentru acest obiectiv
                                        $history_sql = "SELECT amount, type, created_at 
                                                      FROM goal_contributions 
                                                      WHERE goal_id = ? 
                                                      ORDER BY created_at DESC 
                                                      LIMIT 10";
                                        $history_stmt = $conn->prepare($history_sql);
                                        $history_stmt->bind_param("i", $goal['id']);
                                        $history_stmt->execute();
                                        $history_result = $history_stmt->get_result();
                                        
                                        if ($history_result->num_rows > 0):
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Tip</th>
                                                        <th class="text-end">Sumă</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($entry = $history_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo date('d.m.Y H:i', strtotime($entry['created_at'])); ?></td>
                                                        <td>
                                                            <?php if ($entry['type'] === 'add'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-plus-circle me-1"></i>
                                                                Contribuție
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-minus-circle me-1"></i>
                                                                Retragere
                                                            </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="<?php echo $entry['type'] === 'add' ? 'text-success' : 'text-warning'; ?>">
                                                                <?php echo $entry['type'] === 'add' ? '+' : '-'; ?>
                                                                <?php echo number_format($entry['amount'], 2, ',', '.'); ?> RON
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted text-center py-3">Nu există încă nicio contribuție înregistrată.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Navigare pagini">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info" style="width: 100%; max-width: 1100px; margin: 2rem auto;">Nu ai niciun obiectiv financiar setat.</div>
<?php endif; ?>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Adaugă Obiectiv Nou
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="goals.php" id="addGoalForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_goal">
                    
                    <div class="mb-3">
                        <label class="form-label">Nume Obiectiv</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sumă Țintă Finală (RON)</label>
                        <input type="number" class="form-control" id="target_amount" name="target_amount" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-flag me-2"></i>Milestone-uri (Etape Intermediare)</span>
                            <button type="button" 
                                    class="btn btn-animate btn-animate-milestone" 
                                    onclick="addMilestone()">
                                <i class="fas fa-plus me-2"></i>Adaugă Milestone
                            </button>
                        </label>
                        <div id="milestones-container" class="milestone-entries rounded-3 border p-3">
                            <!-- Milestone entries will be added here -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Termen Limită</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categorie</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Fără categorie</option>
                            <?php foreach ($categories as $cat_id => $cat_name): ?>
                            <option value="<?php echo $cat_id; ?>">
                                <?php echo htmlspecialchars($cat_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="submit" 
                            class="btn btn-animate btn-animate-primary">
                        <i class="fas fa-save me-2"></i>Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- Footer -->
<?php include 'footer/footer.html'; ?>

<!-- Delete Goal Modal -->
<div class="modal fade" id="deleteGoalModal" tabindex="-1" aria-labelledby="deleteGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGoalModalLabel">Confirmare ștergere</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest obiectiv?
            </div>
            <div class="modal-footer">
                <form action="delete_goal.php" method="POST">
                    <input type="hidden" name="goal_id" id="goalIdToDelete">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="submit" 
                            class="btn btn-animate btn-animate-primary">
                        <i class="fas fa-trash me-2"></i>Șterge
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click animation to buttons
    document.querySelectorAll('.btn-animate').forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const circle = document.createElement('div');
            circle.style.position = 'absolute';
            circle.style.left = x + 'px';
            circle.style.top = y + 'px';
            circle.style.width = '1px';
            circle.style.height = '1px';
            circle.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
            circle.style.borderRadius = '50%';
            circle.style.transition = 'all 0.3s ease-out';
            
            button.appendChild(circle);
            
            requestAnimationFrame(() => {
                circle.style.transform = 'scale(100)';
                circle.style.opacity = '0';
                
                setTimeout(() => {
                    circle.remove();
                }, 300);
            });
        });
    });

    // Add click animation to action buttons
    document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('div');
            ripple.style.position = 'absolute';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.width = '0';
            ripple.style.height = '0';
            ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.4)';
            ripple.style.borderRadius = '50%';
            ripple.style.transform = 'translate(-50%, -50%)';
            ripple.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            ripple.style.pointerEvents = 'none';
            ripple.style.zIndex = '1';
            
            button.appendChild(ripple);
            
            requestAnimationFrame(() => {
                ripple.style.width = '100px';
                ripple.style.height = '100px';
                ripple.style.opacity = '0';
                
                setTimeout(() => {
                    ripple.remove();
                }, 400);
            });
        });
    });

    // Pentru secțiunea Obiective Realizate
    var completedGoalsHeader = document.querySelector('.completed-goals-header');
    var completedGoalsSection = document.getElementById('completedGoals');
    
    if (completedGoalsHeader && completedGoalsSection) {
        completedGoalsHeader.addEventListener('click', function(e) {
            e.preventDefault();
            var icon = this.querySelector('.fa-chevron-down');
            if (completedGoalsSection.classList.contains('show')) {
                completedGoalsSection.classList.remove('show');
                icon.classList.remove('rotate-icon');
            } else {
                completedGoalsSection.classList.add('show');
                icon.classList.add('rotate-icon');
            }
        });
    }

    // Pentru toate secțiunile de Istoric Contribuții
    document.querySelectorAll('.history-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-history-target');
            var target = document.querySelector(targetId);
            var icon = this.querySelector('.fa-chevron-down');
            
            if (target.classList.contains('show')) {
                target.classList.remove('show');
                icon.classList.remove('rotate-icon');
                this.classList.add('collapsed');
            } else {
                target.classList.add('show');
                icon.classList.add('rotate-icon');
                this.classList.remove('collapsed');
            }
        });
    });
});

// Restul funcțiilor existente rămân neschimbate
function confirmDelete(goalId) {
    document.getElementById('goalIdToDelete').value = goalId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteGoalModal'));
    deleteModal.show();
}

// Funcție pentru adăugarea unui nou milestone în formularul de adăugare
function addMilestone() {
    const container = document.getElementById('milestones-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'milestone-entry d-flex gap-2 mb-2';
    newEntry.innerHTML = `
        <input type="number" class="form-control" name="milestone_amounts[]" 
               placeholder="Sumă (RON)" step="0.01" min="0">
        <input type="text" class="form-control" name="milestone_descriptions[]" 
               placeholder="Descriere (opțional)">
        <button type="button" 
                class="w-full sm:w-auto px-6 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out" 
                onclick="removeMilestone(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(newEntry);
}

// Funcție pentru ștergerea unui milestone din formularul de adăugare
function removeMilestone(button) {
    button.closest('.milestone-entry').remove();
}

// Funcție pentru adăugarea unui nou milestone în formularul de editare
function addMilestoneEdit(goalId) {
    const container = document.getElementById(`milestones-container-${goalId}`);
    const newEntry = document.createElement('div');
    newEntry.className = 'milestone-entry d-flex gap-2 mb-2 align-items-center';
    newEntry.innerHTML = `
        <div class="input-group">
            <input type="number" class="form-control" name="milestone_amounts[]" 
                   placeholder="Sumă (RON)" step="0.01" min="0">
            <input type="text" class="form-control" name="milestone_descriptions[]" 
                   placeholder="Descriere (opțional)">
            <button type="button" 
                    class="w-full sm:w-auto px-6 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out" 
                    onclick="removeMilestoneEdit(this, ${goalId})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newEntry);
}

// Funcție pentru ștergerea unui milestone din formularul de editare
function removeMilestoneEdit(button, goalId) {
    button.closest('.milestone-entry').remove();
}

// ... rest of the existing code ...
</script>
<?php
// Verificăm dacă avem milestone-uri atinse de afișat
if (isset($_GET['success']) && strpos($_GET['success'], 'Milestone') !== false) {
    echo "<script>showMilestoneNotification(" . json_encode($_GET['success']) . ");</script>";
}
?>
</body>
</html> 