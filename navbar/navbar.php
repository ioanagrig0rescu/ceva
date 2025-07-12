<!DOCTYPE html>
<style>
/* Navbar Styles */
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 1rem 0;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.navbar-container {
    max-width: 100%;
    margin: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
    width: 100%;
}

.navbar-brand {
    font-size: 1.8rem;
    font-weight: 700;
    color: white;
    text-decoration: none;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    margin-right: 2rem;
}

.navbar-brand:hover {
    color: #f8f9fa;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.navbar-nav {
    display: flex;
    flex-direction: row;
    list-style: none;
    margin: 0;
    margin-left: auto;
    padding: 0;
    gap: 1.5rem;
}

.nav-item {
    position: relative;
}

.nav-link {
    color: white;
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: all 0.6s ease;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.navbar-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.navbar-toggle:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.hamburger {
    display: flex;
    flex-direction: column;
    width: 25px;
    height: 20px;
    justify-content: space-between;
}

.hamburger span {
    display: block;
    height: 3px;
    width: 100%;
    background-color: white;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.navbar-toggle.active .hamburger span:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.navbar-toggle.active .hamburger span:nth-child(2) {
    opacity: 0;
}

.navbar-toggle.active .hamburger span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .navbar-container {
        padding: 0 1rem;
    }
    
    .navbar-brand {
        font-size: 1.5rem;
    }
    
    .navbar-toggle {
        display: block;
    }
    
    .navbar-nav {
        position: fixed;
        top: 70px;
        left: 0;
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        padding: 1rem;
        gap: 0.5rem;
        transform: translateY(-100vh);
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s ease;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
        white-space: nowrap;
        justify-content: space-evenly;
        align-items: center;
    }
    
    .navbar-nav.active {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
    }
    
    .nav-item {
        flex-shrink: 0;
        text-align: center;
        display: inline-block !important;
    }
    
    .nav-link {
        display: block;
        padding: 0.75rem 1rem;
        border-radius: 25px;
        border: none;
        margin: 0;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
}

/* Body padding pentru navbar fixed */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px !important;
    background: #f3f3f3;
    color: #616f80;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family: 'Poppins', sans-serif;
}
</style>

<nav class="navbar">
    <div class="navbar-container">
        <!-- Brand/Logo -->
        <a href="home.php" class="navbar-brand">BUDGETMASTER</a>
        
        <!-- Navigation Links -->
        <ul class="navbar-nav" id="navbarNav">
            <li class="nav-item">
                <a href="calendar.php" class="nav-link">CALENDAR</a>
            </li>
            <li class="nav-item">
                <a href="goals.php" class="nav-link">OBIECTIVE</a>
            </li>
            <li class="nav-item">
                <a href="venituri.php" class="nav-link">VENITURI</a>
            </li>
            <li class="nav-item">
                <a href="progres.php" class="nav-link">PROGRES</a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">PROFIL</a>
            </li>
        </ul>
        
        <!-- Hamburger Menu Button -->
        <button class="navbar-toggle" id="navbarToggle">
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarNav = document.getElementById('navbarNav');
    
    navbarToggle.addEventListener('click', function() {
        navbarToggle.classList.toggle('active');
        navbarNav.classList.toggle('active');
    });
    
    // Închide meniul când se face click pe un link pe mobile
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navbarToggle.classList.remove('active');
                navbarNav.classList.remove('active');
            }
        });
    });
    
    // Închide meniul când se redimensionează fereastra
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            navbarToggle.classList.remove('active');
            navbarNav.classList.remove('active');
        }
    });
    
    // Închide meniul când se face click în afara lui
    document.addEventListener('click', function(e) {
        if (!navbarToggle.contains(e.target) && !navbarNav.contains(e.target)) {
            navbarToggle.classList.remove('active');
            navbarNav.classList.remove('active');
        }
    });
});
</script> 