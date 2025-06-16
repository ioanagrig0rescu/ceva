<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../plugins/bootstrap.html';
include '../database/db.php';
include 'UserInfo.php';

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = mysqli_prepare($conn, $sql); 
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Verifică parola criptată
        if (password_verify($password, $user['password'])) {
            if ((int)$user['retire'] === 1) {
                echo "<script>alert('Contul este dezactivat. Va rugam contactati departamentul IT.');</script>";
            } else {
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['register_date'] = $user['register_date'];
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];

                // Cookie-uri
                $cookie_options = [
                    'expires' => time() + 86400, // 1 zi
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ];
                setcookie('username', $user['username'], $cookie_options);
                setcookie('email', $user['email'], $cookie_options);
                setcookie('role', $user['role'], $cookie_options);
                setcookie('register_date', $user['register_date'], $cookie_options);
                setcookie('loggedin', 'true', $cookie_options);

                // Logare acces
                $userInfo = new UserInfo();
                $ip = $userInfo->get_ip();
                $os = $userInfo->get_os();
                $browser = $userInfo->get_browser();
                $device = $userInfo->get_device();
                $currentDate = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("INSERT INTO login_logs (username, ip, os, browser, device, login_date) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $user['username'], $ip, $os, $browser, $device, $currentDate);
                    $stmt->execute();

                    $oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
                    $deleteStmt = $conn->prepare("DELETE FROM login_logs WHERE login_date < ?");
                    $deleteStmt->bind_param("s", $oneYearAgo);
                    $deleteStmt->execute();

                    $stmt->close();
                    $deleteStmt->close();
                }

                // Redirect
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/admin.php");
                } else {
                    header("Location: ../home.php");
                }
                exit();
            }
        } else {
            echo "<script>alert('Email sau Parola incorecta!');</script>";
        }
    } else {
        echo "<script>alert('Email sau Parola incorecta!');</script>";
    }

    if ($stmt) mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Auth - Budget Master</title>
    <script type="text/javascript">
        function preventBack(){window.history.forward()};
        setTimeout("preventBack()",0);
            window.onunload=function(){null;}
    </script>
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
              <h2 class="fw-bold mb-2 text-uppercase">Login</h2>
              <p class="text-white-50 mb-5">Introdu mai jos datele tale de conectare!</p>

              <form method="POST" action="">
                <div class="form-outline form-white mb-4">
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Introdu aici adresa de email..." required />
                    <label class="form-label">Email</label>
                </div>
                <div class="form-outline form-white mb-4">
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Introdu parola..." required />
                    <label class="form-label">Parola</label>
                </div>
                <button class="btn btn-outline-light btn-lg px-5" type="submit" name="submit">Login</button>
            </form>
            <br><hr>
              <p class="small mb-5 pb-lg-2"><a class="text-white-50" href="#!">Ti-ai uitat parola?</a></p>
              <div class="d-flex justify-content-center text-center mt-4 pt-1">
                <a href="#!" class="text-white"><i class="fab fa-facebook-f fa-lg"></i></a>
                <a href="#!" class="text-white"><i class="fab fa-twitter fa-lg mx-4 px-2"></i></a>
                <a href="#!" class="text-white"><i class="fab fa-google fa-lg"></i></a>
              </div>
            </div>
            <div>
              <p class="mb-0">Nu ai cont? <a href="register.php" class="text-white-50 fw-bold">Inregistreaza-te!</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>
