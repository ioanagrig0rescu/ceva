<?php
global $conn;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$projectRoot = realpath(__DIR__ . '/../');

//include $projectRoot . '/plugins/include.php';
include __DIR__ . '/../database/db.php';
$currentPage = basename($_SERVER['PHP_SELF']);

$username = $_SESSION['username'];
$query = "SELECT role FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $role = $row['role'];
}

// Query to get the user's profile photo
$query = "SELECT profile_photo FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile_photo = null;
if ($result) {
    $result_user = mysqli_fetch_assoc($result);
    $profile_photo = $result_user['profile_photo'];
    if (!empty($profile_photo) && !file_exists($profile_photo)) {
        $profile_photo = null; // Reset if file does not exist
    }
} else {
    echo "Error fetching data from users table: " . mysqli_error($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'bootstrap.html';?>
</head>
<style>
    @import url('https://fonts.googleapis.com/css?family=Open+Sans:400,700,800');
    @import url('https://fonts.googleapis.com/css?family=Lobster');

    html {
     font-size: smaller;
    }

    body,
    .header-area {
        margin: 0;
        padding: 0;
        padding-top: 60px;
        margin-left: 10px;
        margin-right: 10px;
        
    }

    h1 {
        margin-bottom: 0.5em;
        font-size: 3.6rem;
    }

    p {
        margin-bottom: 0.5em;
        font-size: 1.6rem;
        line-height: 1.6;
    }

    .button {
        display: inline-block;
        margin-top: 20px;
        padding: 8px 25px;
        border-radius: 4px;
    }

    .button-primary {
        position: relative;
        background-color: #0062ff;
        color: #fff;
        font-size: 1.8rem;
        font-weight: 700;
        transition: color 0.3s ease-in;
        z-index: 1;
    }

    .button-primary:hover {
        color: black;
        text-decoration: none;
    }

    .button-primary::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
        background-color: #65abff;
        border-radius: 4px;
        opacity: 0;
        -webkit-transform: scaleX(0.8);
        -ms-transform: scaleX(0.8);
        transform: scaleX(0.8);
        transition: all 0.3s ease-in;
        z-index: -1;
    }

    .button-primary:hover::after {
        opacity: 1;
        -webkit-transform: scaleX(1);
        -ms-transform: scaleX(1);
        transform: scaleX(1);
    }

    .overlay::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
        background-color: rgba(0, 0, 0, .3);
    }

    .header-area {
        position: relative;
        height: 110vh;
        background: #fff;
        background-attachment: fixed;
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
    }

    .banner {
        display: flex;
        align-items: center;
        position: relative;
        height: 100%;
        color: #fff;
        text-align: center;
        z-index: 1;
    }

    .banner h1 {
        font-weight: 800;
    }

    .banner p {
        font-weight: 700;
    }

    .navbar {
        position: fixed;
        left: 0;
        top: 0;
        padding: 0;
        width: 100%;
        height: 80px;
        transition: background 0.9s ease-in;
        z-index: 99999;
        background-color: rgba(0, 0, 0, .9);
    }

    .navbar .navbar-brand {
        font-size: 1.2rem;
    }

    .navbar .navbar-toggler {
        position: relative;
        height: 50px;
        width: 50px;
        border: none;
        cursor: pointer;
        outline: none;
    }

    .navbar .navbar-toggler .menu-icon-bar {
        position: absolute;
        left: 15px;
        right: 15px;
        height: 2px;
        background-color: #fff;
        opacity: 0;
        -webkit-transform: translateY(-1px);
        -ms-transform: translateY(-1px);
        transform: translateY(-1px);
        transition: all 0.3s ease-in;
    }

    .navbar .navbar-toggler .menu-icon-bar:first-child {
        opacity: 1;
        -webkit-transform: translateY(-1px) rotate(45deg);
        -ms-sform: translateY(-1px) rotate(45deg);
        transform: translateY(-1px) rotate(45deg);
    }

    .navbar .navbar-toggler .menu-icon-bar:last-child {
        opacity: 1;
        -webkit-transform: translateY(-1px) rotate(135deg);
        -ms-sform: translateY(-1px) rotate(135deg);
        transform: translateY(-1px) rotate(135deg);
    }

    .navbar .navbar-toggler.collapsed .menu-icon-bar {
        opacity: 1;
    }

    .navbar .navbar-toggler.collapsed .menu-icon-bar:first-child {
        -webkit-transform: translateY(-7px) rotate(0);
        -ms-sform: translateY(-7px) rotate(0);
        transform: translateY(-7px) rotate(0);
    }

    .navbar .navbar-toggler.collapsed .menu-icon-bar:last-child {
        -webkit-transform: translateY(5px) rotate(0);
        -ms-sform: translateY(5px) rotate(0);
        transform: translateY(5px) rotate(0);
    }

    .navbar-dark .navbar-nav .nav-link {
        position: relative;
        color: #fff;    
        padding: 5px 5px; /* Reduci spațiul dintre elementele din navbar */
        font-size: 1.2rem; /* Opțional, micșorează fontul link-urilor */
    }

    .navbar-dark .navbar-nav .nav-link:focus, .navbar-dark .navbar-nav .nav-link:hover {
        color: #fff;
    }

    .navbar .dropdown-menu {
        padding: 0;
        background-color: rgba(0, 0, 0, .9);
        margin-top: 0;
    }

    .navbar .dropdown-menu .dropdown-item {
        position: relative;
        padding: 10px 15px;
        color: #fff;
        font-size: 1.2rem;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
        transition: color 1.4s ease-in;
    }

    .navbar .dropdown-menu .dropdown-item:last-child {
        border-bottom: none;
    }

    .navbar .dropdown-menu .dropdown-item:hover {
        background: transparent;
        color: #fdc013;
    }

    .navbar .dropdown-menu .dropdown-item::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        top: 0;
        width: 7px;
        background-color: #F99F1B;
        opacity: 0;
        transition: opacity 0.4s ease-in;
    }

    .navbar .dropdown-menu .dropdown-item:hover::before {
        opacity: 1;
    }

    .navbar.fixed-top {
        position: fixed;
        -webkit-animation: navbar-animation 0.6s;
        animation: navbar-animation 0.6s;
        background-color: rgba(0, 0, 0, .9);
    }

    .navbar.fixed-top.navbar-dark .navbar-nav .nav-link.active {
        color: #fdc013;
    }

    .navbar.fixed-top.navbar-dark .navbar-nav .nav-link::after {
        background-color: #fdc013;
    }

    .content {
        padding: 120px 0;
    }

    @media screen and (max-width: 768px) {
        .navbar-brand {
            margin-left: 20px;
            margin-top: 0;
        }
        .navbar-nav {
            padding: 0 10px;
            background-color: rgba(0, 0, 0, .9);
        }
        .navbar.fixed-top .navbar-nav {
            background: transparent;
        }
    }

    @media screen and (max-width: 320px) {
        .navbar {
            padding: 10px;
        }
        .navbar .navbar-brand {
            font-size: 2rem;
        }
        .navbar .navbar-toggler {
            height: 40px;
            width: 40px;
        }
        .navbar-dark .navbar-nav .nav-link {
            font-size: 1.4rem;
        }
    }

    @media screen and (min-width: 767px) {
        .banner {
            padding: 0 150px;
        }
        .banner h1 {
            font-size: 5rem;
        }
        .banner p {
            font-size: 2rem;
        }
        .navbar-dark .navbar-nav .nav-link {
            padding: 23px 15px;
        }
        .navbar-dark .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 15px;
            left: 30%;
            right: 30%;
            height: 1px;
            background-color: #fff;
            -webkit-transform: scaleX(0);
            -ms-transform: scaleX(0);
            transform: scaleX(0);
            transition: transform 0.1s ease-in;
        }
        .navbar-dark .navbar-nav .nav-link:hover::after {
            -webkit-transform: scaleX(1);
            -ms-transform: scaleX(1);
            transform: scaleX(1);
        }
        .dropdown-menu {
            min-width: 200px;
            -webkit-animation: dropdown-animation 0.3s;
            animation: dropdown-animation 0.3s;
            -webkit-transform-origin: top;
            -ms-transform-origin: top;
            transform-origin: top;
        }
    }

    @-webkit-keyframes navbar-animation {
        0% {
            opacity: 0;
            -webkit-transform: translateY(-100%);
            -ms-transform: translateY(-100%);
            transform: translateY(-100%);
        }
        100% {
            opacity: 1;
            -webkit-transform: translateY(0);
            -ms-transform: translateY(0);
            transform: translateY(0);
        }
    }

    @keyframes navbar-animation {
        0% {
            opacity: 0;
            -webkit-transform: translateY(-100%);
            -ms-transform: translateY(-100%);
            transform: translateY(-100%);
        }
        100% {
            opacity: 1;
            -webkit-transform: translateY(0);
            -ms-transform: translateY(0);
            transform: translateY(0);
        }
    }

    @-webkit-keyframes dropdown-animation {
        0% {
            -webkit-transform: scaleY(0);
            -ms-transform: scaleY(0);
            transform: scaleY(0);
        }
        75% {
            -webkit-transform: scaleY(1.1);
            -ms-transform: scaleY(1.1);
            transform: scaleY(1.1);
        }
        100% {
            -webkit-transform: scaleY(1);
            -ms-transform: scaleY(1);
            transform: scaleY(1);
        }
    }

    @keyframes dropdown-animation {
        0% {
            -webkit-transform: scaleY(0);
            -ms-transform: scaleY(0);
            transform: scaleY(0);
        }
        75% {
            -webkit-transform: scaleY(1.1);
            -ms-transform: scaleY(1.1);
            transform: scaleY(1.1);
        }
        100% {
            -webkit-transform: scaleY(1);
            -ms-transform: scaleY(1);
            transform: scaleY(1);
        }
    }

    .avatar-sm {
        height: 2.4rem;
        width: 2.4rem;
    }

