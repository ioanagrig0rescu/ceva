<?php
if (!isset($date)) {
    die("Data nu este setată");
}

// Fetch expenses for the given date
$stmt = $conn->prepare("
    SELECT e.*, c.name as category_name 
    FROM expenses e 
    LEFT JOIN categories c ON e.category_id = c.id 
    WHERE e.date = ? AND e.user_id = ?
    ORDER BY e.created_at DESC
");

$stmt->bind_param("si", $date, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$expenses = $result->fetch_all(MYSQLI_ASSOC);

if (count($expenses) === 0) {
    echo '<p class="text-muted">Nu există cheltuieli pentru această zi.</p>';
    return;
}

// Display expenses in a table
?>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Categorie</th>
            <th>Sumă</th>
            <th>Descriere</th>
            <th>Acțiuni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?= htmlspecialchars($expense['category_name']) ?></td>
                <td><?= htmlspecialchars($expense['amount']) ?> RON</td>
                <td><?= htmlspecialchars($expense['description']) ?></td>
                <td>
                    <a href="edit_expense_form.php?id=<?= $expense['id'] ?>" class="btn btn-sm btn-primary me-1">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteExpense(<?= $expense['id'] ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table> 