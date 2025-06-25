<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
include 'database/db.php';

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

// Funcție pentru a verifica obiectivele cu deadline apropiat
function getApproachingDeadlines($conn, $user_id) {
    $warnings = [];
    
    $sql = "SELECT id, name, target_amount, current_amount, deadline,
            DATEDIFF(deadline, CURDATE()) as days_remaining
            FROM goals 
            WHERE user_id = ? 
            AND current_amount < target_amount 
            AND DATEDIFF(deadline, CURDATE()) <= 14 
            AND DATEDIFF(deadline, CURDATE()) >= 0
            ORDER BY days_remaining ASC";
    
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
            'deadline' => $goal['deadline']
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
        ) FROM goal_milestones m WHERE m.goal_id = g.id) as milestones
        FROM goals g 
        LEFT JOIN categories c ON g.category_id = c.id 
        WHERE g.user_id = ? 
        ORDER BY g.current_amount >= g.target_amount ASC, g.created_at DESC
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
    <title>Obiective Financiare - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f3f3;
            color: #616f80;
            padding-top: 60px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
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
            border: 2px dashed #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 0.75rem;
            gap: 1.5rem;
            min-height: 80px;
        }

        .add-goal-icon {
            font-size: 1.75rem;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f8f9fa;
            color: #007bff;
            flex-shrink: 0;
        }

        .add-goal-card .goal-content h5 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .add-goal-card .goal-content p {
            font-size: 0.85rem;
            margin-bottom: 0;
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

        /* Navbar Styles */
        .navbar {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(
                to right,
                rgba(33, 37, 41, 0.97),
                rgba(33, 37, 41, 0.97)
            );
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: rgba(255, 255, 255, 1) !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
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
        }

        .progress-wrapper {
            position: relative;
            height: 25px;
            margin-bottom: 10px;
        }

        .progress {
            position: absolute;
            width: 100%;
            background-color: #f8f9fa;
            border-radius: 15px;
            overflow: visible !important;
            z-index: 1;
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
        
        .warning-alert .progress {
            height: 10px;
            margin-top: 10px;
        }
        
        .warning-alert .deadline-info {
            color: #856404;
            font-weight: bold;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .warning-alert {
            animation: fadeIn 0.5s ease-in-out;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include 'navbar/navbar.php'; ?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container">
        <h2 class="mb-4">Obiective Financiare</h2>

        <!-- Goals Summary Section -->
        <div class="goals-summary">
            <h4><i class="fas fa-chart-line me-2"></i>Progres General</h4>
            
            <div class="progress-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $goals_stats['completed']; ?>/<?php echo $goals_stats['total']; ?></div>
                    <div class="stat-label">Obiective Atinse în <?php echo $current_year; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($goals_stats['inactive']); ?></div>
                    <div class="stat-label">Necesită Atenție</div>
                </div>
            </div>

            <?php if (!empty($goals_stats['inactive'])): ?>
            <div class="alerts-section">
                <h5><i class="fas fa-bell me-2"></i>Alerte și Sugestii</h5>
                <?php foreach ($goals_stats['inactive'] as $inactive_goal): ?>
                    <div class="alert-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="alert-text">
                            Nu ai contribuit la obiectivul 
                            "<a href="#goal-<?php echo $inactive_goal['id']; ?>" class="alert-link">
                                <?php echo htmlspecialchars($inactive_goal['name']); ?>
                            </a>" 
                            <?php 
                            if (is_numeric($inactive_goal['days'])) {
                                echo 'de ' . $inactive_goal['days'] . ' zile';
                            } else {
                                echo 'încă. Începe să contribui!';
                            }
                            ?>
                        </div>
                        <button class="btn btn-sm btn-outline-warning" 
                                onclick="scrollToGoal(<?php echo $inactive_goal['id']; ?>)">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Success & Error Messages -->
        <?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
        <div class="alert alert-<?php echo isset($_SESSION['success_message']) ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php
            if (isset($_SESSION['success_message'])) {
                echo $_SESSION['success_message'];
            } else {
                echo isset($_SESSION['error_message']) ? 'A apărut o eroare: ' . htmlspecialchars($_SESSION['error_message']) : 'A apărut o eroare!';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Add New Goal Card -->
        <div class="card add-goal-card mb-4" data-bs-toggle="modal" data-bs-target="#addGoalModal">
            <div class="add-goal-icon">
                <i class="fa fa-plus"></i>
            </div>
            <div class="goal-content">
                <h5 class="mb-0">Adaugă Obiectiv Nou</h5>
                <p class="text-muted mb-0">Click pentru a adăuga un obiectiv financiar nou</p>
            </div>
        </div>

        <!-- Goals List -->
        <?php 
        if ($goals->num_rows > 0):
            while ($goal = $goals->fetch_assoc()):
                $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                $progress = min(100, max(0, $progress));
                
                $deadline = new DateTime($goal['deadline']);
                $today = new DateTime();
                $days_remaining = $today->diff($deadline)->days;
                $is_overdue = $today > $deadline;

                // Simplified icon selection
                $icon = 'fa-piggy-bank';
                if (!empty($goal['category_name'])) {
                    $category_lower = mb_strtolower($goal['category_name'], 'UTF-8');
                    $icons = [
                        'locuință' => 'fa-home',
                        'transport' => 'fa-car',
                        'educație' => 'fa-graduation-cap',
                        'vacanță' => 'fa-plane',
                        'tehnologie' => 'fa-laptop',
                        'investiții' => 'fa-chart-line'
                    ];
                    $icon = isset($icons[$category_lower]) ? $icons[$category_lower] : 'fa-piggy-bank';
                }
        ?>
        <div class="card mb-4">
            <div class="goal-card">
                <div class="goal-icon">
                    <i class="fas <?php echo $icon; ?>"></i>
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
                    <button class="contribute-btn" data-bs-toggle="modal" data-bs-target="#addContributionModal<?php echo $goal['id']; ?>" title="Adaugă contribuție">
                        <i class="fas fa-plus-circle fa-lg text-success"></i>
                    </button>
                    <button class="withdraw-btn" data-bs-toggle="modal" data-bs-target="#withdrawContributionModal<?php echo $goal['id']; ?>" title="Retrage contribuție">
                        <i class="fas fa-minus-circle fa-lg text-warning"></i>
                    </button>
                    <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#editGoal<?php echo $goal['id']; ?>" title="Editează">
                        <i class="fas fa-edit fa-lg"></i>
                    </button>
                    <button class="delete-btn" onclick="deleteGoal(<?php echo $goal['id']; ?>)" title="Șterge">
                        <i class="fas fa-trash-alt fa-lg"></i>
                    </button>
                </div>
            </div>
            
            <!-- Istoric Contribuții -->
            <div class="history-section">
                <button class="btn btn-link history-toggle collapsed w-100 text-start ps-4" 
                        type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#history<?php echo $goal['id']; ?>" 
                        aria-expanded="false"
                        aria-controls="history<?php echo $goal['id']; ?>">
                    <i class="fas fa-history me-2"></i>
                    Istoric Contribuții
                    <i class="fas fa-chevron-down float-end me-3"></i>
                </button>
                
                <div class="collapse" id="history<?php echo $goal['id']; ?>">
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                            <button type="submit" class="btn btn-success">Adaugă</button>
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                            <button type="submit" class="btn btn-warning">Retrage</button>
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
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
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
                                                    <button type="button" class="btn btn-outline-danger" 
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                            <button type="submit" class="btn btn-primary">Salvează</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
            
            // Pagination
            if ($total_pages > 1):
        ?>
        <nav aria-label="Navigare pagini">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php 
            endif;
        else:
        ?>
        <div class="alert alert-info">Nu ai niciun obiectiv financiar setat.</div>
        <?php endif; ?>

        <!-- Deadline Warnings -->
        <?php if (!empty($deadline_warnings)): ?>
            <div id="deadline-warnings" class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Avertismente pentru obiective apropiate de deadline
                            </h5>
                            
                            <?php foreach ($deadline_warnings as $warning): ?>
                                <div class="warning-alert" data-goal-id="<?php echo $warning['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($warning['name']); ?></h6>
                                        <span class="deadline-info">
                                            <?php echo $warning['days_remaining']; ?> zile rămase
                                        </span>
                                    </div>
                                    <p class="mb-1">
                                        Mai sunt necesari <?php echo number_format($warning['remaining_amount'], 2); ?> RON
                                        până la <?php echo date('d.m.Y', strtotime($warning['deadline'])); ?>
                                    </p>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" 
                                             role="progressbar" 
                                             style="width: <?php echo $warning['progress_percent']; ?>%"
                                             aria-valuenow="<?php echo $warning['progress_percent']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo round($warning['progress_percent']); ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adaugă Obiectiv Nou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="goals.php" id="addGoalForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_goal">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nume Obiectiv</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="target_amount" class="form-label">Sumă Țintă Finală (RON)</label>
                        <input type="number" class="form-control" id="target_amount" name="target_amount" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Milestone-uri (Etape Intermediare)</label>
                        <div id="milestones-container">
                            <div class="milestone-entry d-flex gap-2 mb-2">
                                <input type="number" class="form-control" name="milestone_amounts[]" 
                                       placeholder="Sumă (RON)" step="0.01" min="0">
                                <input type="text" class="form-control" name="milestone_descriptions[]" 
                                       placeholder="Descriere (opțional)">
                                <button type="button" class="btn btn-outline-danger" onclick="removeMilestone(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addMilestone()">
                            <i class="fas fa-plus me-1"></i>Adaugă Milestone
                        </button>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Termen Limită</label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categorie</label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmare Ștergere</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi acest obiectiv?
            </div>
            <div class="modal-footer">
                <form method="POST" action="delete_goal.php" id="deleteGoalForm">
                    <input type="hidden" name="goal_id" id="deleteGoalId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger">Șterge</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include 'footer/footer.html'; ?>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script src="js/goals.js"></script>
<?php
// Verificăm dacă avem milestone-uri atinse de afișat
if (isset($_GET['success']) && strpos($_GET['success'], 'Milestone') !== false) {
    echo "<script>showMilestoneNotification(" . json_encode($_GET['success']) . ");</script>";
}
?>
</body>
</html> 