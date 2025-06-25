<?php
// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /account/login.php');
    exit;
}
?>

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
                <li class="nav-item position-relative">
                    <a class="nav-link" href="/goals.php">
                        Goals
                        <span id="warnings-badge" class="position-absolute badge rounded-pill bg-danger">
                            0
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/progres.php">Progres</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/account/profile.php">Account</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Navbar Styles */
.navbar {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(
        to right,
        rgba(106, 17, 203, 0.05),
        rgba(37, 117, 252, 0.05)
    );
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

/* Navbar când se face scroll */
.navbar.scrolled {
    background: linear-gradient(
        to right,
        rgba(106, 17, 203, 0.95),
        rgba(37, 117, 252, 0.95)
    );
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Brand/Logo */
.navbar-brand {
    font-weight: 600;
    font-size: 1.5rem;
    color: #6a11cb;
}

.scrolled .navbar-brand {
    color: white;
}

/* Link-uri navbar */
.navbar-nav .nav-link {
    color: #6a11cb;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: color 0.3s ease;
}

.scrolled .nav-link {
    color: white;
}

.navbar-nav .nav-link:hover {
    color: #2575fc;
}

.scrolled .nav-link:hover {
    color: rgba(255, 255, 255, 0.8);
}

/* Badge Styles */
#warnings-badge {
    top: 0;
    right: -5px;
    font-size: 0.7rem;
    display: none;
}

/* Buton hamburger */
.navbar-toggler {
    border: none;
    padding: 0.5rem;
}

.navbar-toggler:focus {
    box-shadow: none;
    outline: none;
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(106, 17, 203, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

.scrolled .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Media Queries pentru Responsive */
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .scrolled .navbar-collapse {
        background: rgba(106, 17, 203, 0.95);
    }

    .navbar-nav .nav-link {
        color: #6a11cb;
        padding: 0.7rem 1rem;
        border-radius: 5px;
    }

    .scrolled .navbar-nav .nav-link {
        color: white;
    }

    .navbar-nav .nav-link:hover {
        background-color: rgba(106, 17, 203, 0.1);
    }

    .scrolled .navbar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
}
</style>

<!-- Include navbar.js și goal-warnings.js -->
<script src="/navbar/navbar.js"></script>
<script src="/js/goal-warnings.js"></script> 