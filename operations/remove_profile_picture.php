<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

// Verificăm dacă cererea este POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metodă invalidă. Trebuie să fie POST.'
    ]);
    exit;
}

// Log pentru debugging
error_log("Starting remove_profile_picture.php");

// Setăm direct poza la deleted.jpg, fără alte verificări
$deleted_photo = 'profile-photos/deleted.jpg';
$username = $_SESSION['username'];

error_log("Setting photo to: " . $deleted_photo);
error_log("For username: " . $username);

// Update database - punem direct deleted.jpg
$sql = "UPDATE users SET profile_photo = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $deleted_photo, $username);

$result = $stmt->execute();
error_log("Update result: " . ($result ? "success" : "failed"));

if ($result) {
    $response = [
        'success' => true,
        'default_picture_url' => $deleted_photo,
        'message' => 'Poza de profil a fost ștearsă cu succes!'
    ];
    error_log("Success response: " . json_encode($response));
    echo json_encode($response);
} else {
    $response = [
        'success' => false,
        'message' => 'Eroare la ștergerea pozei de profil.'
    ];
    error_log("Error response: " . json_encode($response));
    echo json_encode($response);
}
?> 