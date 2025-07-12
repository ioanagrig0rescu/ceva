<?php
session_start();
include 'sesion-check/check.php';
include 'database/db.php';
include 'plugins/bootstrap.html';

// Get current user details
$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get display name
$names_file = 'data/display_names.json';
$display_names = file_exists($names_file) ? json_decode(file_get_contents($names_file), true) : [];
$display_names = is_array($display_names) ? $display_names : [];
$current_display_name = isset($display_names[$_SESSION['username']]) ? 
    $display_names[$_SESSION['username']] : 
    ucwords(str_replace(['_', '.'], ' ', $_SESSION['username']));
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setări - Budget Master</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f7ff;
            padding-top: 120px;
        }

        .settings-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
            margin: 8rem auto;
            overflow: hidden;
            min-height: 600px;
        }

        .settings-sidebar {
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            padding: 2rem 0;
            height: 100%;
        }

        .nav-settings {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-settings li {
            margin-bottom: 0.5rem;
        }

        .nav-settings a {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-settings a:hover {
            background: #e9ecef;
            color: #667eea;
        }

        .nav-settings a.active {
            background: linear-gradient(to right, rgba(102,126,234,0.1), transparent);
            color: #667eea;
            border-left-color: #667eea;
        }

        .nav-settings i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }

        .settings-content {
            padding: 2rem;
            display: none;
            position: relative;
            min-height: 600px;
        }

        .settings-content.active {
            display: block;
        }

        .settings-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .settings-header h2 {
            color: #212529;
            margin: 0;
            font-size: 1.5rem;
        }

        .settings-header h2 i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 15px;
            border: 1px solid #e1e1e1;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .alert {
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }

        /* Switch pentru setări de confidențialitate */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-item-text {
            flex: 1;
        }

        .setting-item-text h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
        }

        .setting-item-text p {
            margin: 0;
            color: #718096;
            font-size: 0.9rem;
        }

        .profile-picture-section {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: #fff;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-picture-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 50%;
        }

        .profile-picture:hover .profile-picture-overlay {
            opacity: 1;
        }

        .profile-picture-overlay i {
            color: white;
            font-size: 2rem;
        }

        .profile-picture-options {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .profile-picture-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-picture-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .profile-picture-btn.remove {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%);
        }

        /* Crop Modal Styles */
        .crop-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow-y: auto; /* Add scroll to modal */
            padding: 20px;
        }

        .crop-modal-content {
            background-color: #fefefe;
            margin: 20px auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            border-radius: 10px;
            position: relative;
        }

        .crop-container {
            width: 100%;
            max-height: 70vh; /* Limit height relative to viewport */
            margin-bottom: 20px;
            overflow: hidden;
        }

        #cropImage {
            max-width: 100%;
            display: block;
        }

        .crop-buttons {
            text-align: center;
            margin-top: 15px;
            padding: 10px 0;
            background: #fff;
            position: sticky;
            bottom: 0;
        }

        /* Make sure the cropper container is visible */
        .cropper-container {
            max-height: 70vh;
            margin: 0 auto;
        }

        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }

        .crop-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .crop-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .crop-btn.cancel {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%);
        }

        /* Ensure modal is scrollable on mobile */
        @media (max-width: 768px) {
            .crop-modal-content {
                width: 95%;
                margin: 10px auto;
                padding: 15px;
            }

            .crop-container {
                max-height: 60vh;
            }

            .crop-buttons {
                padding: 10px;
            }

            .crop-btn {
                width: 100%;
                margin: 5px 0;
            }
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            position: relative;
            margin-top: 2rem;
            width: 100%;
        }

        .button-container .btn {
            min-width: 160px;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .button-container .btn-back {
            background: #888888;
            color: white;
            border: none;
        }

        .button-container .btn-back:hover {
            background: #777777;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(136,136,136,0.4);
        }
    </style>
</head>
<body>
    <?php include 'navbar/navbar.php'; ?>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="row g-0">
                <div class="col-md-3 settings-sidebar">
                    <ul class="nav-settings">
                        <li>
                            <a href="#account" class="active" data-section="account">
                                <i class="fas fa-user"></i> Cont
                            </a>
                        </li>
                        <li>
                            <a href="#privacy" data-section="privacy">
                                <i class="fas fa-shield-alt"></i> Confidențialitate
                            </a>
                        </li>
                        <li>
                            <a href="#notifications" data-section="notifications">
                                <i class="fas fa-bell"></i> Notificări
                            </a>
                        </li>
                        <li>
                            <a href="#security" data-section="security">
                                <i class="fas fa-fingerprint"></i> Securitate
                            </a>
                        </li>
                        <li>
                            <a href="#delete-account" data-section="delete-account" style="color: #dc3545;">
                                <i class="fas fa-trash-alt"></i> Șterge Cont
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-9">
                    <!-- Setări Cont -->
                    <div id="account" class="settings-content active">
                        <div class="settings-header">
                            <h2><i class="fas fa-user"></i> Setări Cont</h2>
                        </div>
                        
                        <div class="profile-picture-section">
                            <div class="profile-picture">
                                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Poză de profil">
                                <label for="profile-picture-upload" class="profile-picture-overlay">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <div class="profile-picture-options">
                                <label class="profile-picture-btn">
                                    <i class="fas fa-upload"></i>
                                    Încarcă poză
                                    <input type="file" id="profile-picture-upload" accept="image/*" style="display: none;">
                                </label>
                                <button type="button" class="profile-picture-btn" id="random-avatar-btn">
                                    <i class="fas fa-random"></i>
                                    Avatar aleator
                                </button>
                            </div>
                        </div>

                        <!-- Combined Form -->
                        <form id="accountForm" method="POST" class="mb-5" style="margin-bottom: 150px;">
                            <div class="form-group mb-4">
                                <label for="username" class="form-label">Nume Utilizator</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                                <div id="usernameError" class="invalid-feedback" style="display: none;">
                                    Acest nume de utilizator este deja folosit. Te rugăm să alegi altul.
                                </div>
                                <div id="usernameFormatError" class="invalid-feedback" style="display: none;">
                                    Numele de utilizator poate conține doar litere mici, cifre și caracterul underscore (_).
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="display_name" class="form-label">Nume Afișat</label>
                                <input type="text" class="form-control" id="display_name" name="display_name" 
                                       value="<?php echo htmlspecialchars($current_display_name); ?>" required>
                            </div>

                            <div class="button-container">
                                <button type="submit" class="btn btn-save" id="saveBtn">
                                    <i class="fas fa-save"></i> Salvează Modificările
                                </button>
                                <a href="profile.php" class="btn btn-back">
                                    <i class="fas fa-arrow-left"></i> Înapoi
                                </a>
                            </div>
                        </form>

                        <script>
                        const form = document.getElementById('accountForm');
                        const usernameInput = document.getElementById('username');
                        const displayNameInput = document.getElementById('display_name');
                        const errorDiv = document.getElementById('usernameError');
                        const formatErrorDiv = document.getElementById('usernameFormatError');
                        const saveBtn = document.getElementById('saveBtn');
                        const currentUsername = '<?php echo htmlspecialchars($_SESSION['username']); ?>';
                        
                        // Verifică formatul username-ului
                        function isValidUsername(username) {
                            return /^[a-z0-9_]+$/.test(username);
                        }
                        
                        // Verifică username-ul în timp real
                        usernameInput.addEventListener('input', function() {
                            const username = this.value.trim();
                            
                            if (username === currentUsername) {
                                this.classList.remove('is-invalid');
                                errorDiv.style.display = 'none';
                                formatErrorDiv.style.display = 'none';
                                return;
                            }

                            // Verifică formatul
                            if (username.length > 0 && !isValidUsername(username)) {
                                this.classList.add('is-invalid');
                                formatErrorDiv.style.display = 'block';
                                errorDiv.style.display = 'none';
                                saveBtn.disabled = true;
                                return;
                            }

                            if (username.length > 0) {
                                fetch('operations/check_username.php?username=' + encodeURIComponent(username))
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.exists) {
                                            this.classList.add('is-invalid');
                                            errorDiv.style.display = 'block';
                                            formatErrorDiv.style.display = 'none';
                                            saveBtn.disabled = true;
                                        } else {
                                            this.classList.remove('is-invalid');
                                            errorDiv.style.display = 'none';
                                            formatErrorDiv.style.display = 'none';
                                            saveBtn.disabled = false;
                                        }
                                    });
                            } else {
                                this.classList.remove('is-invalid');
                                errorDiv.style.display = 'none';
                                formatErrorDiv.style.display = 'none';
                            }
                        });

                        // Gestionează trimiterea formularului
                        form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            const username = usernameInput.value.trim();
                            const displayName = displayNameInput.value.trim();

                            try {
                            // Actualizează username-ul dacă s-a modificat
                            if (username !== currentUsername) {
                                    await fetch('operations/update_username.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'username=' + encodeURIComponent(username)
                                });
                            }

                            // Actualizează numele afișat
                                await fetch('operations/update_display_name.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'display_name=' + encodeURIComponent(displayName)
                            });

                            // Redirecționează către pagina de profil
                            window.location.href = 'profile.php';
                            } catch (error) {
                                console.error('Eroare la salvare:', error);
                                alert('A apărut o eroare la salvarea modificărilor. Te rugăm să încerci din nou.');
                            }
                        });
                        </script>
                    </div>

                    <!-- Setări Confidențialitate -->
                    <div id="privacy" class="settings-content">
                        <div class="settings-header">
                            <h2><i class="fas fa-shield-alt"></i> Setări Confidențialitate</h2>
                        </div>
                        <form id="privacyForm" method="POST" class="mb-4" style="margin-bottom: 100px;">
                            <div class="setting-item">
                                <div class="setting-item-text">
                                    <h4>Profil Public</h4>
                                    <p>Permite altor utilizatori să îți vadă profilul și statisticile</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="publicProfile" name="publicProfile">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-item">
                                <div class="setting-item-text">
                                    <h4>Statistici Publice</h4>
                                    <p>Afișează statisticile tale în clasamentele publice</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="publicStats" name="publicStats">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="button-container">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save"></i> Salvează Modificările
                                </button>
                                <a href="profile.php" class="btn btn-back">
                                    <i class="fas fa-arrow-left"></i> Înapoi
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Setări Notificări -->
                    <div id="notifications" class="settings-content">
                        <div class="settings-header">
                            <h2><i class="fas fa-bell"></i> Setări Notificări</h2>
                        </div>
                        <form id="notificationsForm" method="POST" class="mb-4" style="margin-bottom: 100px;">
                            <div class="setting-item">
                                <div class="setting-item-text">
                                    <h4>Notificări Email</h4>
                                    <p>Primește notificări importante pe email</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="emailNotifications" name="emailNotifications">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="setting-item">
                                <div class="setting-item-text">
                                    <h4>Notificări Browser</h4>
                                    <p>Primește notificări în browser când ești online</p>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="browserNotifications" name="browserNotifications">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="button-container">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save"></i> Salvează Modificările
                                </button>
                                <a href="profile.php" class="btn btn-back">
                                    <i class="fas fa-arrow-left"></i> Înapoi
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Setări Securitate -->
                    <div id="security" class="settings-content">
                        <div class="settings-header">
                            <h2><i class="fas fa-fingerprint"></i> Setări Securitate</h2>
                        </div>
                        
                        <div class="security-section">
                            <!-- Password Change Section -->
                            <div class="setting-item mb-3">
                                <div class="setting-item-text">
                                    <h4>Schimbă Parola</h4>
                                    <p>Actualizează parola contului tău</p>
                                </div>
                                <button type="button" class="btn btn-primary" id="showPasswordForm">
                                    <i class="fas fa-key"></i> Schimbă Parola
                                </button>
                            </div>

                        <!-- Password Change Form (Hidden by default) -->
                            <div id="passwordChangeForm" style="display: none; margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                                <form id="passwordForm" action="operations/update_password.php" method="POST">
                                    <div class="form-group mb-3">
                                    <label for="current_password_security" class="form-label">Parolă Actuală</label>
                                    <input type="password" class="form-control" id="current_password_security" 
                                           name="current_password" required>
                                </div>
                                    <div class="form-group mb-3">
                                    <label for="new_password_security" class="form-label">Parolă Nouă</label>
                                    <input type="password" class="form-control" id="new_password_security" 
                                           name="new_password" required>
                                </div>
                                    <div class="form-group mb-4">
                                    <label for="confirm_password_security" class="form-label">Confirmă Parola Nouă</label>
                                    <input type="password" class="form-control" id="confirm_password_security" 
                                           name="confirm_password" required>
                                </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" id="cancelPasswordChange">
                                            <i class="fas fa-times"></i> Anulează
                                    </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvează Parola
                                    </button>
                                </div>
                            </form>
                            </div>
                        </div>
                    </div>

                    <!-- Șterge Cont -->
                    <div id="delete-account" class="settings-content">
                        <div class="settings-header">
                            <h2><i class="fas fa-trash-alt" style="color: #dc3545;"></i> Șterge Cont</h2>
                        </div>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atenție!</strong> Această acțiune este ireversibilă și va șterge permanent contul tău și toate datele asociate.
                        </div>
                        <div class="text-center" style="margin-top: 2rem;">
                            <button type="button" class="btn btn-danger btn-lg" id="deleteAccountBtn" style="border-radius: 25px; padding: 1rem 3rem;">
                                <i class="fas fa-trash-alt"></i> Șterge Cont Permanent
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crop Modal -->
    <div id="cropModal" class="crop-modal">
        <div class="crop-modal-content">
            <div class="crop-container">
                <img id="cropImage" src="">
            </div>
            <div class="crop-buttons">
                <button type="button" class="crop-btn" id="cropButton">
                    <i class="fas fa-crop-alt"></i> Decupează și Salvează
                </button>
                <button type="button" class="crop-btn cancel" id="cancelCrop">
                    <i class="fas fa-times"></i> Anulează
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer/footer.html'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        // Function to activate a section
        function activateSection(sectionId) {
            // If no section provided, default to account
            if (!sectionId) sectionId = 'account';
            
            // Remove active class from all links and content
            document.querySelectorAll('.nav-settings a').forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.settings-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to corresponding link and content
            const link = document.querySelector(`.nav-settings a[data-section="${sectionId}"]`);
            if (link) link.classList.add('active');
            
            const content = document.getElementById(sectionId);
            if (content) content.classList.add('active');
        }

        // Handle navigation clicks
        document.querySelectorAll('.nav-settings a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                window.location.hash = section;
                activateSection(section);
            });
        });

        // Handle initial load and hash changes
        function handleHashChange() {
            const hash = window.location.hash.substring(1);
            activateSection(hash);
        }

        // Set up event listeners
        window.addEventListener('load', handleHashChange);
        window.addEventListener('hashchange', handleHashChange);

        let cropper = null;
        const cropModal = document.getElementById('cropModal');
        const cropImage = document.getElementById('cropImage');
        const cropButton = document.getElementById('cropButton');
        const cancelCrop = document.getElementById('cancelCrop');

        // Profile picture handling
        document.getElementById('profile-picture-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show crop modal
                cropModal.style.display = 'block';
                
                // Create a URL for the file
                const url = URL.createObjectURL(file);
                cropImage.src = url;
                
                // Initialize cropper
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    zoomable: true,
                    scalable: false
                });
            }
        });

        // Handle crop button click
        cropButton.addEventListener('click', function() {
            if (cropper) {
                // Get cropped canvas
                const canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 400
                });

                // Convert canvas to blob
                canvas.toBlob(function(blob) {
                    const formData = new FormData();
                    formData.append('profile_picture', blob, 'cropped_profile.png');

                    // Upload cropped image
                    fetch('operations/update_profile_picture.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.profile-picture img').src = data.picture_url;
                            cropModal.style.display = 'none';
                            cropper.destroy();
                            cropper = null;
                        }
                    });
                }, 'image/png');
            }
        });

        // Handle cancel button click
        cancelCrop.addEventListener('click', function() {
            cropModal.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('profile-picture-upload').value = '';
        });

        // Close modal when clicking outside
        cropModal.addEventListener('click', function(e) {
            if (e.target === cropModal) {
                cancelCrop.click();
            }
        });

        document.getElementById('random-avatar-btn').addEventListener('click', function() {
            fetch('operations/get_random_avatar.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.profile-picture img').src = data.picture_url;
                }
            });
        });

        // Password form toggle
        document.getElementById('showPasswordForm').addEventListener('click', function() {
            const form = document.getElementById('passwordChangeForm');
            const isHidden = form.style.display === 'none';
            form.style.display = isHidden ? 'block' : 'none';
            this.innerHTML = isHidden ? 
                '<i class="fas fa-times"></i> Anulează' : 
                '<i class="fas fa-key"></i> Schimbă Parola';
            this.classList.toggle('btn-danger', isHidden);
            this.classList.toggle('btn-primary', !isHidden);
        });

        // Cancel button handler
        document.getElementById('cancelPasswordChange').addEventListener('click', function() {
            const form = document.getElementById('passwordChangeForm');
            const showButton = document.getElementById('showPasswordForm');
            form.style.display = 'none';
            showButton.innerHTML = '<i class="fas fa-key"></i> Schimbă Parola';
            showButton.classList.remove('btn-danger');
            showButton.classList.add('btn-primary');
                document.getElementById('passwordForm').reset();
        });

        // Password form validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password_security').value;
            const confirmPass = document.getElementById('confirm_password_security').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('Parolele noi nu se potrivesc!');
            }
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.settings-header').insertAdjacentElement('afterend', alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Handle delete account button
        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            const confirmText = 'Te rog scrie "ȘTERGE CONT" pentru a confirma ștergerea permanentă a contului tău și a tuturor datelor asociate:';
            const userInput = prompt(confirmText);
            
            if (userInput === 'ȘTERGE CONT') {
                // Disable the button and show loading state
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Se șterge contul...';
                
                // Disable all interactive elements in the settings
                document.querySelectorAll('button, input, a').forEach(el => {
                    if (el !== this) el.disabled = true;
                });

                // Show processing message
                showAlert('Se procesează ștergerea contului...', 'info');

                // Send request to delete account
                fetch('operations/delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Eroare de rețea sau server');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showAlert('Contul tău a fost șters cu succes. Vei fi redirecționat...', 'success');
                        setTimeout(() => {
                        window.location.href = 'account/login.php';
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Eroare la ștergerea contului');
                    }
                })
                .catch(error => {
                    console.error('Eroare:', error);
                    
                    // Re-enable the button and restore original state
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash-alt"></i> Șterge Cont Permanent';
                    
                    // Re-enable all interactive elements
                    document.querySelectorAll('button, input, a').forEach(el => {
                        el.disabled = false;
                    });
                    
                    // Show error message
                    showAlert(error.message || 'A apărut o eroare la ștergerea contului. Te rugăm să încerci din nou.', 'danger');
                });
            } else if (userInput !== null) {
                showAlert('Textul introdus nu este corect. Contul nu a fost șters.', 'warning');
            }
        });

        // Funcție comună pentru redirecționare
        function redirectToProfile() {
            window.location.href = 'profile.php';
        }

        // Formular Cont
        const accountForm = document.getElementById('accountForm');
        accountForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const username = usernameInput.value.trim();
                const displayName = displayNameInput.value.trim();

                // Actualizează username-ul dacă s-a modificat
                if (username !== currentUsername) {
                    await fetch('operations/update_username.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'username=' + encodeURIComponent(username)
                    });
                }

                // Actualizează numele afișat
                await fetch('operations/update_display_name.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'display_name=' + encodeURIComponent(displayName)
                });

                redirectToProfile();
            } catch (error) {
                console.error('Eroare la salvare:', error);
                alert('A apărut o eroare la salvarea modificărilor. Te rugăm să încerci din nou.');
            }
        });

        // Formular Confidențialitate
        const privacyForm = document.getElementById('privacyForm');
        if (privacyForm) {
            privacyForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    const publicProfile = document.getElementById('publicProfile').checked;
                    const publicStats = document.getElementById('publicStats').checked;

                    await fetch('operations/update_privacy.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'publicProfile=' + publicProfile + '&publicStats=' + publicStats
                    });

                    redirectToProfile();
                } catch (error) {
                    console.error('Eroare la salvare:', error);
                    alert('A apărut o eroare la salvarea setărilor de confidențialitate. Te rugăm să încerci din nou.');
                }
            });
        }

        // Formular Notificări
        const notificationsForm = document.getElementById('notificationsForm');
        if (notificationsForm) {
            notificationsForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    const emailNotifications = document.getElementById('emailNotifications').checked;
                    const browserNotifications = document.getElementById('browserNotifications').checked;

                    await fetch('operations/update_notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'emailNotifications=' + emailNotifications + '&browserNotifications=' + browserNotifications
                    });

                    redirectToProfile();
                } catch (error) {
                    console.error('Eroare la salvare:', error);
                    alert('A apărut o eroare la salvarea setărilor de notificări. Te rugăm să încerci din nou.');
                }
            });
        }

        // Formular Schimbare Parolă
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    const formData = new FormData(this);
                    const response = await fetch('operations/update_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        redirectToProfile();
            } else {
                        alert(data.message || 'A apărut o eroare la schimbarea parolei.');
                    }
                } catch (error) {
                    console.error('Eroare la schimbarea parolei:', error);
                    alert('A apărut o eroare la schimbarea parolei. Te rugăm să încerci din nou.');
                }
            });
        }
    </script>
</body>
</html> 