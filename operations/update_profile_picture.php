<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Nu sunteți autentificat']);
    exit;
}

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'Nu a fost încărcată nicio imagine']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tip de fișier nepermis. Sunt permise doar imagini JPG, PNG și GIF']);
    exit;
}

// Generate unique filename
$filename = uniqid() . '_' . time() . '.png';
$upload_path = '../profile-photos/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Verify if image is square after upload
    $image_info = getimagesize($upload_path);
    if ($image_info[0] !== $image_info[1]) {
        // If somehow the image is not square, we'll crop it to be square
        $size = min($image_info[0], $image_info[1]);
        
        // Create new image from file
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($upload_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($upload_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($upload_path);
                break;
            default:
                unlink($upload_path);
                echo json_encode(['success' => false, 'message' => 'Format de imagine nesuportat']);
                exit;
        }
        
        // Create a square image
        $square = imagecreatetruecolor($size, $size);
        
        // Calculate crop position for center
        $x = ($image_info[0] - $size) / 2;
        $y = ($image_info[1] - $size) / 2;
        
        // Copy and resize part of an image with resampling
        imagecopyresampled(
            $square,
            $source,
            0, 0, $x, $y,
            $size, $size,
            $size, $size
        );
        
        // Save the new square image
        imagepng($square, $upload_path);
        
        // Free up memory
        imagedestroy($source);
        imagedestroy($square);
    }
    
    // Update database with new photo path
    $photo_path = 'profile-photos/' . $filename;
    $username = $_SESSION['username'];
    
    // Get old photo path
    $sql = "SELECT profile_photo FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Delete old photo if it exists and is not a default photo
    if ($user['profile_photo'] && 
        !str_contains($user['profile_photo'], 'default') && 
        file_exists('../' . $user['profile_photo'])) {
        unlink('../' . $user['profile_photo']);
    }
    
    // Update database
    $sql = "UPDATE users SET profile_photo = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $photo_path, $username);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'picture_url' => $photo_path
        ]);
    } else {
        unlink($upload_path);
        echo json_encode([
            'success' => false,
            'message' => 'Eroare la actualizarea bazei de date'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Eroare la încărcarea fișierului'
    ]);
}
?> 