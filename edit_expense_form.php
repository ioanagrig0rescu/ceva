<?php
session_start();
require_once 'database/db.php';
require_once 'sesion-check/check.php';

// Verificăm dacă avem ID-ul cheltuielii
if (!isset($_GET['id'])) {
    die("ID cheltuială lipsă");
}

$expense_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Preluăm datele cheltuielii
$stmt = $conn->prepare("
    SELECT e.*, c.name as category_name 
    FROM expenses e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.id = ? AND e.user_id = ?
");
$stmt->bind_param("ii", $expense_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Cheltuiala nu a fost găsită sau nu aveți permisiunea să o editați");
}

$expense = $result->fetch_assoc();

// Preluăm toate categoriile
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editare Cheltuială</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Editare Cheltuială</h5>
                    </div>
                    <div class="card-body">
                        <form id="editExpenseForm" action="update_expense.php" method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($expense['id']) ?>">
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Categorie</label>
                                <select class="form-select" name="category_id" id="category" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['id']) ?>" 
                                                <?= $category['id'] == $expense['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Sumă (RON)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required 
                                       value="<?= htmlspecialchars($expense['amount']) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descriere</label>
                                <textarea class="form-control" id="description" name="description" 
                                          required><?= htmlspecialchars($expense['description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Data</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       required value="<?= htmlspecialchars($expense['date']) ?>">
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="calendar.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-primary">Salvează Modificările</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('editExpenseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('update_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cheltuiala a fost actualizată cu succes!');
                window.location.href = 'calendar.php';
            } else {
                alert(data.message || 'A apărut o eroare la actualizarea cheltuielii.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la actualizarea cheltuielii.');
        });
    });
    </script>
</body>
</html> 