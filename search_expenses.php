<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

// Initialize the WHERE clause and parameters array
$where_conditions = ['user_id = ?'];
$params = [$_SESSION['id']];
$param_types = 'i'; // Start with user_id as integer

// Process exact date
if (!empty($_GET['exact_date'])) {
    $where_conditions[] = 'DATE(date) = ?';
    $params[] = $_GET['exact_date'];
    $param_types .= 's';
}

// Process month and year
if (!empty($_GET['month_year'])) {
    $date = date_parse($_GET['month_year']);
    $where_conditions[] = 'YEAR(date) = ? AND MONTH(date) = ?';
    $params[] = $date['year'];
    $params[] = $date['month'];
    $param_types .= 'ii';
}

// Process date range
if (!empty($_GET['start_date'])) {
    $where_conditions[] = 'DATE(date) >= ?';
    $params[] = $_GET['start_date'];
    $param_types .= 's';
}
if (!empty($_GET['end_date'])) {
    $where_conditions[] = 'DATE(date) <= ?';
    $params[] = $_GET['end_date'];
    $param_types .= 's';
}

// Process category
if (!empty($_GET['category_id'])) {
    $where_conditions[] = 'category_id = ?';
    $params[] = $_GET['category_id'];
    $param_types .= 'i';
}

// Process amount range
if (isset($_GET['min_amount']) && $_GET['min_amount'] !== '') {
    $where_conditions[] = 'amount >= ?';
    $params[] = $_GET['min_amount'];
    $param_types .= 'd';
}
if (isset($_GET['max_amount']) && $_GET['max_amount'] !== '') {
    $where_conditions[] = 'amount <= ?';
    $params[] = $_GET['max_amount'];
    $param_types .= 'd';
}

// Build the WHERE clause
$where_clause = implode(' AND ', $where_conditions);

// Handle sorting
$allowed_sort_columns = ['date', 'amount', 'category_name'];
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns) ? $_GET['sort_by'] : 'date';
$sort_order = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'desc' ? 'DESC' : 'ASC';

// Prepare the SQL query
$sql = "SELECT e.*, c.name as category_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE $where_clause 
        ORDER BY $sort_by $sort_order";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Start building the HTML response
?>
<!-- Export Buttons -->
<div class="export-buttons mb-3">
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="exportResults('print')">
            <i class="fas fa-print"></i> Printează
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="exportResults('pdf')">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
        <button type="button" class="btn btn-outline-success" onclick="exportResults('excel')">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="exportResults('csv')">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th class="sort-header" data-column="date">
                    Data
                    <?php if ($sort_by === 'date'): ?>
                        <span class="sort-icon"><?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?></span>
                    <?php endif; ?>
                </th>
                <th class="sort-header" data-column="category_name">
                    Categorie
                    <?php if ($sort_by === 'category_name'): ?>
                        <span class="sort-icon"><?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?></span>
                    <?php endif; ?>
                </th>
                <th>Descriere</th>
                <th class="sort-header" data-column="amount">
                    Sumă
                    <?php if ($sort_by === 'amount'): ?>
                        <span class="sort-icon"><?php echo $sort_order === 'ASC' ? '↑' : '↓'; ?></span>
                    <?php endif; ?>
                </th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            while ($row = $result->fetch_assoc()):
                $total += $row['amount'];
            ?>
                <tr>
                    <td><?php echo date('d.m.Y', strtotime($row['date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo number_format($row['amount'], 2, ',', '.') . ' RON'; ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-expense" data-expense-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-expense" data-expense-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                <td colspan="2"><strong><?php echo number_format($total, 2, ',', '.') . ' RON'; ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php if ($result->num_rows === 0): ?>
    <div class="alert alert-info">
        Nu s-au găsit cheltuieli care să corespundă criteriilor de căutare.
    </div>
<?php endif; ?>

<script>
function exportResults(type) {
    // Get current search parameters
    const currentParams = new URLSearchParams(window.location.search);
    const searchParams = $('#searchForm').serialize();
    
    if (type === 'print') {
        // Open in new window for printing
        const exportParams = searchParams + 
            '&export_type=' + type + 
            '&sort_by=' + (currentParams.get('sort_by') || 'date') +
            '&sort_order=' + (currentParams.get('sort_order') || 'asc');
        
        const printWindow = window.open('export_expenses.php?' + exportParams, '_blank');
        printWindow.onload = function() {
            printWindow.print();
        };
    } else {
        // Direct download for other formats
        const exportParams = searchParams + 
            '&export_type=' + type + 
            '&sort_by=' + (currentParams.get('sort_by') || 'date') +
            '&sort_order=' + (currentParams.get('sort_order') || 'asc');
        
        window.location.href = 'export_expenses.php?' + exportParams;
    }
}
</script>

<!-- Print Styles -->
<style media="print">
    .export-buttons,
    .navbar,
    .calendar-container,
    .search-form,
    .no-print {
        display: none !important;
    }
    
    .expenses-table {
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    .table {
        border-collapse: collapse;
        width: 100%;
    }
    
    .table th,
    .table td {
        border: 1px solid #dee2e6;
        padding: 8px;
    }
    
    .table thead th {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
    }
    
    @page {
        size: A4;
        margin: 1cm;
    }
    
    body {
        min-width: 992px !important;
    }
} 