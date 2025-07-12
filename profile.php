<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funcție pentru traducerea lunilor în română
function translateMonth($month) {
    $months = array(
        'January' => 'ianuarie',
        'February' => 'februarie',
        'March' => 'martie',
        'April' => 'aprilie',
        'May' => 'mai',
        'June' => 'iunie',
        'July' => 'iulie',
        'August' => 'august',
        'September' => 'septembrie',
        'October' => 'octombrie',
        'November' => 'noiembrie',
        'December' => 'decembrie',
        'Jan' => 'ian',
        'Feb' => 'feb',
        'Mar' => 'mar',
        'Apr' => 'apr',
        'May' => 'mai',
        'Jun' => 'iun',
        'Jul' => 'iul',
        'Aug' => 'aug',
        'Sep' => 'sep',
        'Oct' => 'oct',
        'Nov' => 'noi',
        'Dec' => 'dec'
    );
    return isset($months[$month]) ? $months[$month] : $month;
}

include 'sesion-check/check.php';
include 'database/db.php';
include 'plugins/bootstrap.html';
include 'operations/get_login_details.php';

// Handle display name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    $names_file = 'data/display_names.json';
    $display_names = file_exists($names_file) ? json_decode(file_get_contents($names_file), true) : [];
    $display_names = is_array($display_names) ? $display_names : [];
    
    $display_names[$_SESSION['username']] = trim($_POST['display_name']);
    
    if (!file_exists('data')) {
        mkdir('data', 0777, true);
    }
    
    file_put_contents($names_file, json_encode($display_names, JSON_PRETTY_PRINT));
    
    header('Location: profile.php');
    exit();
}

// Get display name
$names_file = 'data/display_names.json';
$display_names = file_exists($names_file) ? json_decode(file_get_contents($names_file), true) : [];
$display_names = is_array($display_names) ? $display_names : [];
$current_display_name = isset($display_names[$_SESSION['username']]) ? 
    $display_names[$_SESSION['username']] : 
    ucwords(str_replace(['_', '.'], ' ', $_SESSION['username']));

// Array of profile pictures as base64
$profile_pictures = [
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FF6B6B"/><path d="M30 40 Q50 20 70 40" stroke="white" stroke-width="4" fill="none"/><circle cx="35" cy="35" r="5" fill="white"/><circle cx="65" cy="35" r="5" fill="white"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FF69B4"/><path d="M30 40 Q50 20 70 40" stroke="white" stroke-width="4" fill="none"/><circle cx="35" cy="35" r="5" fill="white"/><circle cx="65" cy="35" r="5" fill="white"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FF69B4"/><circle cx="50" cy="50" r="25" fill="#FFD700"/><path d="M40 50 Q50 60 60 50" stroke="black" stroke-width="2" fill="none"/><circle cx="45" cy="45" r="3" fill="black"/><circle cx="55" cy="45" r="3" fill="black"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FF69B4"/><path d="M35 50 Q50 60 65 50" stroke="white" stroke-width="3" fill="none"/><circle cx="35" cy="40" r="5" fill="white"/><circle cx="65" cy="40" r="5" fill="white"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FFD700"/><circle cx="50" cy="50" r="35" fill="white"/><path d="M40 50 Q50 60 60 50" stroke="black" stroke-width="2" fill="none"/><circle cx="45" cy="45" r="3" fill="black"/><circle cx="55" cy="45" r="3" fill="black"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#FF69B4"/><path d="M35 50 Q50 60 65 50" stroke="white" stroke-width="3" fill="none"/><circle cx="35" cy="40" r="5" fill="white"/><circle cx="65" cy="40" r="5" fill="white"/><circle cx="85" cy="15" r="8" fill="#FFD700"/><circle cx="15" cy="85" r="8" fill="#FFD700"/></svg>'),
    'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M10,50 Q50,90 90,50 Q50,10 10,50" fill="#FFB6C1"/><path d="M35 60 Q50 70 65 60" stroke="#4169E1" stroke-width="3" fill="none"/><circle cx="40" cy="45" r="3" fill="black"/><circle cx="60" cy="45" r="3" fill="black"/><circle cx="50" cy="50" r="3" fill="#32CD32"/></svg>')
];

