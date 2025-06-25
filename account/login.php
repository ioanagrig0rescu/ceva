<?php
ob_start();
session_start();

// Configurare raportare erori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers pentru securitate și cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include '../plugins/bootstrap.html';
include '../database/db.php';

// Inițializare variabile
$error_message = '';
$email_value = '';

// Procesare formular login
if (isset($_POST['submit'])) {
    // Filtrare și validare input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Adresa de email nu este validă!";
    } else {
        // Pregătire și executare query securizat
        $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql); 
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

            // Verificare parolă
        if (password_verify($password, $user['password'])) {
            if ((int)$user['retire'] === 1) {
                    $error_message = "Contul este dezactivat. Vă rugăm contactați departamentul IT.";
                    $email_value = htmlspecialchars($email);
            } else {
                    // Setare sesiune și cookie-uri
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['register_date'] = $user['register_date'];
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];

                    // Cookie-uri cu setări de securitate
                $cookie_options = [
                        'expires' => time() + 86400,
                    'path' => '/',
                        'secure' => true,
                    'httponly' => true,
                        'samesite' => 'Strict'
                ];
                    
                setcookie('username', $user['username'], $cookie_options);
                setcookie('email', $user['email'], $cookie_options);
                setcookie('role', $user['role'], $cookie_options);
                setcookie('loggedin', 'true', $cookie_options);

                    // Redirect bazat pe rol
                    header("Location: " . ($user['role'] === 'admin' ? '../admin/admin.php' : '../home.php'));
                    exit();
                }
            } else {
                $error_message = "Parola introdusă nu este validă!";
                $email_value = htmlspecialchars($email);
            }
        } else {
            $error_message = "Adresa de email nu există în baza de date!";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autentificare - Budget Master</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <style>
        html {
            height: 100%;
        }

        body {
            min-height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            background: #6a11cb;
            background: -webkit-linear-gradient(to right, rgba(106, 17, 203, 1), rgba(37, 117, 252, 1));
            background: linear-gradient(to right, rgba(106, 17, 203, 1), rgba(37, 117, 252, 1));
        }

        .gradient-custom {
            flex: 1;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            text-align: center;
        }

        .card {
            background-color: rgba(33, 37, 41, 0.9) !important;
            border: none !important;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .form-control-lg {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }

        .form-control-lg:focus {
            background-color: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1) !important;
        }

        .form-control-lg::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body>
<section class="gradient-custom">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card bg-dark text-white" style="border-radius: 1rem;">
          <div class="card-body p-5 text-center">
            <div class="mb-md-5 mt-md-4 pb-5">
              <h2 class="fw-bold mb-2 text-uppercase">Budget Master</h2>
                            <h3 class="fw-bold mb-4">Autentificare</h3>

                            <?php if ($error_message): ?>
                                <div class="error-message">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" novalidate>
                <div class="form-outline form-white mb-4">
                                    <input type="email" 
                                           name="email" 
                                           class="form-control form-control-lg" 
                                           value="<?php echo $email_value; ?>"
                                           placeholder="Introdu adresa ta de email"
                                           required
                                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                    <label class="form-label">Email</label>
                </div>

                <div class="form-outline form-white mb-4">
                                    <input type="password" 
                                           name="password" 
                                           class="form-control form-control-lg"
                                           placeholder="Introdu parola ta"
                                           required
                                           autocomplete="current-password"
                                           minlength="6">
                    <label class="form-label">Parola</label>
                </div>

                                <button class="btn btn-outline-light btn-lg px-5" 
                                        type="submit" 
                                        name="submit">
                                    Autentificare
                                </button>
            </form>

                            <div class="mt-4">
                                <p class="small mb-5 pb-lg-2">
                                    <a class="text-white-50" href="reset-password.php">Ai uitat parola?</a>
                                </p>
              </div>

            <div>
                                <p class="mb-0">
                                    Nu ai cont? 
                                    <a href="register.php" class="text-white-50 fw-bold">Înregistrează-te!</a>
              </p>
                            </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>
