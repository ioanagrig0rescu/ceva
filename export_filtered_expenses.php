<?php
session_start();
require 'database/db.php';
require 'dompdf/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['id'])) {
    die('Unauthorized access');
}

// Get parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$category_id = $_GET['category_id'] ?? '';
$min_amount = $_GET['min_amount'] ?? '';
$max_amount = $_GET['max_amount'] ?? '';
$sort_field = $_GET['sort_field'] ?? 'date';
$sort_direction = $_GET['sort_direction'] ?? 'desc';
$format = $_GET['format'] ?? 'pdf';

// Build query
$sql = "SELECT e.*, c.name as category_name 
        FROM expenses e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.user_id = ? AND e.date BETWEEN ? AND ?";
$params = [$_SESSION['id'], $start_date, $end_date];
$types = "iss";

if (!empty($category_id)) {
    $sql .= " AND e.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($min_amount)) {
    $sql .= " AND e.amount >= ?";
    $params[] = $min_amount;
    $types .= "d";
}

if (!empty($max_amount)) {
    $sql .= " AND e.amount <= ?";
    $params[] = $max_amount;
    $types .= "d";
}

// Add sorting
switch($sort_field) {
    case 'date':
        $sql .= " ORDER BY e.date " . ($sort_direction === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'amount':
        $sql .= " ORDER BY e.amount " . ($sort_direction === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'category':
        $sql .= " ORDER BY c.name " . ($sort_direction === 'asc' ? 'ASC' : 'DESC');
        break;
    default:
        $sql .= " ORDER BY e.date DESC";
}

// Execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);

switch($format) {
    case 'pdf':
        // Create new PDF instance
        $dompdf = new Dompdf();
        
        // Prepare HTML content
        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: DejaVu Sans, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                .header { text-align: center; margin-bottom: 30px; }
                .total { margin-top: 20px; text-align: right; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Raport Cheltuieli</h2>
                <p>Perioada: ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . '</p>
            </div>
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
        foreach ($expenses as $expense) {
            $total += $expense['amount'];
            $html .= '<tr>
                <td>' . date('d.m.Y', strtotime($expense['date'])) . '</td>
                <td>' . htmlspecialchars($expense['category_name']) . '</td>
                <td>' . htmlspecialchars($expense['description']) . '</td>
                <td>' . number_format($expense['amount'], 2, '.', ',') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>
            <div class="total">
                Total: ' . number_format($total, 2, '.', ',') . ' RON
            </div>
        </body>
        </html>';

        // Load HTML content
        $dompdf->loadHtml($html);

        // Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render PDF
        $dompdf->render();

        // Output PDF
        $dompdf->stream("cheltuieli_" . date('Y-m-d') . ".pdf", array("Attachment" => true));
        break;

    case 'csv':
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cheltuieli_' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, ['Data', 'Categorie', 'Descriere', 'Sumă (RON)']);
        
        // Add data
        $total = 0;
        foreach ($expenses as $expense) {
            $total += $expense['amount'];
            fputcsv($output, [
                date('d.m.Y', strtotime($expense['date'])),
                $expense['category_name'],
                $expense['description'],
                number_format($expense['amount'], 2, '.', '')
            ]);
        }
        
        // Add total
        fputcsv($output, ['', '', 'Total:', number_format($total, 2, '.', '')]);
        fclose($output);
        break;

    case 'excel':
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=cheltuieli_' . date('Y-m-d') . '.xls');
        header('Cache-Control: max-age=0');
        
        // Create Excel-compatible HTML
        echo '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style>
                td { mso-number-format:\@; }
                .number { mso-number-format:#,##0.00; }
            </style>
        </head>
        <body>
            <table border="1">
                <tr>
                    <th>Data</th>
                    <th>Categorie</th>
                    <th>Descriere</th>
                    <th>Sumă (RON)</th>
                </tr>';
        
        $total = 0;
        foreach ($expenses as $expense) {
            $total += $expense['amount'];
            echo '<tr>
                <td>' . date('d.m.Y', strtotime($expense['date'])) . '</td>
                <td>' . htmlspecialchars($expense['category_name']) . '</td>
                <td>' . htmlspecialchars($expense['description']) . '</td>
                <td class="number">' . str_replace(',', '.', $expense['amount']) . '</td>
            </tr>';
        }
        
        echo '<tr>
                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                <td class="number"><strong>' . str_replace(',', '.', $total) . '</strong></td>
            </tr>
            </table>
        </body>
        </html>';
        break;

    case 'print':
        // Output HTML for printing
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Lista Cheltuieli</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; }
                .total { font-weight: bold; }
                @media print {
                    body { -webkit-print-color-adjust: exact; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Raport Cheltuieli</h2>
                <p>Perioada: ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . '</p>
            </div>
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
        foreach ($expenses as $expense) {
            $total += $expense['amount'];
            echo '<tr>
                <td>' . date('d.m.Y', strtotime($expense['date'])) . '</td>
                <td>' . htmlspecialchars($expense['category_name']) . '</td>
                <td>' . htmlspecialchars($expense['description']) . '</td>
                <td style="text-align: right;">' . number_format($expense['amount'], 2, ',', '.') . '</td>
            </tr>';
        }

        echo '</tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3" style="text-align: right;">Total:</td>
                    <td style="text-align: right;">' . number_format($total, 2, ',', '.') . '</td>
                </tr>
            </tfoot>
            </table>';

        if (isset($_GET['print_mode']) && $_GET['print_mode'] === 'direct') {
            echo '<script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            </script>';
        }
        
        echo '</body></html>';
        break;
}

exit(); 