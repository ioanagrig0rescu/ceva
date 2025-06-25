<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set UTF-8 encoding for all output
header('Content-Type: text/html; charset=utf-8');

include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Roboto font with support for diacritics -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&subset=latin-ext" rel="stylesheet">
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
            font-family: 'Roboto', Arial, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .calendar-container {
            width: 100%;
            overflow-x: hidden;
            margin: 0 auto;
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
            background-color: #e9ecef;
            font-weight: bold;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
        }
        
        .calendar-day {
            height: 150px; /* Fixed height */
            padding: 10px;
            border: 1px solid #dee2e6;
            background-color: white;
            cursor: pointer;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .calendar-day:hover {
            background-color: #f8f9fa;
        }
        
        .calendar-day.other-month {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .calendar-day .date {
            font-weight: bold;
            margin-bottom: 5px;
            position: sticky;
            top: 0;
            background: inherit;
            padding: 2px 0;
            z-index: 1;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
        }
        
        .expense-item {
            font-size: clamp(0.7rem, 1.2vw, 0.8rem);
            margin-bottom: 2px;
            padding: 2px 5px;
            border-radius: 3px;
            color: white;
            word-break: break-word;
            white-space: normal;
        }
        
        .today {
            background-color: #e8f4f8;
        }
        
        .expense-total {
            color: #dc3545;
            font-weight: bold;
            margin-top: auto;
            font-size: clamp(0.75rem, 1.3vw, 0.9rem);
            position: sticky;
            bottom: 0;
            background: inherit;
            padding: 2px 0;
            z-index: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .calendar-header {
                padding: 10px;
            }

            .calendar-header button {
                padding: 5px 10px;
                font-size: 0.9rem;
            }

            .calendar-header select {
                font-size: 0.9rem;
            }

            .calendar-day {
                padding: 5px;
                height: 120px; /* Slightly smaller height on mobile */
            }
        }

        @media (max-width: 576px) {
            .calendar-header {
                flex-direction: column;
                gap: 10px;
            }

            .calendar-header .d-flex {
                width: 100%;
            }

            .calendar-header select {
                width: 100%;
            }

            .calendar-header button {
                width: 100%;
            }
            
            .calendar-day {
                height: 100px; /* Even smaller height on very small screens */
            }
        }

        /* Navbar Styles */
        .navbar {
            background: #000 !important;
            backdrop-filter: none !important;
        }
        
        .navbar-brand, .nav-link {
            color: #fff !important;
        }
        
        .nav-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: #000 !important;
            }
            .navbar-nav .nav-link {
                color: #fff !important;
            }
            .navbar-nav .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.1) !important;
            }
        }

        .content-wrapper {
            flex: 1;
            padding: 20px 0;
        }

        /* Custom category colors */
        .bg-purple {
            background-color: #6f42c1 !important;
            color: white !important;
        }
        
        .bg-teal {
            background-color: #20c997 !important;
            color: white !important;
        }
        
        .bg-pink {
            background-color: #e83e8c !important;
            color: white !important;
        }
        
        .bg-brown {
            background-color: #795548 !important;
            color: white !important;
        }
        
        .bg-orange {
            background-color: #fd7e14 !important;
            color: white !important;
        }
        
        .bg-cyan {
            background-color: #17a2b8 !important;
            color: white !important;
        }
        
        .bg-indigo {
            background-color: #6610f2 !important;
            color: white !important;
        }

        /* Make text visible on warning (yellow) background */
        .bg-warning {
            color: #000 !important;
        }

        .search-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-form .form-group {
            margin-bottom: 15px;
        }
        
        .expenses-table {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .expenses-table th {
            cursor: pointer;
        }
        
        .expenses-table th:hover {
            background-color: #f8f9fa;
        }
        
        .sort-icon {
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .search-form {
                padding: 15px;
            }
            
            .expenses-table {
                padding: 10px;
            }
        }
    </style>
    <!-- Add flatpickr for better date picking -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Add monthSelect plugin for flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
</head>
<body>

<!-- Include navbar -->
<?php include 'navbar/navbar.php'; ?>

<div class="container mt-5">
    <div class="calendar-container">
        <!-- Calendar Header with Navigation -->
        <div class="calendar-header d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-primary" onclick="changeMonth(-1)">
                    <i class="fas fa-chevron-left"></i> Luna anterioară
                </button>
            </div>
            <div class="d-flex align-items-center">
                <select id="monthSelect" class="form-select me-2" onchange="updateCalendar()">
                    <?php
                    $months = [
                        1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie',
                        4 => 'Aprilie', 5 => 'Mai', 6 => 'Iunie',
                        7 => 'Iulie', 8 => 'August', 9 => 'Septembrie',
                        10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
                    ];
                    foreach ($months as $num => $name) {
                        $selected = $num == $current_month ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    }
                    ?>
                </select>
                <select id="yearSelect" class="form-select" onchange="updateCalendar()">
                    <?php
                    $year_start = $current_year - 5;
                    $year_end = $current_year + 5;
                    for ($year = $year_start; $year <= $year_end; $year++) {
                        $selected = $year == $current_year ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <button class="btn btn-outline-primary" onclick="changeMonth(1)">
                    Luna următoare <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Calendar Grid -->
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
                    echo "<div class='calendar-day other-month' onclick='showExpenseModal(\"" . date('Y-m-d', strtotime("$current_year-" . ($current_month-1) . "-$day")) . "\")'>";
                } elseif ($day_counter <= $last_day_of_month) {
                    // Current month
                    $current_date = date('Y-m-d', strtotime("$current_year-$current_month-$day_counter"));
                    $is_today = $current_date == date('Y-m-d') ? 'today' : '';
                    echo "<div class='calendar-day $is_today' onclick='showExpenseModal(\"$current_date\")'>";
                    
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
                    echo "<div class='calendar-day other-month' onclick='showExpenseModal(\"" . date('Y-m-d', strtotime("$current_year-" . ($current_month+1) . "-$day")) . "\")'>";
                }
                echo "<div class='date'>$day</div>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <!-- Advanced Search Form -->
    <div class="search-form mt-5">
        <h4 class="mb-4">Căutare și Filtrare Avansată</h4>
        <form id="searchForm" method="GET" action="search_expenses.php">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="exactDate">Dată Exactă:</label>
                    <input type="text" class="form-control datepicker" id="exactDate" name="exact_date">
                </div>
                
                <div class="col-md-4 form-group">
                    <label for="monthYear">Luna și Anul:</label>
                    <input type="text" class="form-control monthpicker" id="monthYear" name="month_year">
                </div>
                
                <div class="col-md-4 form-group">
                    <label>Interval de Date:</label>
                    <div class="input-group">
                        <input type="text" class="form-control datepicker" id="startDate" name="start_date" placeholder="De la">
                        <input type="text" class="form-control datepicker" id="endDate" name="end_date" placeholder="Până la">
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4 form-group">
                    <label for="category">Tipul Cheltuielii:</label>
                    <select class="form-select" id="category" name="category_id">
                        <option value="">Toate categoriile</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4 form-group">
                    <label for="minAmount">Sumă Minimă:</label>
                    <input type="number" class="form-control" id="minAmount" name="min_amount" min="0" step="0.01">
                </div>
                
                <div class="col-md-4 form-group">
                    <label for="maxAmount">Sumă Maximă:</label>
                    <input type="number" class="form-control" id="maxAmount" name="max_amount" min="0" step="0.01">
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Caută
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Resetează
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Results Table -->
    <div class="expenses-table mt-4" id="searchResults">
        <!-- Table will be populated via AJAX -->
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
                <div id="existingExpenses" class="mb-4">
                    <h6>Cheltuieli existente:</h6>
                    <div id="expensesList">
                        <?php 
                        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                        include 'expense_list.php'; 
                        ?>
                    </div>
                </div>

                <!-- Add New Expense Form -->
                <h6>Adaugă cheltuială nouă:</h6>
                <form id="expenseForm" method="post" accept-charset="UTF-8">
                    <input type="hidden" name="action" value="add_expense">
                    <input type="hidden" id="expenseDate" name="date">
                    <div class="mb-3">
                        <label class="form-label">Categorie</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Alege categoria</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sumă (RON)</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descriere</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-primary" onclick="saveExpense()">Salvează</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Expenses -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExpenseModalLabel">Editare cheltuială</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" method="post" accept-charset="UTF-8">
                    <input type="hidden" name="action" value="update_expense">
                    <input type="hidden" name="id" id="editExpenseId">
                    <input type="hidden" name="date" id="editExpenseDate">
                    
                    <div class="mb-3">
                        <label for="editExpenseCategory" class="form-label">Categorie</label>
                        <select class="form-select" name="category_id" id="editExpenseCategory" required>
                            <option value="">Alege categoria</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editExpenseAmount" class="form-label">Sumă (RON)</label>
                        <input type="number" class="form-control" name="amount" id="editExpenseAmount" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editExpenseDescription" class="form-label">Descriere</label>
                        <textarea class="form-control" name="description" id="editExpenseDescription" required maxlength="255"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editExpenseDateDisplay" class="form-label">Data</label>
                        <input type="text" class="form-control" id="editExpenseDateDisplay" disabled>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-primary" onclick="updateExpense()">Salvează modificările</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ro.js"></script>

<script>
let expenseModal = null;
let editExpenseModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr for date inputs
    flatpickr(".datepicker", {
        locale: "ro",
        dateFormat: "Y-m-d",
        allowInput: true
    });
    
    flatpickr(".monthpicker", {
        locale: "ro",
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ]
    });
    
    // Handle form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'search_expenses.php',
            method: 'GET',
            data: $(this).serialize(),
            success: function(response) {
                $('#searchResults').html(response);
            },
            error: function() {
                alert('A apărut o eroare la căutare. Vă rugăm încercați din nou.');
            }
        });
    });
    
    // Handle table sorting
    $(document).on('click', '.sort-header', function() {
        const column = $(this).data('column');
        const currentOrder = $(this).data('order') || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // Update sort icons
        $('.sort-icon').remove();
        $(this).append(`<span class="sort-icon">
            ${newOrder === 'asc' ? '↑' : '↓'}
        </span>`);
        
        // Update data-order attribute
        $(this).data('order', newOrder);
        
        // Get current form data and add sorting parameters
        const formData = $('#searchForm').serialize() + `&sort_by=${column}&sort_order=${newOrder}`;
        
        // Reload results with new sorting
        $.ajax({
            url: 'search_expenses.php',
            method: 'GET',
            data: formData,
            success: function(response) {
                $('#searchResults').html(response);
            }
        });
    });

    // Inițializăm modalele
    expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    editExpenseModal = new bootstrap.Modal(document.getElementById('editExpenseModal'));

    // Add event listener for edit button clicks
    document.addEventListener('click', function(event) {
        if (event.target.matches('.edit-expense-btn') || event.target.closest('.edit-expense-btn')) {
            const button = event.target.matches('.edit-expense-btn') ? event.target : event.target.closest('.edit-expense-btn');
            const id = button.dataset.id;
            const categoryId = button.dataset.categoryId;
            const amount = button.dataset.amount;
            const description = button.dataset.description;
            const date = button.dataset.date;
            
            editExpense(id, categoryId, amount, description, date);
        }
    });
});

