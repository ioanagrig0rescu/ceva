<?php
ob_start();
session_start();

// Temporary error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'venituri_error.log');

include 'database/db.php';
include 'sesion-check/check.php';

try {
    // Create income_history table if it doesn't exist
    $create_history_table = "CREATE TABLE IF NOT EXISTS income_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        income_id INT NOT NULL,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        income_date DATE NOT NULL,
        completed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    if (!$conn->query($create_history_table)) {
        throw new Exception("Error creating history table: " . $conn->error);
    }

    // Check if incomes table exists
    $result = $conn->query("SHOW TABLES LIKE 'incomes'");
    if ($result === false) {
        throw new Exception("Error checking table existence: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        // Table exists, get existing incomes
        $sql = "SELECT id, name, amount, income_date FROM incomes WHERE user_id = ? ORDER BY income_date DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $_SESSION['id']);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $incomes = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Create incomes table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS incomes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            income_date DATE NOT NULL,
            category VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB";
        
        if (!$conn->query($create_table_sql)) {
            throw new Exception("Error creating table: " . $conn->error);
        }
        
        // After creating table, initialize empty incomes array
        $incomes = [];
    }
} catch (Exception $e) {
    error_log("Error in venituri.php: " . $e->getMessage());
    die("A apărut o eroare: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venituri - Budget Master</title>
    <?php include 'plugins/bootstrap.html'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            }
        }
    </script>
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f3f3;
            color: #616f80;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
        }

        .content-wrapper {
            flex: 1 0 auto;
            width: 100%;
            margin: 0 !important;
            padding: 120px 0 30px 0 !important;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .top-widgets {
            display: grid;
            grid-template-columns: 75% 25%;
            gap: 1.5rem;
            min-height: 400px;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .income-widget, .notifications-widget {
            height: 100%;
        }

        .bottom-widgets {
            display: grid;
            grid-template-columns: 75% 25%;
            gap: 1.5rem;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .statistics-widget,         .evolution-widget {
            width: 100%;
            height: 100%;
        }

        .evolution-content {
            padding: 0.75rem;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .evolution-percentage {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .evolution-percentage.positive {
            color: #38a169;
        }

        .evolution-percentage.negative {
            color: #e53e3e;
        }

        .evolution-percentage.neutral {
            color: #718096;
        }

        .evolution-arrow {
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .evolution-arrow.up {
            color: #38a169;
            transform: rotate(-45deg);
        }

        .evolution-arrow.down {
            color: #e53e3e;
            transform: rotate(135deg);
        }

        .evolution-arrow.neutral {
            color: #718096;
            transform: rotate(0deg);
        }

        .evolution-description {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 0.4rem;
        }

        .evolution-comparison {
            font-size: 0.75rem;
            color: #a0aec0;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .evolution-empty {
            padding: 1.5rem;
            text-align: center;
            color: #a0aec0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .evolution-empty i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .evolution-empty p {
            margin: 0;
            font-size: 0.9rem;
        }

        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .statistic-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }

        @media (max-width: 992px) {
                .top-widgets, .bottom-widgets {
                    grid-template-columns: 1fr;
                }
                
                .statistics-widget, .next-income-widget {
                    width: 100%;
                }
                
                .statistics-grid {
                grid-template-columns: 1fr;
            }
        }

        .widget {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            overflow: hidden;
            height: 100%;
        }

        .widget-header {
            padding: 1.25rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
        }

        .widget-header h2 {
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .widget-body {
            padding: 1.25rem;
        }

        .income-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            padding: 0.5rem;
        }

        .income-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
            border: 1px solid #f0f0f0;
            aspect-ratio: 1;
            position: relative;
            overflow: hidden;
        }

        .income-card.expired {
            background-color: rgba(154, 230, 180, 0.2) !important;
            border-color: #9ae6b4;
        }

        .income-card.expired .income-card-content {
            background-color: transparent !important;
        }

        .btn-confirm {
            display: none;
            width: 28px;
            height: 28px;
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            border-radius: 50%;
            background-color: #48bb78;
            color: white;
            border: none;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .btn-confirm:hover {
            background-color: #38a169;
            transform: scale(1.1);
        }

        .income-card.expired .btn-confirm {
            display: flex;
        }

        .income-card.expired .income-card-actions {
            right: 3rem;
        }

        .income-card .confirm-button {
            position: absolute;
            top: 0.75rem;
            right: 3rem;
            width: 22px;
            height: 22px;
            padding: 0;
            display: none;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 6px;
            background: #48bb78;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .income-card .confirm-button:hover {
            background: #38a169;
            transform: scale(1.1);
        }

        .income-card.expired .confirm-button {
            display: flex;
        }

        .quick-edit-modal .modal-body {
            padding: 2rem;
        }

        .quick-edit-modal .date-picker {
            width: 100%;
            margin-bottom: 1rem;
        }

        .income-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        }

        .income-card-content {
            padding: 1rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: white;
        }

        .income-name {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #2d3748;
            padding-right: 45px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .income-amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: #38a169;
            margin-bottom: 0.5rem;
        }

        .income-date {
            font-size: 0.8rem;
            color: #718096;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .income-date i {
            color: #718096;
            font-size: 0.8rem;
        }

        .income-card-actions {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.35rem;
        }

        .btn-edit, .btn-delete {
            width: 22px;
            height: 22px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }

        .btn-edit i, .btn-delete i {
            font-size: 0.8rem;
        }

        .btn-edit {
            color: #4299e1;
        }

        .btn-delete {
            color: #e53e3e;
        }

        .btn-edit:hover {
            background: #ebf8ff;
        }

        .btn-delete:hover {
            background: #fff5f5;
        }

        .add-income-card {
            cursor: pointer;
            background: white;
            border: 1px dashed #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #718096;
            transition: all 0.2s ease;
        }

        .add-income-card:hover {
            border-color: #4299e1;
            background: #f7fafc;
            color: #4299e1;
        }

        .add-income-icon {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .add-income-text {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .notifications-widget {
            background: white;
            border-radius: 12px;
            height: 100%;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ebf8ff;
            color: #4299e1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon.success {
            background: #f0fff4;
            color: #38a169;
        }

        .notification-icon.warning {
            background: #fffaf0;
            color: #dd6b20;
        }

        .notification-icon.info {
            background: #ebf8ff;
            color: #4299e1;
        }

        .notification-icon i {
            font-size: 0.9rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .notification-text {
            font-size: 0.8rem;
            color: #718096;
            margin: 0;
            line-height: 1.4;
        }

        .notification-date {
            font-size: 0.75rem;
            color: #a0aec0;
            margin-top: 0.5rem;
        }

        .empty-notifications {
            text-align: center;
            padding: 2rem 1.25rem;
            color: #a0aec0;
        }

        .empty-notifications i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .empty-notifications p {
            font-size: 0.9rem;
            margin: 0;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .modal-header {
            padding: 1.25rem;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            color: #2d3748;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .form-label {
            color: #4a5568;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.625rem 0.875rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66,153,225,0.15);
        }

        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn-primary {
            background: #4299e1;
            border: none;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #3182ce;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #edf2f7;
            border: none;
            color: #4a5568;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .income-cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .income-cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
        }

        /* Calendar Styles */
        .calendar-container {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e2e8f0;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .calendar-header-cell {
            padding: 0.75rem;
            text-align: center;
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .calendar-cell {
            background: white;
            min-height: 100px;
            padding: 0.5rem;
            position: relative;
        }

        .calendar-cell.other-month {
            background: #f8fafc;
        }

        .calendar-cell.today {
            background: #ebf8ff;
        }

        .calendar-date {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 0.8rem;
            color: #718096;
        }

        .calendar-income {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .calendar-income:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .calendar-income .income-tooltip {
            visibility: hidden;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #2d3748;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            white-space: nowrap;
            z-index: 1000;
            opacity: 0;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 200px;
            text-align: center;
        }

        .calendar-income .income-tooltip .income-name {
            font-weight: 600;
            color: #E2E8F0;
            margin-bottom: 0.25rem;
        }

        .calendar-income .income-tooltip .income-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: #48BB78;
            margin: 0.5rem 0;
        }

        .calendar-income .income-tooltip .income-status {
            font-size: 0.75rem;
            color: #A0AEC0;
            font-style: italic;
        }

        .calendar-income:hover .income-tooltip {
            visibility: visible;
            opacity: 1;
            bottom: calc(100% + 5px);
        }

        .calendar-income .income-tooltip:after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #2d3748 transparent transparent transparent;
        }

        .calendar-income.historical {
            opacity: 0.7;
        }

        .calendar-controls {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .calendar-month-year {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2d3748;
        }

        .widget-actions .btn-link {
            padding: 0.25rem 0.5rem;
            color: #718096;
            transition: all 0.2s ease;
        }

        .widget-actions .btn-link:hover {
            color: #4a5568;
            transform: scale(1.1);
        }

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

        .btn-animate.btn-danger {
            background-color: #dc3545 !important;
            color: white !important;
            border: 1px solid #dc3545 !important;
            transition: all 0.3s ease !important;
        }

        .btn-animate.btn-danger:hover {
            background-color: #c82333 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2) !important;
        }

        .btn-animate.btn-danger:active {
            transform: translateY(0) !important;
        }

        /* Statistics Navigation Styles */
        .statistics-navigation {
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .statistics-period {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .statistics-navigation .btn {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .statistics-navigation .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .statistics-navigation .form-select {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            background-color: white;
            transition: all 0.2s ease;
        }

        .statistics-navigation .form-select:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 0.2rem rgba(66, 153, 225, 0.25);
        }

        .statistics-navigation .form-select:hover {
            border-color: #ced4da;
        }
    </style>
</head>
<body>
    <?php include 'navbar/navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="main-content">
                <!-- Top Row: Income Widget and Notifications -->
                <div class="top-widgets">
                <!-- Income Widget -->
                    <div class="widget income-widget">
                    <div class="widget-header">
                        <h2>Venituri</h2>
                        <div class="widget-actions">
                                <button class="btn btn-link text-secondary" onclick="showIncomeCalendar()" title="Deschide calendarul veniturilor">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="income-cards-grid">
                            <!-- Add Income Card -->
                            <div class="income-card add-income-card" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
                                <div class="income-card-content">
                                    <div class="add-income-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="add-income-text">Adaugă Venit</div>
                                </div>
                            </div>

                            <!-- Income Cards -->
                            <?php
                            $sql = "SELECT * FROM incomes WHERE user_id = ? ORDER BY income_date DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $_SESSION['id']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($income = $result->fetch_assoc()) {
                                    // Check if income is expired
                                    $income_date = strtotime($income['income_date']);
                                    $today = strtotime('today');
                                    $is_expired = $income_date < $today;
                                    
                                    $expired_class = $is_expired ? 'expired' : '';
                                    
                                    echo '<div class="income-card ' . $expired_class . '" data-income-id="' . $income['id'] . '">';
                                echo '<div class="income-card-content">';
                                echo '<div>';
                                echo '<h3 class="income-name">' . htmlspecialchars($income['name']) . '</h3>';
                                echo '<div class="income-amount">' . number_format($income['amount'], 2) . ' RON</div>';
                                echo '</div>';
                                echo '<div class="income-date"><i class="fas fa-calendar-alt"></i> ' . date('d.m.Y', strtotime($income['income_date'])) . '</div>';
                                echo '</div>';
                                    
                                    // Show either check button or edit/delete buttons
                                    if ($is_expired) {
                                        echo '<button class="btn-confirm" onclick="confirmIncome(' . $income['id'] . ', event)" title="Actualizează pentru luna următoare"><i class="fas fa-check"></i></button>';
                                    } else {
                                echo '<div class="income-card-actions">';
                                        echo '<button class="btn-edit" onclick="editIncome(' . $income['id'] . ')" title="Modifică"><i class="fas fa-edit"></i></button>';
                                        echo '<button class="btn-delete" onclick="deleteIncome(' . $income['id'] . ')" title="Șterge"><i class="fas fa-trash-alt"></i></button>';
                                echo '</div>';
                                    }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Notifications Widget -->
                <div class="widget notifications-widget">
                        <div class="widget-header">
                            <h2>Notificări</h2>
                        </div>
                        <div class="widget-body">
                            <?php
                            $has_notifications = false;

                            // Get expired incomes
                            $expired_sql = "SELECT name, income_date FROM incomes 
                                          WHERE user_id = ? AND income_date < CURDATE() 
                                          ORDER BY income_date DESC";
                            $expired_stmt = $conn->prepare($expired_sql);
                            $expired_stmt->bind_param("i", $_SESSION['id']);
                            $expired_stmt->execute();
                            $expired_result = $expired_stmt->get_result();
                            
                            while ($expired_income = $expired_result->fetch_assoc()) {
                                $has_notifications = true;
                                echo '<div class="notification-item">';
                                echo '<div class="notification-icon warning">';
                                echo '<i class="fas fa-exclamation-circle"></i>';
                                echo '</div>';
                                echo '<div class="notification-content">';
                                echo '<div class="notification-title">Venit Expirat</div>';
                                echo '<div class="notification-text">';
                                echo 'Modifică data venitului "' . htmlspecialchars($expired_income['name']) . '" pentru luna următoare!';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }

                            // Get upcoming incomes (next 5 days)
                            $upcoming_sql = "SELECT name, income_date, 
                                          DATEDIFF(income_date, CURDATE()) as days_until
                                          FROM incomes 
                                          WHERE user_id = ? 
                                          AND income_date >= CURDATE()
                                          AND DATEDIFF(income_date, CURDATE()) <= 5
                                          ORDER BY income_date ASC";
                            $upcoming_stmt = $conn->prepare($upcoming_sql);
                            $upcoming_stmt->bind_param("i", $_SESSION['id']);
                            $upcoming_stmt->execute();
                            $upcoming_result = $upcoming_stmt->get_result();

                            while ($upcoming_income = $upcoming_result->fetch_assoc()) {
                                $has_notifications = true;
                                $days_until = $upcoming_income['days_until'];
                                
                                echo '<div class="notification-item">';
                                echo '<div class="notification-icon info">';
                                echo '<i class="fas fa-calendar-check"></i>';
                                echo '</div>';
                                echo '<div class="notification-content">';
                                echo '<div class="notification-title">Venit Apropiat</div>';
                                echo '<div class="notification-text">';
                                
                                if ($days_until == 0) {
                                    echo 'Venitul "' . htmlspecialchars($upcoming_income['name']) . '" este programat pentru astăzi!';
                                } else if ($days_until == 1) {
                                    echo 'Venitul "' . htmlspecialchars($upcoming_income['name']) . '" este programat pentru mâine!';
                                } else {
                                    echo 'Mai sunt ' . $days_until . ' zile până la venitul "' . htmlspecialchars($upcoming_income['name']) . '"!';
                                }
                                
                                echo '</div>';
                                echo '<div class="notification-date">' . date('d.m.Y', strtotime($upcoming_income['income_date'])) . '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            if (!$has_notifications) {
                                echo '<div class="notification-item">';
                                echo '<div class="notification-icon info">';
                                echo '<i class="fas fa-info-circle"></i>';
                                echo '</div>';
                                echo '<div class="notification-content">';
                                echo '<div class="notification-text">Nu sunt notificări pentru moment.</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Statistics and Next Income side by side -->
                <div class="bottom-widgets">
                    <!-- Statistics Widget -->
                    <div class="widget statistics-widget">
                    <div class="widget-header">
                        <h2>Statistici</h2>
                    </div>
                    <div class="widget-body">
                        <!-- Statistics Navigation -->
                        <div class="statistics-navigation mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-sm btn-outline-secondary" onclick="previousStatMonth()">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div class="statistics-period">
                                    <select class="form-select form-select-sm d-inline-block me-2" 
                                            id="statisticsYear" 
                                            onchange="updateStatistics()" 
                                            style="width: 80px; min-width: 80px;">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++) {
                                            $selected = $year == $currentYear ? 'selected' : '';
                                            echo "<option value='$year' $selected>$year</option>";
                                        }
                                        ?>
                                    </select>
                                    <select class="form-select form-select-sm d-inline-block" 
                                            id="statisticsMonth" 
                                            onchange="updateStatistics()" 
                                            style="width: 120px; min-width: 120px;">
                                        <?php
                                        $months = [
                                            '01' => 'Ianuarie', '02' => 'Februarie', '03' => 'Martie',
                                            '04' => 'Aprilie', '05' => 'Mai', '06' => 'Iunie',
                                            '07' => 'Iulie', '08' => 'August', '09' => 'Septembrie',
                                            '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie'
                                        ];
                                        $currentMonth = date('m');
                                        foreach ($months as $value => $name) {
                                            $selected = $value == $currentMonth ? 'selected' : '';
                                            echo "<option value='$value' $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="nextStatMonth()">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Statistics Content -->
                        <div id="statisticsContent">
                            <div class="statistics-grid">
                                <div class="statistic-item">
                            <div class="notification-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Total Luna Selectată</div>
                                <div class="notification-text" id="monthlyTotal">
                                    <?php
                                    $current_month = date('m');
                                    $current_year = date('Y');
                                            
                                            // Get total from both current incomes and history for current month
                                            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM (
                                                    SELECT amount FROM incomes 
                                                    WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                                                    UNION ALL
                                                    SELECT amount FROM income_history 
                                                    WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                                                ) as combined_incomes";
                                            
                                    $stmt = $conn->prepare($sql);
                                            $stmt->bind_param("iiiiii", 
                                                $_SESSION['id'], $current_month, $current_year,
                                                $_SESSION['id'], $current_month, $current_year
                                            );
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    echo number_format($row['total'], 2) . ' RON';
                                    ?>
                                </div>
                            </div>
                        </div>

                                <div class="statistic-item">
                            <div class="notification-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Total Anul Selectat</div>
                                <div class="notification-text" id="yearlyTotal">
                                    <?php
                                            // Get total from both current incomes and history for current year
                                            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM (
                                                    SELECT amount FROM incomes 
                                                    WHERE user_id = ? AND YEAR(income_date) = ?
                                                    UNION ALL
                                                    SELECT amount FROM income_history 
                                                    WHERE user_id = ? AND YEAR(income_date) = ?
                                                ) as combined_incomes";
                                            
                                    $stmt = $conn->prepare($sql);
                                            $stmt->bind_param("iiii", 
                                                $_SESSION['id'], $current_year,
                                                $_SESSION['id'], $current_year
                                            );
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    echo number_format($row['total'], 2) . ' RON';
                                    ?>
                                </div>
                            </div>
                        </div>

                                <div class="statistic-item">
                            <div class="notification-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">Media Lunară (An)</div>
                                <div class="notification-text" id="monthlyAverage">
                                    <?php
                                            // Calculate monthly average including historical data
                                    $sql = "SELECT COALESCE(AVG(monthly_total), 0) as average 
                                           FROM (
                                                       SELECT YEAR(income_date) as year, 
                                                              MONTH(income_date) as month,
                                                              SUM(amount) as monthly_total 
                                                       FROM (
                                                           SELECT income_date, amount 
                                               FROM incomes 
                                               WHERE user_id = ? AND YEAR(income_date) = ? 
                                                           UNION ALL
                                                           SELECT income_date, amount 
                                                           FROM income_history 
                                                           WHERE user_id = ? AND YEAR(income_date) = ?
                                                       ) as combined_incomes
                                                       GROUP BY YEAR(income_date), MONTH(income_date)
                                           ) as monthly_totals";
                                            
                                    $stmt = $conn->prepare($sql);
                                            $stmt->bind_param("iiii", 
                                                $_SESSION['id'], $current_year,
                                                $_SESSION['id'], $current_year
                                            );
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    echo number_format($row['average'], 2) . ' RON';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                        </div>
                </div>
            </div>

                    <!-- Evolution Widget -->
                    <div class="widget evolution-widget">
                        <div class="widget-header">
                            <h2>Evoluție față de luna trecută</h2>
                        </div>
                        <div class="widget-body">
                            <?php
                            // Get current month and previous month totals
                            $current_month = date('m');
                            $current_year = date('Y');
                            $prev_month = $current_month - 1;
                            $prev_year = $current_year;
                            
                            if ($prev_month < 1) {
                                $prev_month = 12;
                                $prev_year--;
                            }
                            
                            // Get current month total
                            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM (
                                    SELECT amount FROM incomes 
                                    WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                                    UNION ALL
                                    SELECT amount FROM income_history 
                                    WHERE user_id = ? AND MONTH(income_date) = ? AND YEAR(income_date) = ?
                                ) as combined_incomes";
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iiiiii", 
                                $_SESSION['id'], $current_month, $current_year,
                                $_SESSION['id'], $current_month, $current_year
                            );
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $current_total = $result->fetch_assoc()['total'];
                            
                            // Get previous month total
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iiiiii", 
                                $_SESSION['id'], $prev_month, $prev_year,
                                $_SESSION['id'], $prev_month, $prev_year
                            );
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $prev_total = $result->fetch_assoc()['total'];
                            
                            // Calculate percentage change
                            if ($prev_total > 0) {
                                $percentage_change = (($current_total - $prev_total) / $prev_total) * 100;
                                $arrow_class = $percentage_change > 0 ? 'up' : ($percentage_change < 0 ? 'down' : 'neutral');
                                $percentage_class = $percentage_change > 0 ? 'positive' : ($percentage_change < 0 ? 'negative' : 'neutral');
                                
                                echo '<div class="evolution-content">';
                                echo '<div class="evolution-percentage ' . $percentage_class . '">';
                                echo '<i class="fas fa-arrow-right evolution-arrow ' . $arrow_class . '"></i>';
                                echo abs(round($percentage_change, 1)) . '%';
                                echo '</div>';
                                echo '<div class="evolution-description">';
                                if ($percentage_change > 0) {
                                    echo 'Creștere față de luna trecută';
                                } else if ($percentage_change < 0) {
                                    echo 'Scădere față de luna trecută';
                                } else {
                                    echo 'Fără modificare';
                                }
                                echo '</div>';
                                echo '<div class="evolution-comparison">';
                                echo '<span>Luna curentă: ' . number_format($current_total, 2) . ' RON</span>';
                                echo '<span>Luna trecută: ' . number_format($prev_total, 2) . ' RON</span>';
                                echo '</div>';
                                echo '</div>';
                            } else if ($current_total > 0) {
                                echo '<div class="evolution-content">';
                                echo '<div class="evolution-percentage positive">';
                                echo '<i class="fas fa-arrow-right evolution-arrow up"></i>';
                                echo '100%';
                                echo '</div>';
                                echo '<div class="evolution-description">Prima lună cu venituri</div>';
                                echo '<div class="evolution-comparison">';
                                echo '<span>Luna curentă: ' . number_format($current_total, 2) . ' RON</span>';
                                echo '<span>Luna trecută: 0.00 RON</span>';
                                echo '</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="evolution-empty">';
                                echo '<i class="fas fa-chart-line"></i>';
                                echo '<p>Nu există date pentru comparație</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer/footer.html'; ?>

    <!-- Income Calendar Modal -->
    <div class="modal fade" id="incomeCalendarModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Calendar Venituri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="calendar-controls mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="previousMonth()">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h6 class="calendar-month-year mb-0"></h6>
                            <button class="btn btn-sm btn-outline-secondary" onclick="nextMonth()">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="calendar-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div class="modal fade" id="addIncomeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adaugă Venit Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addIncomeForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Denumire</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Sumă (RON)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="income_date" class="form-label">Data</label>
                            <input type="date" class="form-control" id="income_date" name="income_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="button" 
                            onclick="saveIncome()"
                            class="btn btn-animate btn-animate-primary">
                        <i class="fas fa-plus me-2"></i>Adaugă
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Income Modal -->
    <div class="modal fade" id="editIncomeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifică Venitul</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editIncomeForm">
                        <input type="hidden" id="edit_income_id" name="income_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Denumire</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">Sumă (RON)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_income_date" class="form-label">Data</label>
                            <input type="date" class="form-control" id="edit_income_date" name="income_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="button" 
                            onclick="updateIncome()"
                            class="btn btn-animate btn-animate-primary">
                        <i class="fas fa-save me-2"></i>Salvează
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div class="modal fade quick-edit-modal" id="quickEditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifică Data Venitului</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickEditForm">
                        <input type="hidden" id="quick_edit_income_id" name="income_id">
                        <div class="mb-3">
                            <label for="quick_edit_income_date" class="form-label">Noua Dată</label>
                            <input type="date" class="form-control date-picker" id="quick_edit_income_date" name="income_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="updateIncomeDate()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Edit Date Modal -->
    <div class="modal fade" id="quickEditDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifică Data Venitului</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickEditDateForm">
                        <input type="hidden" id="quick_edit_income_id" name="income_id">
                        <div class="mb-3">
                            <label for="quick_edit_date" class="form-label">Noua Dată</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="quick_edit_date" 
                                   name="income_date" 
                                   required
                                   onkeydown="return false">
                        </div>
    <script>
                            // Disable all past dates in the date picker
                            const quickEditDate = document.getElementById('quick_edit_date');
                            const today = new Date().toISOString().split('T')[0];
                            quickEditDate.setAttribute('min', today);
                            
                            // Prevent manual input and paste
                            quickEditDate.addEventListener('keydown', (e) => {
                                e.preventDefault();
                                return false;
                            });
                            
                            quickEditDate.addEventListener('paste', (e) => {
                                e.preventDefault();
                                return false;
                            });
                        </script>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="button" 
                            onclick="saveQuickEdit()"
                            class="btn btn-animate btn-animate-primary">
                        <i class="fas fa-save me-2"></i>Salvează
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentCalendarDate = new Date();
    let incomeData = [];
    
    // Array de culori pentru venituri
    const incomeColors = [
        { bg: '#E8F5E9', text: '#2E7D32' }, // Verde
        { bg: '#E3F2FD', text: '#1565C0' }, // Albastru
        { bg: '#FFF3E0', text: '#E65100' }, // Portocaliu
        { bg: '#F3E5F5', text: '#7B1FA2' }, // Mov
        { bg: '#E0F7FA', text: '#00838F' }, // Turcoaz
        { bg: '#FBE9E7', text: '#D84315' }, // Roșu-portocaliu
        { bg: '#F1F8E9', text: '#558B2F' }, // Verde lime
        { bg: '#E8EAF6', text: '#283593' }, // Indigo
        { bg: '#FFF8E1', text: '#FF8F00' }, // Galben
        { bg: '#FCE4EC', text: '#C2185B' }  // Roz
    ];
    
    // Map pentru a păstra culorile consistente pentru fiecare venit
    const incomeColorMap = new Map();

    // Calendar Functions
    function showIncomeCalendar() {
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('incomeCalendarModal'));
        modal.show();
        
        // Load income data for the current month
        loadIncomeData();
    }

    function loadIncomeData() {
        const year = currentCalendarDate.getFullYear();
        const month = currentCalendarDate.getMonth() + 1;
        
        fetch(`get_calendar_incomes.php?year=${year}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    incomeData = data.incomes;
                    renderCalendar();
                }
            })
            .catch(error => console.error('Error loading income data:', error));
    }

    function renderCalendar() {
        const year = currentCalendarDate.getFullYear();
        const month = currentCalendarDate.getMonth();
        
        // Update month/year display
        const monthNames = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 
                          'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
        document.querySelector('.calendar-month-year').textContent = `${monthNames[month]} ${year}`;
        
        // Get first day of month and total days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const totalDays = lastDay.getDate();
        const startingDay = firstDay.getDay();
        
        // Create calendar grid
        let calendarHTML = '<div class="calendar-header">';
        const dayNames = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
        dayNames.forEach(day => {
            calendarHTML += `<div class="calendar-header-cell">${day}</div>`;
        });
        calendarHTML += '</div><div class="calendar-grid">';
        
        // Previous month days
        const prevMonthDays = startingDay;
        const prevMonth = new Date(year, month, 0);
        const prevMonthTotalDays = prevMonth.getDate();
        
        for (let i = prevMonthDays - 1; i >= 0; i--) {
            calendarHTML += `
                <div class="calendar-cell other-month">
                    <div class="calendar-date">${prevMonthTotalDays - i}</div>
                </div>`;
        }
        
        // Current month days
        const today = new Date();
        for (let day = 1; day <= totalDays; day++) {
            const isToday = today.getDate() === day && 
                           today.getMonth() === month && 
                           today.getFullYear() === year;
            
            const dayIncomes = incomeData.filter(income => {
                const incomeDate = new Date(income.income_date);
                return incomeDate.getDate() === day;
            });
            
            calendarHTML += `
                <div class="calendar-cell${isToday ? ' today' : ''}">
                    <div class="calendar-date">${day}</div>
                    ${dayIncomes.map(income => {
                        // Asigură-te că fiecare venit are o culoare consistentă
                        if (!incomeColorMap.has(income.name)) {
                            const colorIndex = incomeColorMap.size % incomeColors.length;
                            incomeColorMap.set(income.name, incomeColors[colorIndex]);
                        }
                        const color = incomeColorMap.get(income.name);
        
        return `
                            <div class="calendar-income${income.is_historical ? ' historical' : ''}"
                                 style="background-color: ${color.bg}; color: ${color.text};">
                                ${income.name}
                                <div class="income-tooltip">
                        <div class="income-name">${income.name}</div>
                                    <div class="income-amount">${formatMoney(income.amount)}</div>
                                    <div class="income-status">${income.is_historical ? 'Venit Istoric' : 'Venit Activ'}</div>
                </div>
            </div>
        `;
                    }).join('')}
                </div>`;
        }
        
        // Next month days
        const remainingDays = 42 - (prevMonthDays + totalDays); // 42 = 6 rows × 7 days
        for (let day = 1; day <= remainingDays; day++) {
            calendarHTML += `
                <div class="calendar-cell other-month">
                    <div class="calendar-date">${day}</div>
                </div>`;
        }
        
        calendarHTML += '</div>';
        document.querySelector('.calendar-container').innerHTML = calendarHTML;
    }

    function previousMonth() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
        loadIncomeData();
    }

    function nextMonth() {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
        loadIncomeData();
    }

    // Utility Functions
    function formatMoney(amount) {
        return parseFloat(amount).toLocaleString('ro-RO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' RON';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ro-RO');
    }

    function showAddIncomeModal() {
        document.getElementById('income_date').valueAsDate = new Date();
        const modal = new bootstrap.Modal(document.getElementById('addIncomeModal'));
        modal.show();
    }

    function saveIncome() {
        console.log('saveIncome function called');
        
        const form = document.getElementById('addIncomeForm');
        if (!form) {
            console.error('Form not found');
            return;
        }
        
        console.log('Form found:', form);
        
        // Validate form before submitting
        if (!form.checkValidity()) {
            console.log('Form validation failed');
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        console.log('Form data created:', {
            name: formData.get('name'),
            amount: formData.get('amount'),
            income_date: formData.get('income_date')
        });
        
        // Disable the submit button
        const submitBtn = document.querySelector('#addIncomeModal .btn-animate-primary');
        if (!submitBtn) {
            console.error('Submit button not found');
            return;
        }
        
        console.log('Submit button found:', submitBtn);
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Se adaugă...';
        
        fetch('add_income.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received:', response);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Close modal first
                const modal = bootstrap.Modal.getInstance(document.getElementById('addIncomeModal'));
                modal.hide();
                
                // Small delay to ensure modal is closed
                setTimeout(() => {
                    // Reload the page to refresh all data
                    window.location.reload();
                }, 100);
            } else {
                throw new Error(data.message || 'A apărut o eroare la adăugarea venitului.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare',
                text: error.message || 'A apărut o eroare la adăugarea venitului.',
                confirmButtonText: 'Ok'
            });
        })
        .finally(() => {
            // Re-enable the submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Adaugă';
        });
    }

    function editIncome(id) {
        const formData = new FormData();
        formData.append('income_id', id);
        
        fetch('get_income.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
                if (data.success) {
                    const income = data.income;
                    document.getElementById('edit_income_id').value = income.id;
                    document.getElementById('edit_name').value = income.name;
                    document.getElementById('edit_amount').value = income.amount;
                    document.getElementById('edit_income_date').value = income.income_date;
                const modal = new bootstrap.Modal(document.getElementById('editIncomeModal'));
                modal.show();
                } else {
                throw new Error(data.message || 'A apărut o eroare la încărcarea datelor.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare',
                text: error.message || 'A apărut o eroare la încărcarea datelor.',
                confirmButtonText: 'Ok'
            });
        });
    }

    function deleteIncome(id) {
        // Store the income ID for deletion
        document.getElementById('incomeIdToDelete').value = id;
        
        // Show confirmation modal
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteIncomeModal'));
        deleteModal.show();
    }

    function confirmDeleteIncome() {
        const incomeId = document.getElementById('incomeIdToDelete').value;
        const formData = new FormData();
        formData.append('income_id', incomeId);
        
        // Disable the delete button
        const deleteBtn = document.querySelector('#deleteIncomeModal .btn-danger');
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Se șterge...';
        
        fetch('delete_income.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload page
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteIncomeModal'));
                modal.hide();
                window.location.reload();
            } else {
                throw new Error(data.message || 'A apărut o eroare la ștergerea venitului.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare',
                text: error.message || 'A apărut o eroare la ștergerea venitului.',
                confirmButtonText: 'Ok'
            });
        })
        .finally(() => {
            // Re-enable the delete button
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Șterge';
        });
    }

    function updateIncome() {
        const form = document.getElementById('editIncomeForm');
        const formData = new FormData(form);
        
        // Validate form before submitting
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Disable the submit button
        const submitBtn = document.querySelector('#editIncomeModal .btn-animate-primary');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Se salvează...';
        
        fetch('edit_income.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
                if (data.success) {
                // Close modal and reload
                const modal = bootstrap.Modal.getInstance(document.getElementById('editIncomeModal'));
                modal.hide();
                window.location.reload();
                } else {
                throw new Error(data.message || 'A apărut o eroare la modificarea venitului.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Eroare',
                text: error.message || 'A apărut o eroare la modificarea venitului.',
                confirmButtonText: 'Ok'
            });
        })
        .finally(() => {
            // Re-enable the submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvează';
        });
    }

document.addEventListener('DOMContentLoaded', function() {
    // Set default date for the add income form
    document.getElementById('income_date').valueAsDate = new Date();
    
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
});

    function confirmIncome(incomeId, event) {
        event.preventDefault();
        
        // Set default date to next month
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        
        // Get the date input element
        const dateInput = document.getElementById('quick_edit_date');
        
        // Set minimum date to today
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        dateInput.min = today.toISOString().split('T')[0];
        
        // Set default value to next month
        dateInput.valueAsDate = nextMonth;
        
        // Set income ID
        document.getElementById('quick_edit_income_id').value = incomeId;
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('quickEditDateModal'));
        modal.show();
    }

    function saveQuickEdit() {
        const form = document.getElementById('quickEditDateForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const incomeId = document.getElementById('quick_edit_income_id').value;
        
        // First save to history
        const historyData = new FormData();
        historyData.append('income_id', incomeId);
        
        fetch('add_to_history.php', {
            method: 'POST',
            body: historyData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Now update the income date
                const updateData = new FormData();
                updateData.append('income_id', incomeId);
                updateData.append('income_date', form.querySelector('[name="income_date"]').value);
                
                return fetch('quick_edit_income.php', {
                    method: 'POST',
                    body: updateData
                });
            } else {
                throw new Error(data.message || 'A apărut o eroare la salvarea în istoric.');
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickEditDateModal'));
                modal.hide();
                window.location.reload();
            } else {
                throw new Error(data.message || 'A apărut o eroare la actualizarea datei.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'A apărut o eroare la procesarea cererii.');
        });
    }

    // Statistics Navigation Functions
    function previousStatMonth() {
        const monthSelect = document.getElementById('statisticsMonth');
        const yearSelect = document.getElementById('statisticsYear');
        
        let currentMonth = parseInt(monthSelect.value);
        let currentYear = parseInt(yearSelect.value);
        
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        
        monthSelect.value = currentMonth.toString().padStart(2, '0');
        yearSelect.value = currentYear.toString();
        
        updateStatistics();
    }

    function nextStatMonth() {
        const monthSelect = document.getElementById('statisticsMonth');
        const yearSelect = document.getElementById('statisticsYear');
        
        let currentMonth = parseInt(monthSelect.value);
        let currentYear = parseInt(yearSelect.value);
        
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        
        monthSelect.value = currentMonth.toString().padStart(2, '0');
        yearSelect.value = currentYear.toString();
        
        updateStatistics();
    }

    function updateStatistics() {
        const month = document.getElementById('statisticsMonth').value;
        const year = document.getElementById('statisticsYear').value;
        
        // Show loading state
        document.getElementById('monthlyTotal').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Se încarcă...';
        document.getElementById('yearlyTotal').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Se încarcă...';
        document.getElementById('monthlyAverage').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Se încarcă...';
        
        fetch('get_income_statistics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                month: month,
                year: year
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('monthlyTotal').textContent = data.monthlyTotal + ' RON';
                document.getElementById('yearlyTotal').textContent = data.yearlyTotal + ' RON';
                document.getElementById('monthlyAverage').textContent = data.monthlyAverage + ' RON';
            } else {
                throw new Error(data.message || 'Eroare la încărcarea statisticilor');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('monthlyTotal').textContent = 'Eroare';
            document.getElementById('yearlyTotal').textContent = 'Eroare';
            document.getElementById('monthlyAverage').textContent = 'Eroare';
        });
    }
</script>

    <!-- Delete Income Confirmation Modal -->
    <div class="modal fade" id="deleteIncomeModal" tabindex="-1" aria-labelledby="deleteIncomeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteIncomeModalLabel">Confirmare ștergere</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 2rem;"></i>
                        <div>
                            <p class="mb-1"><strong>Ești sigur că vrei să ștergi acest venit?</strong></p>
                            <p class="text-muted mb-0">Această acțiune nu poate fi anulată.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="incomeIdToDelete">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="btn btn-animate btn-animate-secondary">
                        <i class="fas fa-times me-2"></i>Anulează
                    </button>
                    <button type="button" 
                            onclick="confirmDeleteIncome()"
                            class="btn btn-animate btn-animate-primary btn-danger">
                        <i class="fas fa-trash me-2"></i>Șterge
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html> 