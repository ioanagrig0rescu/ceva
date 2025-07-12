<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

// Funcție pentru logging
function logDeleteOperation($message, $username = null) {
    $log_message = date('Y-m-d H:i:s') . " - " . ($username ? "[$username] " : "") . $message;
    error_log($log_message, 3, "../venituri_error.log");
}

// Verificare sesiune și metodă HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDeleteOperation("Încercare de acces incorectă - metoda HTTP invalidă");
    echo json_encode(['success' => false, 'message' => 'Metodă de acces invalidă.']);
    exit;
}

if (!isset($_SESSION['username']) || !isset($_SESSION['id'])) {
    logDeleteOperation("Încercare de ștergere cont fără sesiune activă");
    echo json_encode(['success' => false, 'message' => 'Nu ești autentificat.']);
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['id'];
logDeleteOperation("Începe procesul de ștergere cont", $username);

// Verificare conexiune bază de date
if (!$conn || $conn->connect_error) {
    logDeleteOperation("Eroare conexiune bază de date: " . $conn->connect_error, $username);
    echo json_encode(['success' => false, 'message' => 'Eroare la conectarea la baza de date.']);
    exit;
}

try {
    // Verificare existență utilizator
    $check_sql = "SELECT id, profile_photo FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Eroare la pregătirea query-ului de verificare: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        logDeleteOperation("Utilizatorul nu există în baza de date", $username);
        echo json_encode(['success' => false, 'message' => 'Utilizatorul nu există.']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Ștergem contribuțiile pentru toate obiectivele utilizatorului
        $sql = "DELETE gc FROM goal_contributions gc
                INNER JOIN goals g ON gc.goal_id = g.id
                WHERE g.user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea query-ului pentru contribuții: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        logDeleteOperation("Contribuții șterse", $username);
        $stmt->close();

        // 2. Ștergem obiectivele
        $sql = "DELETE FROM goals WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea query-ului pentru obiective: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        logDeleteOperation("Obiective șterse", $username);
        $stmt->close();

        // 3. Ștergem veniturile
        $sql = "DELETE FROM incomes WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea query-ului pentru venituri: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        logDeleteOperation("Venituri șterse", $username);
        $stmt->close();

        // 4. Ștergem cheltuielile
        $sql = "DELETE FROM expenses WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea query-ului pentru cheltuieli: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        logDeleteOperation("Cheltuieli șterse", $username);
        $stmt->close();

        // 5. Ștergere poză de profil
        if ($user['profile_photo'] && $user['profile_photo'] != 'profile-photos/default-avatar.png') {
            $photo_path = '../' . $user['profile_photo'];
            if (file_exists($photo_path)) {
                if (unlink($photo_path)) {
                    logDeleteOperation("Poză de profil ștearsă cu succes: $photo_path", $username);
                } else {
                    logDeleteOperation("Eroare la ștergerea pozei de profil: $photo_path", $username);
                }
            }
        }

        // 6. Ștergere nume de afișare din JSON
        $names_file = '../data/display_names.json';
        if (file_exists($names_file)) {
            $display_names = json_decode(file_get_contents($names_file), true) ?: [];
            if (isset($display_names[$username])) {
                unset($display_names[$username]);
                if (file_put_contents($names_file, json_encode($display_names))) {
                    logDeleteOperation("Nume de afișare șters din JSON", $username);
                } else {
                    logDeleteOperation("Eroare la ștergerea numelui de afișare din JSON", $username);
                }
            }
        }

        // 7. Ștergere cont utilizator
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Eroare la pregătirea query-ului pentru ștergere cont: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            logDeleteOperation("Cont utilizator șters cu succes", $username);
            $conn->commit();
            
            // Curățare sesiune
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-3600, '/');
            }
            session_destroy();
            
            echo json_encode(['success' => true, 'message' => 'Contul tău a fost șters cu succes.']);
        } else {
            throw new Exception("Nu s-a putut șterge contul utilizatorului.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        logDeleteOperation("Eroare în timpul tranzacției: " . $e->getMessage(), $username);
        throw $e;
    }

} catch (Exception $e) {
    logDeleteOperation("Eroare generală la ștergerea contului: " . $e->getMessage(), $username);
    echo json_encode([
        'success' => false,
        'message' => 'A apărut o eroare la ștergerea contului. Te rugăm să încerci din nou.',
        'error' => $e->getMessage()
    ]);
} 