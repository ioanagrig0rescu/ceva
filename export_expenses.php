<?php
session_start();
require 'database/db.php';
require 'sesion-check/check.php';

// Funcție pentru a obține datele filtrate
function getFilteredData($conn) {
    $where_conditions = ['user_id = ?'];
    $params = [$_SESSION['id']];
    $param_types = 'i';

    if (!empty($_GET['exact_date'])) {
        $where_conditions[] = 'DATE(date) = ?';
        $params[] = $_GET['exact_date'];
        $param_types .= 's';
    }

    if (!empty($_GET['month_year'])) {
        $date = date_parse($_GET['month_year']);
        $where_conditions[] = 'YEAR(date) = ? AND MONTH(date) = ?';
        $params[] = $date['year'];
        $params[] = $date['month'];
        $param_types .= 'ii';
    }

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

    if (!empty($_GET['category_id'])) {
        $where_conditions[] = 'category_id = ?';
        $params[] = $_GET['category_id'];
        $param_types .= 'i';
    }

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

    $where_clause = implode(' AND ', $where_conditions);
    
    $allowed_sort_columns = ['date', 'amount', 'category_name'];
    $sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowed_sort_columns) ? $_GET['sort_by'] : 'date';
    $sort_order = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT e.*, c.name as category_name 
            FROM expenses e 
            LEFT JOIN categories c ON e.category_id = c.id 
            WHERE $where_clause 
            ORDER BY $sort_by $sort_order";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// Export ca CSV
function exportCSV($data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cheltuieli.csv"');
    
    $output = fopen('php://output', 'w');
    // BOM pentru Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Antet
    fputcsv($output, ['Data', 'Categorie', 'Descriere', 'Sumă (RON)'], ',');
    
    // Date
    $total = 0;
    while ($row = $data->fetch_assoc()) {
        fputcsv($output, [
            date('d.m.Y', strtotime($row['date'])),
            $row['category_name'],
            $row['description'],
            number_format($row['amount'], 2, ',', '.')
        ], ',');
        $total += $row['amount'];
    }
    
    // Total
    fputcsv($output, ['', '', 'Total:', number_format($total, 2, ',', '.')], ',');
    fclose($output);
    exit;
}

// Export ca HTML pentru printare/PDF
function exportHTML($data) {
    // CSS pentru stilizare
    $css = '
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .text-end { text-align: right; }
        .total { font-weight: bold; }
    </style>';
    
    // Începem HTML-ul
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Lista Cheltuieli</title>
        ' . $css . '
    </head>
    <body>
        <h2>Lista Cheltuieli</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Categorie</th>
                    <th>Descriere</th>
                    <th>Sumă (RON)</th>
                </tr>
            </thead>
            <tbody>';
    
    $total = 0;
    while ($row = $data->fetch_assoc()) {
        $html .= '<tr>
            <td>' . date('d.m.Y', strtotime($row['date'])) . '</td>
            <td>' . htmlspecialchars($row['category_name']) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
            <td class="text-end">' . number_format($row['amount'], 2, ',', '.') . '</td>
        </tr>';
        $total += $row['amount'];
    }
    
    $html .= '<tr class="total">
            <td colspan="3" class="text-end">Total:</td>
            <td class="text-end">' . number_format($total, 2, ',', '.') . '</td>
        </tr>
        </tbody>
        </table>
    </body>
    </html>';
    
    return $html;
}

// Export ca Excel (.xls) - folosim HTML cu extensie .xls
function exportExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="cheltuieli.xls"');
    header('Cache-Control: max-age=0');
    
    echo '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            td, th {
                text-align: left;
                padding: 8px;
                border: 1px solid #000;
            }
            .number {
                mso-number-format:"#,##0.00";
            }
            .total {
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Categorie</th>
                    <th>Descriere</th>
                    <th>Sumă (RON)</th>
                </tr>
            </thead>
            <tbody>';
    
    $total = 0;
    while ($row = $data->fetch_assoc()) {
        echo '<tr>
            <td>' . date('d.m.Y', strtotime($row['date'])) . '</td>
            <td>' . htmlspecialchars($row['category_name']) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
            <td class="number">' . str_replace(',', '.', $row['amount']) . '</td>
        </tr>';
        $total += $row['amount'];
    }
    
    echo '<tr class="total">
            <td colspan="3" style="text-align: right;">Total:</td>
            <td class="number">' . str_replace(',', '.', $total) . '</td>
        </tr>
        </tbody>
        </table>
    </body>
    </html>';
    exit;
}

// Export ca PDF
function exportPDF($data) {
    // Generăm conținutul HTML
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Lista Cheltuieli</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; }
            .text-end { text-align: right; }
            .total { font-weight: bold; }
            h1 { font-size: 18px; margin-bottom: 20px; }
            @media print {
                body { -webkit-print-color-adjust: exact; }
            }
        </style>
    </head>
    <body>
        <div id="content">
            <h1>Lista Cheltuieli</h1>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Categorie</th>
                        <th>Descriere</th>
                        <th>Sumă (RON)</th>
                    </tr>
                </thead>
                <tbody>';

    $total = 0;
    while ($row = $data->fetch_assoc()) {
        $html .= '<tr>
            <td>' . date('d.m.Y', strtotime($row['date'])) . '</td>
            <td>' . htmlspecialchars($row['category_name']) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
            <td class="text-end">' . number_format($row['amount'], 2, ',', '.') . '</td>
        </tr>';
        $total += $row['amount'];
    }

    $html .= '<tr class="total">
                <td colspan="3" class="text-end">Total:</td>
                <td class="text-end">' . number_format($total, 2, ',', '.') . '</td>
            </tr>
            </tbody>
        </table>
        </div>
        <script>
            window.onload = function() {
                var element = document.getElementById("content");
                var opt = {
                    margin: 1,
                    filename: "cheltuieli.pdf",
                    image: { type: "jpeg", quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: "cm", format: "a4", orientation: "portrait" }
                };

                html2pdf().set(opt).from(element).save();
            };
        </script>
    </body>
    </html>';

    echo $html;
    exit;
}

// Procesăm cererea de export
if (isset($_GET['export_type'])) {
    $data = getFilteredData($conn);
    
    switch ($_GET['export_type']) {
        case 'excel':
            exportExcel($data);
            break;
        case 'csv':
            exportCSV($data);
            break;
        case 'pdf':
            exportPDF($data);
            break;
        case 'print':
            echo exportHTML($data);
            break;
    }
    exit;
}