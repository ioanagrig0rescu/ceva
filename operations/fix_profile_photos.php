<?php
session_start();
include __DIR__ . '/../database/db.php';

// First, let's check if any users have default5.png as their profile photo
$sql = "SELECT id, username, profile_photo FROM users WHERE profile_photo LIKE '%default5%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Get list of valid default photos
    $default_photos = [
        'profile-photos/default1.png',
        'profile-photos/default2.png',
        'profile-photos/default3.png',
        'profile-photos/default6.png',
        'profile-photos/default7.png',
        'profile-photos/default8.png'
    ];

    while ($user = $result->fetch_assoc()) {
        // Select a random valid default photo
        $new_photo = $default_photos[array_rand($default_photos)];
        
        // Update the user's profile photo
        $update_sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_photo, $user['id']);
        $stmt->execute();
        
        echo "Updated profile photo for user {$user['username']} from default5.png to {$new_photo}<br>";
    }
    
    echo "All references to default5.png have been fixed.";
} else {
    echo "No users found with default5.png as their profile photo.";
}

$conn->close();
?> 