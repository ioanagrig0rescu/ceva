<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $income_id = $_POST['income_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $income_date = $_POST['income_date'] ?? '';
    $user_id = $_SESSION['id'];

    // Validate inputs
    if (empty($income_id) || empty($name) || empty($amount) || empty($income_date)) {
        echo json_encode([
            'success' => false,
            'message' => 'Toate câmpurile sunt obligatorii.'
        ]);
        exit;
    }

    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Suma trebuie să fie un număr pozitiv.'
        ]);
        exit;
    }

    try {
        // Verify ownership
        $check_sql = "SELECT user_id FROM incomes WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $income_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $income = $result->fetch_assoc();

        if (!$income || $income['user_id'] != $user_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Nu aveți permisiunea de a modifica acest venit.'
            ]);
            exit;
        }

        // Update income
        $sql = "UPDATE incomes SET name = ?, amount = ?, income_date = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsii", $name, $amount, $income_date, $income_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Venitul a fost modificat cu succes.'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'A apărut o eroare la modificarea venitului.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Metodă invalidă.'
    ]);
}
?> 