<?php
session_start();
require_once '../database/db.php';
require_once '../plugins/bootstrap.html';

$error = '';
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Curățare și validare date
    $username = strtolower(trim($_POST['username']));
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validare username
    if (empty($username)) {
        $error = "Username-ul este obligatoriu!";
    } elseif (!preg_match('/^[a-z0-9]{3,20}$/', $username)) {
        $error = "Username-ul trebuie să conțină doar litere mici și cifre, între 3-20 caractere!";
    }
    // Validare email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalid!";
    }
    // Validare parolă
    elseif (strlen($password) < 6) {
        $error = "Parola trebuie să aibă minim 6 caractere!";
    }
    elseif (!preg_match("/[A-Z]/", $password)) {
        $error = "Parola trebuie să conțină cel puțin o literă mare!";
    }
    elseif (!preg_match("/[0-9]/", $password)) {
        $error = "Parola trebuie să conțină cel puțin o cifră!";
    }
    elseif ($password !== $confirm_password) {
        $error = "Parolele nu coincid!";
    }
    else {
        // Verificare username și email existente
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['username'] === $username) {
                $error = "Username-ul este deja folosit!";
            } else {
                $error = "Email-ul este deja folosit!";
            }
        } else {
            // Get random profile photo
            $photos_file = '../data/default_profile_photos.json';
            $photos_data = file_exists($photos_file) ? json_decode(file_get_contents($photos_file), true) : null;
            $profile_photo = null;
            
            if ($photos_data && !empty($photos_data['photos'])) {
                $profile_photo = $photos_data['photos'][array_rand($photos_data['photos'])];
            }

            // Inserare utilizator nou
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $register_date = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, register_date, retire, role, profile_photo) VALUES (?, ?, ?, ?, 0, 'user', ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $register_date, $profile_photo);

            if ($stmt->execute()) {
                // Track first login
                include '../operations/track_login.php';
                trackLogin($username, $conn);
                
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Eroare la crearea contului. Încearcă din nou!";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare - Budget Master</title>
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

        .error { 
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .requirements {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
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
                        <h2 class="fw-bold mb-4">Creare cont nou</h2>

                        <?php if ($error): ?>
                            <div class="error"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <!-- Username -->
                            <div class="form-outline form-white mb-4">
                                <input type="text" 
                                       name="username" 
                                       class="form-control form-control-lg"
                                       value="<?php echo htmlspecialchars($username); ?>"
                                       placeholder="Alege un username"
                                       required>
                                <div class="requirements">
                                    Username: doar litere mici și cifre (3-20 caractere)
                                </div>
                            </div>

                            <!-- Email -->
                <div class="form-outline form-white mb-4">
                                <input type="email" 
                                       name="email" 
                                       class="form-control form-control-lg"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       placeholder="Adresa ta de email"
                                       required>
                </div>

                            <!-- Parola -->
                <div class="form-outline form-white mb-4">
                                <input type="password" 
                                       name="password" 
                                       class="form-control form-control-lg"
                                       placeholder="Alege o parolă"
                                       required 
                                       autocomplete="new-password">
                                <div class="requirements">
                                    Parola: minim 6 caractere, o literă mare și o cifră
                                </div>
                </div>

                            <!-- Confirmare Parola -->
                            <div class="form-outline form-white mb-4">
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control form-control-lg"
                                       placeholder="Confirmă parola"
                                       required 
                                       autocomplete="new-password">
            </div>

                            <button class="btn btn-outline-light btn-lg px-5" type="submit">
                                Creează cont
                            </button>

                            <div class="mt-4">
                                <p class="mb-0">
                                    Ai deja cont? 
                                    <a href="login.php" class="text-white-50 fw-bold">Conectează-te</a>
              </p>
            </div>
                        </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>