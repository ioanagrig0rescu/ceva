<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

// Lista exactă de avatare disponibile
$default_photos = [
    'profile-photos/default2.png',
    'profile-photos/default3.png',
    'profile-photos/default5.png',
    'profile-photos/default6.png',
    'profile-photos/default7.png',
    'profile-photos/default8.png'
];

// Get current user's photo
$username = $_SESSION['username'];
$sql = "SELECT profile_photo FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Remove current photo from options if it exists
if ($user && $user['profile_photo']) {
    $current_photo_key = array_search($user['profile_photo'], $default_photos);
    if ($current_photo_key !== false) {
        unset($default_photos[$current_photo_key]);
        $default_photos = array_values($default_photos); // Reindex array
    }
}

// Select random photo from remaining options
$random_photo = $default_photos[array_rand($default_photos)];

// Delete old photo if it's not a default one, deleted.jpg, or SVG
if ($user && $user['profile_photo'] && 
    !str_contains($user['profile_photo'], 'default') && 
    !str_contains($user['profile_photo'], 'deleted.jpg') &&
    !str_contains($user['profile_photo'], 'data:image/svg+xml;base64')) {
    $file_path = '../' . $user['profile_photo'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Update user's profile photo
$update_sql = "UPDATE users SET profile_photo = ? WHERE username = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("ss", $random_photo, $username);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'picture_url' => $random_photo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile photo'
    ]);
}
?> 