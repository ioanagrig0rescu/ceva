<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set UTF-8 encoding for all output
header('Content-Type: text/html; charset=utf-8');

include 'sesion-check/check.php';
include 'database/db.php';

// Set UTF-8 for database connection
mysqli_set_charset($conn, "utf8mb4");

// Get current month and year, or from query parameters if set
$current_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$current_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Get first and last day of current month
$first_day = date('Y-m-01', strtotime("$current_year-$current_month-01"));
$last_day = date('Y-m-t', strtotime("$current_year-$current_month-01"));

// Get all expenses for the current month using prepared statement
$sql = "SELECT e.id, e.description, e.date, e.amount, e.category_id, c.name as category_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.user_id = ? AND e.date BETWEEN ? AND ?
        ORDER BY e.date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $_SESSION['id'], $first_day, $last_day);
$stmt->execute();
$result = $stmt->get_result();

// Group expenses by date
$expenses_by_date = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    if (!isset($expenses_by_date[$date])) {
        $expenses_by_date[$date] = [];
    }
    
    // Add category color class
    $eventColor = '';
    switch(mb_strtolower($row['category_name'], 'UTF-8')) {
        case 'locuință':
            $eventColor = 'bg-primary'; // Albastru
            break;
        case 'alimentație':
            $eventColor = 'bg-success'; // Verde
            break;
        case 'transport':
            $eventColor = 'bg-info'; // Albastru deschis
            break;
        case 'sănătate':
            $eventColor = 'bg-danger'; // Roșu
            break;
        case 'shopping':
            $eventColor = 'bg-warning'; // Galben
            break;
        case 'timp liber':
            $eventColor = 'bg-purple'; // Violet
            break;
        case 'educație':
            $eventColor = 'bg-teal'; // Turcoaz
            break;
        case 'familie':
            $eventColor = 'bg-pink'; // Roz
            break;
        case 'pets':
            $eventColor = 'bg-brown'; // Maro
            break;
        case 'cadouri':
            $eventColor = 'bg-orange'; // Portocaliu
            break;
        case 'călătorii':
            $eventColor = 'bg-cyan'; // Cyan
            break;
        case 'servicii & abonamente':
            $eventColor = 'bg-indigo'; // Indigo
            break;
        case 'diverse':
            $eventColor = 'bg-secondary'; // Gri
            break;
        default:
            $eventColor = 'bg-secondary'; // Gri pentru categorii necunoscute
    }
    $row['color_class'] = $eventColor;
    $expenses_by_date[$date][] = $row;
}

// Get all categories for the form using prepared statement
$categories_sql = "SELECT DISTINCT id, name FROM categories ORDER BY name ASC";
$stmt = $conn->prepare($categories_sql);
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = [];

