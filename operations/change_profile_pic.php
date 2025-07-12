<?php
session_start();
include '../database/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

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

// Get current profile picture
$username = $_SESSION['username'];
$sql = "SELECT profile_photo FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Select a random picture different from the current one
$current_index = array_search($user['profile_photo'], $profile_pictures);
$available_indices = array_keys($profile_pictures);
if ($current_index !== false) {
    unset($available_indices[$current_index]);
}
$random_index = $available_indices[array_rand($available_indices)];
$new_pic = $profile_pictures[$random_index];

// Update the profile picture
$update_sql = "UPDATE users SET profile_photo = ? WHERE username = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("ss", $new_pic, $username);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Poza de profil a fost actualizată cu succes!";
} else {
    $_SESSION['error_message'] = "A apărut o eroare la actualizarea pozei de profil.";
}

// Redirect back to profile page
header('Location: ../profile.php');
exit();
?> 