</style>
<body>
<?php
if ($role == 'admin') { ?>

    <?php }
elseif ($role == 'user') { ?>
    <header class="header-area">
        <nav class="navbar navbar-expand-md navbar-dark">
            <div class="container">
            <a href="home.php" class="navbar-brand">
                <img src="https://i.ibb.co/tT1b01zw/buget-logo.png" style="height: 25px;">
            </a>
                <button type="button" class="navbar-toggler collapsed" data-toggle="collapse" data-target="#main-nav">
                    <span class="menu-icon-bar"></span>
                    <span class="menu-icon-bar"></span>
                    <span class="menu-icon-bar"></span>
                </button>

                <div id="main-nav" class="collapse navbar-collapse justify-content-end">
                    <ul class="navbar-nav ml-auto">
                        <li class="dropdown">
                            <a href="#" class="nav-item nav-link" data-toggle="dropdown"><i class="fa-solid fa-users"></i></a>
                            <div class="dropdown-menu">
                                <a href="../user/main.php?status=open" class="dropdown-item"><i class="fa-solid fa-message"></i> - Contacteaza-ne</a>
                                <a href="../user/main.php?status=closed" class="dropdown-item"><i class="fa-solid fa-info"></i> - Centru de ajutor</a>
                                <a href="../user/register-incident-moveos.php" class="dropdown-item"><i class="fa-solid fa-dollar-sign"></i> - Preturi</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="nav-item nav-link me-3" data-toggle="dropdown"><i class="fa-solid fa-plus"></i></a>
                            <div class="dropdown-menu">
                                <a href="new.php" class="dropdown-item"><i class="fa-solid fa-list-check"></i> Adauga inregistrari</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="nav-item nav-link me-3" data-toggle="dropdown"><i class="fa-solid fa-gears"></i></a>
                            <div class="dropdown-menu">
                                <a href="about.php" class="dropdown-item"><i class="fa-solid fa-gears"></i> - Setari</a>
                                <a href="profile.php" class="dropdown-item"><i class="fa-solid fa-user"></i> Profilul meu</a>
                            </div>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="nav-item nav-link me-1" data-toggle="dropdown"><?php
                                // Display profile photo or default photo
                                $photoPath = $profile_photo ? $profile_photo : 'https://www.pngitem.com/pimgs/m/130-1300253_female-user-icon-png-download-user-image-color.png';
                                echo '<img src="' . $photoPath . '" alt="Profile Photo" class="avatar-sm rounded-circle me-2" />';
                                ?></a>
                            <div class="dropdown-menu">
                                <a href="../user/register-incident-moveos.php" class="dropdown-item"><i class="fa-solid fa-gears"></i> - Setari</a>
                                <a href="profile.php" class="dropdown-item"><i class="fa-solid fa-user"></i> Profilul meu</a>
                                <a href="../user/profile-info.php" class="dropdown-item"><i class="fa-solid fa-key"></i> Schimba Parola</a>
                                <a href="logout.php" class="dropdown-item"><i class="fas fa-lock"></i> Iesi din cont</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        </body>
        <?php
    }
    ?>

<script>

    document.addEventListener('DOMContentLoaded', function () {
        const toggler = document.querySelector('.navbar-toggler');
        toggler.addEventListener('click', function () {
            toggler.classList.toggle('collapsed');
        });
    });

    jQuery(function($) {
        $(window).on('scroll', function() {
            if ($(this).scrollTop() >= 200) {
                $('.navbar').addClass('fixed-top');
            } else if ($(this).scrollTop() == 0) {
                $('.navbar').removeClass('fixed-top');
            }
        });

        function adjustNav() {
            var winWidth = $(window).width(),
                dropdown = $('.dropdown'),
                dropdownMenu = $('.dropdown-menu');

            if (winWidth >= 768) {
                dropdown.on('mouseenter', function() {
                    $(this).addClass('show')
                        .children(dropdownMenu).addClass('show');
                });

                dropdown.on('mouseleave', function() {
                    $(this).removeClass('show')
                        .children(dropdownMenu).removeClass('show');
                });
            } else {
                dropdown.off('mouseenter mouseleave');
            }
        }

        $(window).on('resize', adjustNav);

        adjustNav();
    });
</script>