function showExpenseModal(date) {
    // Setăm data selectată
    document.getElementById('selectedDate').textContent = formatDate(date);
    document.getElementById('expenseDate').value = date;
    
    // Resetăm formularul pentru adăugare nouă
    document.getElementById('expenseForm').reset();
    
    // Încărcăm cheltuielile existente
    fetch(`fetch_expenses.php?date=${date}`)
        .then(response => response.json())
        .then(expenses => {
            const expensesList = document.getElementById('expensesList');
            expensesList.innerHTML = '';
            
            if (!Array.isArray(expenses) || expenses.length === 0) {
                expensesList.innerHTML = '<p class="text-muted">Nu există cheltuieli pentru această zi.</p>';
                return;
            }
            
            const table = document.createElement('table');
            table.className = 'table table-sm';
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>Categorie</th>
                        <th>Sumă</th>
                        <th>Descriere</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
            
            expenses.forEach(expense => {
                const row = table.querySelector('tbody').insertRow();
                row.innerHTML = `
                    <td>${expense.category_name}</td>
                    <td>${expense.amount} RON</td>
                    <td>${expense.description}</td>
                    <td>
                        <a href="edit_expense_form.php?id=${expense.id}" class="btn btn-sm btn-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
            });
            
            expensesList.appendChild(table);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('expensesList').innerHTML = '<p class="text-danger">Eroare la încărcarea cheltuielilor.</p>';
        });
    
    // Deschidem modala
    expenseModal.show();
}

function editExpense(id, categoryId, amount, description, date) {
    // Populăm formularul de editare cu datele existente
    document.getElementById('editExpenseId').value = id;
    document.getElementById('editExpenseCategory').value = categoryId;
    document.getElementById('editExpenseAmount').value = amount;
    document.getElementById('editExpenseDescription').value = description;
    document.getElementById('editExpenseDate').value = date;
    
    // Deschidem modala de editare
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

    fetch('edit_expense.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
            if (editModal) {
                editModal.hide();
            }
            location.reload();
        } else {
            throw new Error(data.message || 'A apărut o eroare la actualizarea cheltuielii.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'A apărut o eroare la actualizarea cheltuielii.');
    });
}

function saveExpense() {
    const form = document.getElementById('expenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    fetch('save-expense.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('expenseModal'));
            if (modal) {
                modal.hide();
            }
            location.reload();
        } else {
            throw new Error(data.message || 'A apărut o eroare la salvarea cheltuielii.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'A apărut o eroare la salvarea cheltuielii.');
    });
}

function deleteExpense(id) {
    if (confirm('Sigur doriți să ștergeți această cheltuială?')) {
        fetch(`delete_expense.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Eroare la ștergerea cheltuielii: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la ștergerea cheltuielii.');
            });
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function changeMonth(delta) {
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    let month = parseInt(monthSelect.value);
    let year = parseInt(yearSelect.value);
    
    month += delta;
    
    if (month > 12) {
        month = 1;
        year++;
    } else if (month < 1) {
        month = 12;
        year--;
    }
    
    monthSelect.value = month;
    yearSelect.value = year;
    updateCalendar();
}

function updateCalendar() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    window.location.href = `calendar.php?month=${month}&year=${year}`;
}
</script>

<!-- Adaugă footer-ul la sfârșitul paginii, înainte de închiderea tag-ului body -->
<?php include 'footer/footer.html'; ?>
</body>
</html> 