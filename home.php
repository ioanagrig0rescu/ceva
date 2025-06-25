<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'sesion-check/check.php';
include 'plugins/bootstrap.html';
//include 'database/db.php';

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Home - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f3f3;
            color: #616f80;
            padding-top: 60px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .card {
            border: none;
            margin-bottom: 24px;
            -webkit-box-shadow: 0 0 13px 0 rgba(236,236,241,.44);
            box-shadow: 0 0 13px 0 rgba(236,236,241,.44);
        }

        .avatar-xs {
            height: 2.3rem;
            width: 2.3rem;
        }

        /* Navbar Styles */
        .navbar {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(
                to right,
                rgba(33, 37, 41, 0.97),
                rgba(33, 37, 41, 0.97)
            );
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: rgba(255, 255, 255, 1) !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(33, 37, 41, 0.97);
                border-radius: 10px;
                padding: 1rem;
                margin-top: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .navbar-nav .nav-link {
                padding: 0.7rem 1rem;
                border-radius: 5px;
            }

            .navbar-nav .nav-link:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }
        }

        /* Footer Styles */
        footer {
            background: #212529;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .content-wrapper {
            flex: 1;
            padding: 20px 0;
        }
    </style>
</head>
<body>

<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <!-- Logo/Brand -->
        <a class="navbar-brand" href="/home.php">
            Budget Master
        </a>

        <!-- Buton Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Meniu Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/venituri.php">Venituri</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/calendar.php">Calendar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/goals.php">Obiective</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/progres.php">Progres</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/account/profile.php">Profil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logout.php">Deconectare</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Area -->
<div class="content-wrapper">
    <div class="container">
        <!-- Aici va fi adăugat conținutul principal -->
    </div>
</div>

<!-- Footer -->
<?php include 'footer/footer.html'; ?>

<!-- Script pentru navbar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Închide meniul hamburger când se face click pe un link
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const menuToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (navbarCollapse.classList.contains('show')) {
                menuToggle.click();
            }
        });
    });
});
</script>

</body>
</html>