while ($row = $categories_result->fetch_assoc()) {
    $categories[] = [
        'id' => $row['id'],
        'name' => $row['name'] // No need for htmlspecialchars here as we'll do it in the HTML
    ];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Calendar - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&subset=latin-ext" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ro.js"></script>
    
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

        /* Calendar Styles */
        .container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 4rem;
        }

        /* Sort Dropdown Styles */
        #sortButton {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.6);
            color: #4a5568;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        #sortButton:hover {
            background: linear-gradient(to bottom, #ffffff, #f8f9fa);
            border-color: rgba(203, 213, 224, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        #sortButton i {
            color: #667eea;
        }

        #sortDropdown {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(226, 232, 240, 0.89);
            padding: 0.5rem;
            min-width: 220px;
            z-index: 50;
            backdrop-filter: blur(10px);
        }

        #sortDropdown .sort-group {
            padding: 0.5rem 0;
        }

        #sortDropdown .sort-group:not(:last-child) {
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        #sortDropdown button {
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            color: #64748b;
            font-size: 0.875rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            background: transparent;
        }

        #sortDropdown button:hover {
            background: rgba(248, 249, 250, 0.5);
            color: #667eea;
            border-color: rgba(226, 232, 240, 0.4);
        }

        #sortDropdown button i {
            width: 16px;
            color: #94a3b8;
            font-size: 0.875rem;
            transition: color 0.2s ease;
        }

        #sortDropdown button:hover i {
            color: #667eea;
        }

        #sortDropdown button.active {
            background: rgba(248, 249, 250, 0.7);
            color: #667eea;
            font-weight: 500;
            border-color: rgba(226, 232, 240, 0.6);
        }

        #sortDropdown .sort-group-title {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .calendar-container {
            width: 100%;
            max-width: 1100px;
            overflow-x: hidden;
            margin: 2rem auto 0;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calendar-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .calendar-day-header {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
            border-radius: 8px;
        }
        
        .calendar-day {
            height: 150px;
            padding: 10px;
            border: 1px solid #dee2e6;
            background-color: white;
            cursor: pointer;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .calendar-day:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .calendar-day.other-month {
            background-color: #f8f9fa;
            color: #6c757d;
            opacity: 0.7;
        }
        
        .calendar-day .date {
            font-weight: 500;
            margin-bottom: 5px;
            position: sticky;
            top: 0;
            background: inherit;
            padding: 4px 0;
            z-index: 1;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
            color: #495057;
        }

        .expense-item {
            font-size: 0.85rem;
            padding: 4px 8px;
            margin-bottom: 3px;
            border-radius: 6px;
            color: white;
            transition: all 0.2s ease;
        }

        .expense-item:hover {
            transform: translateX(2px);
        }

        .expense-total {
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: auto;
            padding: 4px 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            color: #495057;
        }

        .today {
            background-color: #e8f4ff;
            border: 2px solid #0d6efd;
            box-shadow: 0 0 0 1px rgba(13, 110, 253, 0.1);
        }

        .future-date {
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .future-date:hover {
            background-color: #f8f9fa !important;
            transform: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .future-date .date {
            color: #adb5bd;
        }

        /* Advanced Search Widget Styles */
        .advanced-search-widget {
            width: 100%;
            max-width: 1100px;
            margin: 2rem auto;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

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

        .modal .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modal .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .modal .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }

        .modal .btn-secondary {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #4a5568;
        }

        .modal .btn-secondary:hover {
            background: #e9ecef;
        }

        /* Existing Expenses List Styling */
        #existingExpenses {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        #existingExpenses h6 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .list-group-item {
            border-radius: 8px !important;
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef !important;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .list-group-item:hover {
            background: #f8f9fa;
        }

        .list-group-item .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .list-group-item .btn-sm {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .list-group-item .btn-outline-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }

        .list-group-item .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
        }

        /* Category Colors */
        .bg-purple { background-color: #6f42c1; }
        .bg-pink { background-color: #e83e8c; }
        .bg-orange { background-color: #fd7e14; }
        .bg-teal { background-color: #20c997; }
        .bg-brown { background-color: #795548; }
        .bg-cyan { background-color: #17a2b8; }
        .bg-indigo { background-color: #6610f2; }

        /* Custom styles for flatpickr */
        .flatpickr-calendar {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .flatpickr-day {
            border-radius: 8px;
            margin: 2px;
            height: 36px;
            line-height: 36px;
        }

        .flatpickr-day.selected {
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .flatpickr-day.inRange {
            background: #e8f4ff;
            border-color: #e8f4ff;
            box-shadow: -5px 0 0 #e8f4ff, 5px 0 0 #e8f4ff;
        }

        .flatpickr-day.today {
            border-color: #0d6efd;
        }

        .flatpickr-months .flatpickr-month {
            background: #f8f9fa;
            color: #616f80;
        }

        .flatpickr-current-month {
            font-size: 1.2em;
            padding: 10px 0;
        }

        .flatpickr-weekday {
            background: #f8f9fa;
            color: #616f80;
            font-weight: 600;
        }

        .flatpickr-monthSelect-month {
            border-radius: 8px;
            margin: 2px;
        }

        .flatpickr-monthSelect-month.selected {
            background: #0d6efd;
        }

        .month-navigation {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 6rem auto 1rem;
            max-width: 1100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .month-navigation .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .month-navigation .nav-selects {
            display: flex;
            gap: 1rem;
        }

        .month-navigation .nav-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .month-navigation .nav-btn:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .nav-select {
            padding: 0.5rem 2.5rem 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 150px;
        }

        .nav-select:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }

        .nav-select:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .nav-selects {
            display: flex;
            gap: 1rem;
            position: relative;
        }

        .nav-selects select {
            padding-right: 2.5rem;
            min-width: 160px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.25rem;
        }

        .nav-selects select:focus {
            outline: none;
            border-color: transparent;
            box-shadow: 0 0 0 2px rgba(66, 165, 245, 0.2);
        }

        .nav-selects select option {
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 640px) {
            .nav-selects {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-selects select {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar/navbar.php'; ?>

<div class="container">
    <!-- Month Navigation -->
    <div class="month-navigation">
        <div class="nav-buttons">
            <a href="?month=<?php 
                $prev_month = $current_month - 1;
                $prev_year = $current_year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }
                echo $prev_month . '&year=' . $prev_year; 
            ?>" class="nav-btn">
                <i class="fas fa-chevron-left"></i>
                Luna trecută
            </a>
            </div>

        <div class="nav-selects">
            <select id="yearSelect" class="px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#42a5f5] focus:border-transparent transition-all duration-300 ease-in-out appearance-none cursor-pointer hover:border-gray-300 hover:shadow-sm" aria-label="Selectează anul">
                    <?php
                    $start_year = 2020; // You can adjust this
                    $end_year = intval(date('Y')) + 1;
                    for($year = $start_year; $year <= $end_year; $year++) {
                        $selected = ($year == $current_year) ? 'selected' : '';
                        echo "<option value=\"$year\" $selected>$year</option>";
                    }
                ?>
            </select>
            <select id="monthSelect" class="px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#42a5f5] focus:border-transparent transition-all duration-300 ease-in-out appearance-none cursor-pointer hover:border-gray-300 hover:shadow-sm" aria-label="Selectează luna">
                <?php
                    $months = array(
                        1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie',
                        4 => 'Aprilie', 5 => 'Mai', 6 => 'Iunie',
                        7 => 'Iulie', 8 => 'August', 9 => 'Septembrie',
                        10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
                    );
                    foreach($months as $num => $name) {
                        $selected = ($num == $current_month) ? 'selected' : '';
                        echo "<option value=\"$num\" $selected>$name</option>";
                    }
                    ?>
                </select>
        </div>

        <div class="nav-buttons">
            <a href="?month=<?php 
                $next_month = $current_month + 1;
                $next_year = $current_year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }
                echo $next_month . '&year=' . $next_year; 
            ?>" class="nav-btn">
                Luna următoare
                <i class="fas fa-chevron-right"></i>
            </a>
            </div>
        </div>

        <!-- Calendar Grid -->
    <div class="calendar-container">
        <div class="calendar-grid">
            <?php
            // Display day headers
            $days = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
            foreach ($days as $day) {
                echo "<div class='calendar-day-header'>$day</div>";
            }

            // Get the first day of the month
            $first_day_of_month = date('N', strtotime($first_day));
            $last_day_of_month = date('t', strtotime($first_day));
            
            // Calculate previous month's days
            $prev_month = date('Y-m-d', strtotime('-1 month', strtotime($first_day)));
            $prev_month_days = date('t', strtotime($prev_month));
            $prev_month_start = $prev_month_days - $first_day_of_month + 2;

            // Display calendar days
            $day_counter = 1;
            $next_month_day = 1;

            for ($i = 1; $i <= 42; $i++) {
                if ($i < $first_day_of_month) {
                    // Previous month
                    $day = $prev_month_start++;
                    $prev_date = date('Y-m-d', strtotime("$current_year-" . ($current_month-1) . "-$day"));
                    $is_future = $prev_date > date('Y-m-d');
                    echo "<div class='calendar-day other-month" . ($is_future ? " future-date" : "") . "' " . 
                         (!$is_future ? "onclick='showExpenseModal(\"$prev_date\")'" : "") . ">";
                } elseif ($day_counter <= $last_day_of_month) {
                    // Current month
                    $current_date = date('Y-m-d', strtotime("$current_year-$current_month-$day_counter"));
                    $is_today = $current_date == date('Y-m-d') ? 'today' : '';
                    $is_future = $current_date > date('Y-m-d');
                    echo "<div class='calendar-day $is_today" . ($is_future ? " future-date" : "") . "' " . 
                         (!$is_future ? "onclick='showExpenseModal(\"$current_date\")'" : "") . ">";
                    
                    // Display expenses for this day
                    if (isset($expenses_by_date[$current_date])) {
                        $total = 0;
                        foreach ($expenses_by_date[$current_date] as $expense) {
                            $total += $expense['amount'];
                            echo "<div class='expense-item {$expense['color_class']}' title='{$expense['description']}'>";
                            echo "{$expense['category_name']}: {$expense['amount']} RON";
                            echo "</div>";
                        }
                        echo "<div class='expense-total'>Total: $total RON</div>";
                    }
                    $day = $day_counter++;
                } else {
                    // Next month
                    $day = $next_month_day++;
                    $next_date = date('Y-m-d', strtotime("$current_year-" . ($current_month+1) . "-$day"));
                    $is_future = $next_date > date('Y-m-d');
                    echo "<div class='calendar-day other-month" . ($is_future ? " future-date" : "") . "' " . 
                         (!$is_future ? "onclick='showExpenseModal(\"$next_date\")'" : "") . ">";
                }
                echo "<div class='date'>$day</div>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <!-- Advanced Search Interface -->
    <div class="advanced-search-widget">
        <div class="flex items-center justify-between mb-6">
            <h4 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-search mr-2"></i>Căutare Avansată
            </h4>
            <button id="toggleSearch" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        
        <div id="searchForm" class="space-y-6">
            <!-- Date Range Picker -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="far fa-calendar-alt mr-2"></i>Interval de Date
                    </label>
                    <input type="text" 
                           id="dateRange" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Selectează interval de date">
                </div>
                
                <!-- Category Dropdown -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2"></i>Categoria
                    </label>
                    <select id="categoryFilter" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent appearance-none">
                        <option value="">Toate categoriile</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none mt-7">
                        <i class="fas fa-chevron-down text-gray-500"></i>
                    </div>
                </div>
            </div>

            <!-- Amount Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-coins mr-2"></i>Sumă Minimă (RON)
                    </label>
                    <input type="number" 
                           id="minAmount" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="ex: 100"
                           min="0"
                           step="0.01">
                </div>
                
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-coins mr-2"></i>Sumă Maximă (RON)
                    </label>
                    <input type="number" 
                           id="maxAmount" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="ex: 1000"
                           min="0"
                           step="0.01">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-end mt-6">
                <button type="button" 
                        onclick="resetSearch()"
                        class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                    <i class="fas fa-undo-alt mr-2"></i>Resetează
                </button>
                <button type="button"
                        onclick="performSearch()"
                        class="w-full sm:w-auto px-6 py-2 bg-[#42a5f5B3] text-white rounded-lg hover:bg-[#42a5f5CC] hover:shadow-md hover:translate-y-[-2px] hover:shadow-[#42a5f5]/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#42a5f5] transition-all duration-300 ease-in-out border border-[#42a5f5]">
                    <i class="fas fa-search mr-2"></i>Caută
                </button>
            </div>

            <!-- Search Results -->
            <div id="searchResults" class="mt-8 hidden">
                <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
                    <div class="flex items-center gap-4">
                        <h5 class="text-lg font-semibold text-gray-800">Rezultate Căutare</h5>
                        <div class="relative">
                            <button id="sortButton">
                                <i class="fas fa-sort-amount-down"></i>
                                <span>Ordonează rezultatele</span>
                                <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                            </button>
                            <div id="sortDropdown" class="hidden absolute left-0 mt-2">
                                <!-- Sort dropdown content -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Buttons -->
                    <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">
                        <button onclick="exportSearchResults('pdf')" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                            <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                            PDF
                        </button>
                        <button onclick="exportSearchResults('csv')" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                            <i class="fas fa-file-csv text-green-600 mr-2"></i>
                            CSV
                        </button>
                        <button onclick="exportSearchResults('excel')" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                            <i class="fas fa-file-excel text-green-700 mr-2"></i>
                            Excel
                        </button>
                        <button onclick="printSearchResults()" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                            <i class="fas fa-print text-blue-600 mr-2"></i>
                            Printează
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categorie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sumă</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descriere</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="searchResultsBody">
                            <!-- Results will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Expenses -->
    <div class="modal fade" id="expenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cheltuieli pentru <span id="selectedDate"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Existing Expenses List -->
                    <div id="existingExpenses">
                        <h6>Cheltuieli existente:</h6>
                        <div id="expensesList"></div>
                    </div>

                    <!-- Add New Expense Form -->
                    <h6>Adaugă cheltuială nouă:</h6>
                    <form id="expenseForm">
                        <input type="hidden" name="date" id="expenseDate">
                        <div class="mb-3">
                            <label class="form-label">Categorie</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Alege categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sumă (RON)</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descriere</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>Închide
                    </button>
                    <button type="button" 
                            onclick="saveExpense()" 
                            class="w-full sm:w-auto px-6 py-2 bg-[#42a5f5B3] text-white rounded-lg hover:bg-[#42a5f5CC] hover:shadow-md hover:translate-y-[-2px] hover:shadow-[#42a5f5]/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#42a5f5] transition-all duration-300 ease-in-out border border-[#42a5f5]">
                        <i class="fas fa-save mr-2"></i>Salvează
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Editing Expenses -->
    <div class="modal fade" id="editExpenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editare cheltuială</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editExpenseForm">
                        <input type="hidden" name="id" id="editExpenseId">
                        <input type="hidden" name="date" id="editExpenseDate">
                        
                        <div class="mb-3">
                            <label class="form-label">Categorie</label>
                            <select class="form-select" name="category_id" id="editExpenseCategory" required>
                                <option value="">Alege categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sumă (RON)</label>
                            <input type="number" class="form-control" name="amount" id="editExpenseAmount" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descriere</label>
                            <textarea class="form-control" name="description" id="editExpenseDescription" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>Anulează
                    </button>
                    <button type="button" 
                            onclick="updateExpense()" 
                            class="w-full sm:w-auto px-6 py-2 bg-[#42a5f5B3] text-white rounded-lg hover:bg-[#42a5f5CC] hover:shadow-md hover:translate-y-[-2px] hover:shadow-[#42a5f5]/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#42a5f5] transition-all duration-300 ease-in-out border border-[#42a5f5]">
                        <i class="fas fa-save mr-2"></i>Salvează modificările
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal for expense details after the other modals -->
    <div class="modal fade" id="expenseDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalii Cheltuială</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="text-sm text-gray-600">Data:</label>
                        <p id="detailsDate" class="font-medium"></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-sm text-gray-600">Categorie:</label>
                        <p id="detailsCategory" class="font-medium"></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-sm text-gray-600">Sumă:</label>
                        <p id="detailsAmount" class="font-medium"></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-sm text-gray-600">Descriere:</label>
                        <p id="detailsDescription" class="font-medium"></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-sm text-gray-600">Data adăugării:</label>
                        <p id="detailsCreatedAt" class="font-medium"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" 
                            data-bs-dismiss="modal"
                            class="w-full sm:w-auto px-6 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 hover:shadow-md hover:translate-y-[-2px] hover:border-[#42a5f5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-300 ease-in-out">
                        <i class="fas fa-times mr-2"></i>Închide
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adaugă footer-ul -->
<?php include 'footer/footer.html'; ?>

<!-- Bootstrap și scripturi necesare -->
<script src="calendar/expense-editor.js"></script>

<script>
    // Add global variables for sorting
    let currentSortField = 'date';
    let currentSortDirection = 'desc';
    let searchResults = [];

    // Initialize date range picker
    const dateRangePicker = flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        locale: "ro",
        minDate: "2020-01-01",
        maxDate: new Date().fp_incr(365),
        showMonths: 1,
        animate: true,
        theme: "material_blue",
        prevArrow: '<i class="fas fa-chevron-left"></i>',
        nextArrow: '<i class="fas fa-chevron-right"></i>',
        onChange: function(selectedDates, dateStr, instance) {
            const searchBtn = document.querySelector('button[onclick="performSearch()"]');
            searchBtn.disabled = selectedDates.length !== 2;
        }
    });

    // Funcții pentru calendar
    function saveExpense() {
        const form = document.getElementById('expenseForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        // Debug log
        console.log('Sending data:', {
            date: formData.get('date'),
            category_id: formData.get('category_id'),
            amount: formData.get('amount'),
            description: formData.get('description')
        });

        fetch('add_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Debug log
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            // Debug log
            console.log('Response data:', data);
            
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('expenseModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Eroare la salvarea cheltuielii: ' + (data.message || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la salvarea cheltuielii.');
        });
    }

    function editExpense(id, date, categoryId, amount, description) {
        document.getElementById('editExpenseId').value = id;
        document.getElementById('editExpenseDate').value = date;
        document.getElementById('editExpenseCategory').value = categoryId;
        document.getElementById('editExpenseAmount').value = amount;
        document.getElementById('editExpenseDescription').value = description;

        const currentModal = bootstrap.Modal.getInstance(document.getElementById('expenseModal'));
        if (currentModal) {
            currentModal.hide();
        }

        const editModal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        editModal.show();
    }

    function updateExpense() {
        const form = document.getElementById('editExpenseForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        fetch('update_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Eroare la actualizarea cheltuielii: ' + (data.message || 'Eroare necunoscută'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la actualizarea cheltuielii.');
        });
    }

    function deleteExpense(id) {
        if (confirm('Ești sigur că vrei să ștergi această cheltuială?')) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('delete_expense.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Eroare la ștergerea cheltuielii: ' + (data.message || 'Eroare necunoscută'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la ștergerea cheltuielii.');
            });
        }
    }

    // Toggle search form visibility
    document.getElementById('toggleSearch').addEventListener('click', function() {
        const searchForm = document.getElementById('searchForm');
        const icon = this.querySelector('i');
        
        if (searchForm.classList.contains('hidden')) {
            searchForm.classList.remove('hidden');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            // Nu mai ascundem rezultatele când extindem formularul
            // document.getElementById('searchResults').classList.add('hidden');
        } else {
            searchForm.classList.add('hidden');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            // Nu mai ascundem rezultatele când retragem formularul
            // document.getElementById('searchResults').classList.add('hidden');
        }
    });

    // Reset search form
    function resetSearch() {
        dateRangePicker.clear();
        document.getElementById('categoryFilter').value = '';
        document.getElementById('minAmount').value = '';
        document.getElementById('maxAmount').value = '';
        document.getElementById('searchResults').classList.add('hidden');
        document.getElementById('searchResultsBody').innerHTML = '';
    }

    // Function to show expense details
    function showExpenseDetails(expense) {
        document.getElementById('detailsDate').textContent = formatDate(expense.date);
        document.getElementById('detailsCategory').textContent = expense.category_name;
        document.getElementById('detailsAmount').textContent = `${expense.amount} RON`;
        document.getElementById('detailsDescription').textContent = expense.description;
        document.getElementById('detailsCreatedAt').textContent = formatDate(expense.created_at);
        
        const detailsModal = new bootstrap.Modal(document.getElementById('expenseDetailsModal'));
        detailsModal.show();
    }

    // Update the search results display
    function performSearch() {
        const selectedDates = dateRangePicker.selectedDates;
        if (selectedDates.length !== 2) {
            alert('Te rog selectează un interval de date valid.');
            return;
        }

        const startDate = selectedDates[0].toISOString().split('T')[0];
        const endDate = selectedDates[1].toISOString().split('T')[0];
        const categoryId = document.getElementById('categoryFilter').value;
        const minAmount = document.getElementById('minAmount').value;
        const maxAmount = document.getElementById('maxAmount').value;

        document.getElementById('searchResultsBody').innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-4 text-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Se încarcă rezultatele...
                </td>
            </tr>
        `;
        document.getElementById('searchResults').classList.remove('hidden');

        fetch('search_expenses.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                category_id: categoryId,
                min_amount: minAmount,
                max_amount: maxAmount
            })
        })
        .then(response => response.json())
        .then(data => {
            searchResults = data; // Store results globally
            displayResults(searchResults); // Display initial results
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('searchResultsBody').innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-red-500">
                        <i class="fas fa-exclamation-circle mr-2"></i>A apărut o eroare la căutare. Te rog încearcă din nou.
                    </td>
                </tr>
            `;
        });
    }

    // Function to display results
    function displayResults(results) {
        const tbody = document.getElementById('searchResultsBody');
        tbody.innerHTML = '';

        if (results.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        Nu s-au găsit rezultate pentru criteriile selectate.
                    </td>
                </tr>
            `;
            return;
        }

        results.forEach(expense => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 cursor-pointer transition-colors duration-150 ease-in-out';
            row.onclick = () => showExpenseDetails(expense);
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${formatDate(expense.date)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${expense.color_class} text-white">
                        ${expense.category_name}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${expense.amount} RON</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 truncate max-w-xs">${expense.description}</div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Function to sort results
    function sortResults(field, direction) {
        const sortDropdown = document.getElementById('sortDropdown');
        sortDropdown.classList.add('hidden');

        // Update current sort parameters
        currentSortField = field;
        currentSortDirection = direction;

        const sortedResults = [...searchResults].sort((a, b) => {
            let comparison = 0;
            
            switch(field) {
                case 'date':
                    comparison = new Date(a.date) - new Date(b.date);
                    break;
                case 'amount':
                    comparison = parseFloat(a.amount) - parseFloat(b.amount);
                    break;
                case 'category':
                    comparison = a.category_name.localeCompare(b.category_name, 'ro');
                    break;
            }
            
            return direction === 'asc' ? comparison : -comparison;
        });

        displayResults(sortedResults);
    }

    // Format date helper
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('ro-RO', options);
    }

    // Export search results
    function exportSearchResults(format) {
        const selectedDates = dateRangePicker.selectedDates;
        if (!selectedDates || selectedDates.length !== 2) {
            alert('Vă rugăm să selectați un interval de date.');
            return;
        }

        const startDate = selectedDates[0];
        const endDate = selectedDates[1];
        
        const categoryId = document.getElementById('categoryFilter').value;
        const minAmount = document.getElementById('minAmount').value;
        const maxAmount = document.getElementById('maxAmount').value;

        const params = new URLSearchParams({
            start_date: startDate.toISOString().split('T')[0],
            end_date: endDate.toISOString().split('T')[0],
            format: format,
            sort_field: currentSortField,
            sort_direction: currentSortDirection
        });

        if (categoryId) params.append('category_id', categoryId);
        if (minAmount) params.append('min_amount', minAmount);
        if (maxAmount) params.append('max_amount', maxAmount);

        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'export_filtered_expenses.php';
        
        for (const [key, value] of params.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Print search results
    function printSearchResults() {
        const selectedDates = dateRangePicker.selectedDates;
        if (!selectedDates || selectedDates.length !== 2) {
            alert('Vă rugăm să selectați un interval de date.');
            return;
        }

        const startDate = selectedDates[0];
        const endDate = selectedDates[1];
        
        const categoryId = document.getElementById('categoryFilter').value;
        const minAmount = document.getElementById('minAmount').value;
        const maxAmount = document.getElementById('maxAmount').value;

        const params = new URLSearchParams({
            start_date: startDate.toISOString().split('T')[0],
            end_date: endDate.toISOString().split('T')[0],
            format: 'print',
            print_mode: 'direct',
            sort_field: currentSortField,
            sort_direction: currentSortDirection
        });

        if (categoryId) params.append('category_id', categoryId);
        if (minAmount) params.append('min_amount', minAmount);
        if (maxAmount) params.append('max_amount', maxAmount);

        const printWindow = window.open(`export_filtered_expenses.php?${params.toString()}`, '_blank');
        if (printWindow) {
            printWindow.onload = function() {
                printWindow.print();
            };
        }
    }

    // Calendar navigation functions
    function changeMonth(delta) {
        const monthSelect = document.getElementById('monthSelect');
        const yearSelect = document.getElementById('yearSelect');
        
        let newMonth = parseInt(monthSelect.value) + delta;
        let newYear = parseInt(yearSelect.value);
        
        if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        } else if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        }
        
        monthSelect.value = newMonth;
        yearSelect.value = newYear;
        
        updateCalendar();
    }

    function updateCalendar() {
        const month = document.getElementById('monthSelect').value;
        const year = document.getElementById('yearSelect').value;
        window.location.href = `calendar.php?month=${month}&year=${year}`;
    }

    function showExpenseModal(date) {
        document.getElementById('selectedDate').textContent = formatDate(date);
        document.getElementById('expenseDate').value = date;
        
        // Resetează formularul
        document.getElementById('expenseForm').reset();
        document.getElementById('expensesList').innerHTML = '';
        
        fetch('get_expenses.php?date=' + date)
            .then(response => response.json())
            .then(data => {
                const expensesList = document.getElementById('expensesList');
                if (data.length === 0) {
                    expensesList.innerHTML = '<p class="text-muted">Nu există cheltuieli pentru această dată.</p>';
                } else {
                    const list = document.createElement('div');
                    list.className = 'list-group';
                    
                    data.forEach(expense => {
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.innerHTML = `
                            <div>
                                <span class="badge bg-${expense.color_class} me-2">${expense.category_name}</span>
                                <span class="text-muted">${expense.description}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="me-3">${expense.amount} RON</span>
                                <button type="button" onclick="editExpense(${expense.id}, '${expense.date}', ${expense.category_id}, ${expense.amount}, '${expense.description.replace(/'/g, "\\'")}')" 
                                        class="btn btn-sm me-2 border border-[#42a5f5] text-[#42a5f5] hover:bg-[#42a5f5B3] hover:text-white transition-all duration-300">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" onclick="deleteExpense(${expense.id})" 
                                        class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        list.appendChild(item);
                    });
                    
                    expensesList.innerHTML = '';
                    expensesList.appendChild(list);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('expensesList').innerHTML = 
                    '<div class="alert alert-danger">A apărut o eroare la încărcarea cheltuielilor.</div>';
            });
        
        const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
        modal.show();
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearSelect = document.getElementById('yearSelect');
    const monthSelect = document.getElementById('monthSelect');

    function updateCalendar() {
        const selectedYear = yearSelect.value;
        const selectedMonth = monthSelect.value;
        window.location.href = `calendar.php?month=${selectedMonth}&year=${selectedYear}`;
    }

    yearSelect.addEventListener('change', updateCalendar);
    monthSelect.addEventListener('change', updateCalendar);

    // Sort button dropdown toggle
    const sortButton = document.getElementById('sortButton');
    const sortDropdown = document.getElementById('sortDropdown');
    
    sortButton.addEventListener('click', function(e) {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!sortDropdown.contains(e.target) && !sortButton.contains(e.target)) {
            sortDropdown.classList.add('hidden');
        }
    });
});
</script>
</body>
</html> 