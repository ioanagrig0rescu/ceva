<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $income_id = $_POST['income_id'] ?? '';
    $user_id = $_SESSION['id'];

    if (empty($income_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID-ul venitului este necesar.'
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
                'message' => 'Nu aveți permisiunea de a șterge acest venit.'
            ]);
            exit;
        }

        // Delete income
        $sql = "DELETE FROM incomes WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $income_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Venitul a fost șters cu succes.'
            ]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'A apărut o eroare la ștergerea venitului.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Metodă invalidă.'
    ]);
}
?> 