<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../plugins/bootstrap.html';
include '../database/db.php';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $username = strstr($email, '@', true);
    $register_date = date('Y-m-d H:i:s');
    $retire = 0; // cont activ
    $role = 'user';

    // verifică dacă emailul există deja
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Adresa de email este deja folosită!');</script>";
    } else {
        // inserează contul nou
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertSql = "INSERT INTO users (username, email, password, register_date, retire, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $register_date, $retire, $role);

        if ($stmt->execute()) {
            echo "<script>alert('Contul a fost creat cu succes!'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('A apărut o eroare la crearea contului.');</script>";
        }
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css" integrity="sha256-3sPp8BkKUE7QyPSl6VfBByBroQbKxKG7tsusY2mhbVY=" crossorigin="anonymous" />
    <style type="text/css">
        <?php include 'style.css'; ?>
    </style>
</head>
<body>
<section class="vh-100 gradient-custom">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        <div class="card bg-dark text-white" style="border-radius: 1rem;">
          <div class="card-body p-5 text-center">

            <div class="mb-md-5 mt-md-4 pb-5">

              <h2 class="fw-bold mb-2 text-uppercase">Budget Master</h2>
              <h2 class="fw-bold mb-2">Creaza un cont gratuit!</h2>
              <p class="text-white-50 mb-5">Introdu mai jos datele tale tale pentru a te intregistra!</p>

              <form method="POST" action="">
                <div class="form-outline form-white mb-4">
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Introdu aici adresa de email..." required />
                    <label class="form-label">Email</label>
                </div>
                <div class="form-outline form-white mb-4">
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Introdu parola..." required />
                    <label class="form-label">Parola</label>
                </div>
                <button class="btn btn-outline-light btn-lg px-5" type="submit" name="submit">Creaza contul!</button>
            </form>
            <br><hr>
            </div>
            <div>
              <p class="mb-0">Ai un cont? <a href="login.php" class="text-white-50 fw-bold">Conecteaza-te!</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</body>
</html>