// If user doesn't have a profile picture, assign a random one
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $check_pic_sql = "SELECT profile_photo FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_pic_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (empty($user['profile_photo'])) {
        $random_pic = $profile_pictures[array_rand($profile_pictures)];
        $update_pic_sql = "UPDATE users SET profile_photo = ? WHERE username = ?";
        $stmt = $conn->prepare($update_pic_sql);
        $stmt->bind_param("ss", $random_pic, $username);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profile - Budget Master</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
       @import "https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700";

        body {
            background: #f7f7ff;
            margin-top: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .profile-card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 0 solid transparent;
            border-radius: 25px;
            margin-bottom: 0;
            box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
            padding: 2rem 2rem 2rem 4rem;
            height: 500px;
        }

        .profile-header {
            text-align: left;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 0 1.5rem 0;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: #fff;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .name-container {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .name-container h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .name-container p {
            color: #718096;
            font-size: 1rem;
        }

        .profile-info {
            margin-top: 1rem;
            text-align: left;
        }

        .info-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .info-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 1rem;
            color: #2d3748;
        }

        .edit-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e1e1e1;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }

        .new-profile-pic {
            cursor: pointer;
            color: #667eea;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .new-profile-pic:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .login-logs-card {
            background-color: #fff;
            border-radius: 25px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px 0 rgb(218 218 253 / 65%), 0 2px 6px 0 rgb(206 206 238 / 54%);
            height: 500px;
            margin-bottom: 0;
        }

        .login-logs-card h3 {
            position: sticky;
            top: 0;
            background-color: #fff;
            padding: 0.5rem 0;
            margin: 0 0 1rem 0;
            z-index: 1;
            color: #667eea;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-logs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            height: calc(100% - 50px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .log-entry {
            background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
            border-radius: 10px;
            padding: 0.4rem;
            display: flex;
            gap: 0.4rem;
            align-items: center;
            transition: all 0.3s ease;
            margin-bottom: 0.3rem;
            flex-shrink: 0;
        }

        .log-entry:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
        }

        .log-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .log-details {
            flex-grow: 1;
            min-width: 0; /* Pentru a permite text overflow */
        }

        .log-time {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.2rem;
            font-size: 0.8rem;
        }

        .log-info {
            background: rgba(102,126,234,0.05);
            border-radius: 6px;
            padding: 0.3rem;
            display: flex;
            flex-wrap: nowrap;
            gap: 0.4rem;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }

        .log-info::-webkit-scrollbar {
            display: none; /* Chrome, Safari and Opera */
        }

        .log-device, .log-os, .log-browser, .log-ip {
            color: #616f80;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.2rem;
            margin: 0;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .log-device i, .log-os i, .log-browser i, .log-ip i {
            width: 12px;
            text-align: center;
            color: #667eea;
            font-size: 0.75rem;
        }

        /* Eliminăm width-ul fix pentru a permite aranjarea pe un singur rând */
        .log-device, .log-os, .log-browser, .log-ip {
            width: auto;
        }

        /* Custom scrollbar */
        .login-logs-card::-webkit-scrollbar {
            width: 8px;
        }

        .login-logs-card::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .login-logs-card::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }

        .login-logs-card::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Custom scrollbar pentru login-logs-list */
        .login-logs-list::-webkit-scrollbar {
            width: 6px;
        }

        .login-logs-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .login-logs-list::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 3px;
        }

        .login-logs-list::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Adăugăm stil pentru butonul de modificare cont */
        .modify-account-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
            text-decoration: none;
            margin: 0;
        }

        .modify-account-btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
            text-decoration: none;
        }

        .modify-account-btn i {
            margin-right: 0.5rem;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
            display: inline-block;
            width: fit-content;
            text-decoration: none;
            margin: 0 0 0 1rem;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #ee5253 0%, #ff6b6b 100%);
            color: white;
            text-decoration: none;
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        .buttons-container {
            display: flex;
            align-items: center;
            margin-top: 1rem;
            margin-bottom: 2rem;
            padding-left: 1rem;
        }

        @media (max-width: 991px) {
            .profile-card, .login-logs-card {
                margin-bottom: 1rem;
            }
            
            .buttons-container {
                justify-content: center;
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar/navbar.php'; ?>

    <div class="container" style="margin-top: 120px;">
        <div class="row">
            <div class="col-lg-8">
                <div class="profile-card">
<?php
                    $username = $_SESSION['username'];
                    $sql = "SELECT * FROM users WHERE username = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $row = $result->fetch_assoc()) {
        $email = $row['email'];
                        $created_at = new DateTime($row['created_at']);
                        $profile_photo = $row['profile_photo'];
                        
                        if (empty($profile_photo)) {
                            $profile_photo = $profile_pictures[array_rand($profile_pictures)];
                        }
                    ?>
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                            <div class="name-container">
                                <h2>
                                    <?php echo htmlspecialchars($current_display_name); ?>
                                </h2>
                            <p class="text-muted">Membru din <?php 
                                $month = $created_at->format('F');
                                $year = $created_at->format('Y');
                                echo translateMonth($month) . ' ' . $year; 
                            ?></p>
                                </div>

                        <div class="profile-info">
                            <div class="info-group">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($username); ?></div>
                    </div>
                                    </div>
                    <?php
                    } else {
                        echo '<div class="alert alert-danger">Eroare la încărcarea profilului.</div>';
                    }
                    ?>
                        </div>
                                    </div>
                                </div>
            <div class="col-lg-4">
                <div class="login-logs-card">
                    <h3><i class="fas fa-history"></i> Istoric Autentificări</h3>
                    <div class="login-logs-list">
                        <?php
                        // Get all login logs
                        $logs_sql = "SELECT * FROM login_logs WHERE username = ? ORDER BY login_date DESC";
                        $stmt = $conn->prepare($logs_sql);
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $logs_result = $stmt->get_result();

                        if ($logs_result->num_rows > 0) {
                            while ($log = $logs_result->fetch_assoc()) {
                                $login_time = new DateTime($log['login_date']);
                                ?>
                                <div class="log-entry">
                                    <div class="log-icon">
                                        <?php if (stripos($log['device'], 'mobile') !== false): ?>
                                            <i class="fas fa-mobile-alt"></i>
                                        <?php elseif (stripos($log['device'], 'tablet') !== false): ?>
                                            <i class="fas fa-tablet-alt"></i>
                                        <?php else: ?>
                                            <i class="fas fa-desktop"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="log-details">
                                        <div class="log-time">
                                            <i class="far fa-clock"></i>
                                            <?php 
                                                $day = $login_time->format('d');
                                                $month = $login_time->format('M');
                                                $year = $login_time->format('Y');
                                                $time = $login_time->format('H:i');
                                                echo $day . ' ' . translateMonth($month) . ' ' . $year . ', ' . $time; 
                                            ?>
                                        </div>
                                        <div class="log-info">
                                            <div class="log-device">
                                                <i class="fas fa-laptop"></i>
                                                <?php echo htmlspecialchars($log['device']); ?>
                                            </div>
                                            <div class="log-os">
                                                <i class="fas fa-microchip"></i>
                                                <?php echo htmlspecialchars($log['os']); ?>
                                            </div>
                                            <div class="log-browser">
                                                <i class="fas fa-globe"></i>
                                                <?php echo htmlspecialchars($log['browser']); ?>
                                            </div>
                                            <div class="log-ip">
                                                <i class="fas fa-network-wired"></i>
                                                <?php echo htmlspecialchars($log['ip']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="text-muted">Nu există înregistrări de autentificare.</p>';
                        }
                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="buttons-container" style="justify-content: flex-start;">
            <a href="edit_account.php" class="modify-account-btn">
                <i class="fas fa-cog"></i> Setări
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Deconectare
            </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Editeaza Profilul</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm" action="operations/update_profile.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Parola Actuală</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Parola Nouă (opțional)</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword">
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmă Parola Nouă</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                            <button type="submit" class="btn btn-primary">Salvează</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer/footer.html'; ?>

    <script>
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Parolele noi nu se potrivesc!');
            }
        });
    </script>
</body>
</html>