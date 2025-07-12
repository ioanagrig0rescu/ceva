<?php
// Array of source images (the ones you provided)
$source_images = [
    'image1.png', // Replace with actual source image names
    'image2.png',
    'image3.png',
    'image4.png',
    'image5.png',
    'image6.png',
    'image7.png',
    'image8.png'
];

// Target directory
$target_dir = '../profile-photos/';

// Create target directory if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Copy and rename each image
foreach ($source_images as $index => $source_image) {
    $source_path = $source_image; // Update this with the actual source path
    $target_path = $target_dir . 'default' . ($index + 1) . '.png';
    
    if (file_exists($source_path)) {
        if (copy($source_path, $target_path)) {
            echo "Successfully copied $source_image to $target_path<br>";
        } else {
            echo "Failed to copy $source_image<br>";
        }
    } else {
        echo "Source image $source_image does not exist<br>";
    }
}

echo "Setup complete!";
?> 