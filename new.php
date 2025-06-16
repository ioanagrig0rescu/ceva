<?php
ob_start();
session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
include 'calendar/Calendar.php';
include 'navbar/sidebar_old.php';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Creează calendar
$calendar = new Calendar($selected_year . '-' . $selected_month . '-01');

// Încarcă cheltuielile din DB
$stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? AND MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
$stmt->bind_param("sss", $_SESSION['id'], $selected_month, $selected_year);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);

// Adaugă evenimentele în calendar
foreach ($expenses as $expense) {
    $label = $expense['category'] . ': ' . number_format($expense['amount'], 2) . ' RON';
    $calendar->add_event($label, $expense['expense_date'], 1, 'blue');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Record - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css" integrity="sha256-3sPp8BkKUE7QyPSl6VfBByBroQbKxKG7tsusY2mhbVY=" crossorigin="anonymous" />
    <link href="calendar/calendar.css" rel="stylesheet" type="text/css">
    <style>
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "segoe ui", roboto, oxygen, ubuntu, cantarell, "fira sans", "droid sans", "helvetica neue", Arial, sans-serif;
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body {
            background-color: #FFFFFF;
            margin: 0;
        }
        .navtop {
            
            
            width: 100%;
            border: 0;
        }
        .navtop div {
            display: flex;
            margin: 0 auto;
            width: 95%;
            height: 100%;
        }
        .navtop div h1, .navtop div a {
            display: inline-flex;
            align-items: center;
        }
        .navtop div h1 {
            flex: 1;
            font-size: 24px;
            padding: 0;
            margin: 0;
            color: dark;
            font-weight: bold;
        }
        .navtop div a {
            padding: 0 20px;
            text-decoration: none;
            color: #c4c8cc;
            font-weight: bold;
        }
        .navtop div a i {
            padding: 2px 8px 0 0;
        }
        .navtop div a:hover {
            color: #ebedee;
        }
        .content {
            width: 95%;
            margin: 0 auto;
        }
        .content h2 {
            margin: 0;
            padding: 25px 0;
            font-size: 22px;
            border-bottom: 1px solid #ebebeb;
            color: #666666;
        }
    </style>
</head>
<body>



<h2 class="mt-4">Calendarul Cheltuielilor</h2>

<!-- Selectare lună/an -->
<form method="GET" class="row g-2 calendar-nav mb-3">
  <div class="col-md-3">
    <select name="month" class="form-select">
      <?php
      for ($m = 1; $m <= 12; $m++) {
          $selected = str_pad($m, 2, "0", STR_PAD_LEFT) == $selected_month ? 'selected' : '';
          echo "<option value='" . str_pad($m, 2, "0", STR_PAD_LEFT) . "' $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-3">
    <select name="year" class="form-select">
      <?php
      $currentYear = date('Y');
      for ($y = $currentYear - 5; $y <= $currentYear + 5; $y++) {
          $selected = $y == $selected_year ? 'selected' : '';
          echo "<option value='$y' $selected>$y</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-3">
    <button type="submit" class="btn btn-primary" style="width: 100%">Afișează</button>
  </div>
</form>

<!-- Tab-uri pentru calendar și listă -->
<ul class="nav nav-tabs" id="viewTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-view" type="button" role="tab" aria-controls="calendar-view" aria-selected="true">Calendar</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-view" type="button" role="tab" aria-controls="list-view" aria-selected="false">Listă</button>
  </li>
</ul>

<div class="tab-content" id="viewTabsContent">
  <div class="tab-pane fade show active" id="calendar-view" role="tabpanel" aria-labelledby="calendar-tab">
    <?= $calendar ?>
  </div>
  <div class="tab-pane fade" id="list-view" role="tabpanel" aria-labelledby="list-tab">

    <div class="d-flex justify-content-between align-items-center mb-2">
      <input type="text" id="searchTable" placeholder="Caută..." class="form-control table-search">
      <!--<div>
        <a href="export_expenses.php?month=<?= $selected_month ?>&year=<?= $selected_year ?>&format=csv" class="btn btn-outline-success btn-sm me-2">Export CSV</a>
        <a href="export_expenses.php?month=<?= $selected_month ?>&year=<?= $selected_year ?>&format=xlsx" class="btn btn-outline-primary btn-sm">Export Excel</a>
      </div>-->
    </div>

    <table id="expensesTable" class="display nowrap" style="width:100%">
        <thead>
            <tr>
            <th>Data</th>
            <th>Categorie</th>
            <th>Sumă (RON)</th>
            <th>Notițe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $exp): ?>
            <tr>
                <td><?= htmlspecialchars($exp['expense_date']) ?></td>
                <td><?= htmlspecialchars($exp['category']) ?></td>
                <td><?= number_format($exp['amount'], 2) ?></td>
                <td><?= nl2br(htmlspecialchars($exp['notes'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

  </div>
</div>

</div>

<!-- Modal pentru adăugare cheltuială -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="save-expense.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expenseModalLabel">Adaugă cheltuială</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="expense_date" id="expense-date">

        <div id="expense-list"></div>
        <hr>

        <div id="expense-form">
          <div class="mb-3">
            <label for="category" class="form-label">Categorie</label>
            <select name="category" id="category" class="form-select" required>
              <option value="Mâncare">Mâncare</option>
              <option value="Transport">Transport</option>
              <option value="Facturi">Facturi</option>
              <option value="Altele">Altele</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label">Sumă</label>
            <input type="number" name="amount" id="amount" step="0.01" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notițe</label>
            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
        <button type="submit" class="btn btn-primary">Salvează</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Script pentru căutare simplă în tabel
document.getElementById('searchTable').addEventListener('input', function() {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('#expensesTable tbody tr');
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? '' : 'none';
  });
});

// Scriptul pentru afișarea modalului când se dă click pe zi din calendar (din vechi)
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.day_num:not(.ignore)').forEach(function (day) {
    day.addEventListener('click', function () {
      const dayNumber = this.querySelector('span').textContent.trim().padStart(2, '0');
      const year = <?= json_encode($selected_year) ?>;
      const month = <?= json_encode($selected_month) ?>;
      const date = `${year}-${month}-${dayNumber}`;
      document.getElementById('expense-date').value = date;

      fetch('fetch_expenses.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'date=' + encodeURIComponent(date)
      })
      .then(response => response.json())
      .then(data => {
        const expenseListDiv = document.querySelector('#expense-list');

        let expenseList = '';
        if (data.length > 0) {
          data.forEach(exp => {
            expenseList += `
              <div class="alert alert-light d-flex justify-content-between align-items-center">
                <div>
                  <strong>${exp.category}</strong>: ${parseFloat(exp.amount).toFixed(2)} RON
                  ${exp.notes ? `<br><small>${exp.notes}</small>` : ''}
                </div>
                <div>
                  <a href="edit_expense.php?id=${exp.id}" class="btn btn-sm btn-warning">Edit</a>
                  <a href="delete_expense.php?id=${exp.id}" class="btn btn-sm btn-danger" onclick="return confirm('Sigur ștergi această cheltuială?');">Șterge</a>
                </div>
              </div>
            `;
          });
        } else {
          expenseList = `<p class="text-muted">Nicio cheltuială în această zi.</p>`;
        }

        expenseListDiv.innerHTML = expenseList;

        const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
        modal.show();
      });
    });
  });
});
</script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
$(document).ready(function() {
  $('#expensesTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'copyHtml5',
      'csvHtml5',
      'excelHtml5',
      {
        extend: 'pdfHtml5',
        orientation: 'portrait',
        pageSize: 'A4',
        title: 'Export Cheltuieli'
      },
      'print'
    ],
    order: [[0, 'asc']],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ro.json'
    }
  });
});
</script>
</body>
<?php include 'footer/footer.html'; ?>
</html>