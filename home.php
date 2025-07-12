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
            min-height: calc(100vh - 160px);
        }
        
        .home-content {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .welcome-title {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 3rem;
        }
    </style>
</head>
<body>

<!-- Include Navbar -->
<?php include 'navbar/navbar.php'; ?>

<!-- Main Content Area -->
<div class="content-wrapper">
    <div class="container">
        <div class="home-content">
            <h1 class="welcome-title">Bun venit la Budget Master!</h1>
            <p class="welcome-subtitle">Gestionează-ți cheltuielile și obiectivele financiare cu ușurință</p>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fa fa-calendar fa-3x mb-3" style="color: #667eea;"></i>
                            <h5>Calendar</h5>
                            <p class="card-text">Vizualizează și gestionează cheltuielile pe calendar</p>
                            <a href="/calendar.php" class="btn btn-primary">Accesează</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fa fa-bullseye fa-3x mb-3" style="color: #764ba2;"></i>
                            <h5>Obiective</h5>
                            <p class="card-text">Setează și urmărește obiectivele financiare</p>
                            <a href="/goals.php" class="btn btn-primary">Accesează</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fa fa-user fa-3x mb-3" style="color: #667eea;"></i>
                            <h5>Profil</h5>
                            <p class="card-text">Gestionează setările contului tău</p>
                            <a href="/profile.php" class="btn btn-primary">Accesează</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include 'footer/footer.html'; ?>

</body>
</html>