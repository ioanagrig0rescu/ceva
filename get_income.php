<?php
session_start();
include 'database/db.php';
include 'sesion-check/check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['income_id'])) {
    $income_id = $_POST['income_id'];
    $user_id = $_SESSION['id'];

    try {
        $sql = "SELECT id, name, amount, income_date FROM incomes WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $income_id, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $income = $result->fetch_assoc();
            
            if ($income) {
                // Format the date for the form (YYYY-MM-DD)
                $income['income_date'] = date('Y-m-d', strtotime($income['income_date']));
                
                echo json_encode([
                    'success' => true,
                    'income' => $income
                ]);
            } else {
                throw new Exception('Venitul nu a fost găsit.');
            }
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Eroare la obținerea venitului: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Cerere invalidă'
    ]);
}
?> 