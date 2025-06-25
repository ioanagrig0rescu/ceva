<?php
// Creăm directorul dompdf dacă nu există
if (!file_exists('dompdf')) {
    mkdir('dompdf', 0777, true);
}

// URL-ul pentru dompdf
$dompdfUrl = 'https://raw.githubusercontent.com/dompdf/dompdf/v2.0.3/';
$files = [
    'autoload.inc.php',
    'lib/Cpdf.php',
    'src/Dompdf.php',
    'src/Adapter/CPDF.php',
    'src/Options.php',
    'src/FontMetrics.php',
    'src/Frame.php',
    'src/FrameDecorator/AbstractFrameDecorator.php',
    'src/FrameReflower/AbstractFrameReflower.php',
    'src/Positioner/AbstractPositioner.php'
];

// Creăm structura de directoare
mkdir('dompdf/dompdf', 0777, true);
mkdir('dompdf/dompdf/lib', 0777, true);
mkdir('dompdf/dompdf/src', 0777, true);
mkdir('dompdf/dompdf/src/Adapter', 0777, true);
mkdir('dompdf/dompdf/src/FrameDecorator', 0777, true);
mkdir('dompdf/dompdf/src/FrameReflower', 0777, true);
mkdir('dompdf/dompdf/src/Positioner', 0777, true);

// Descărcăm fiecare fișier
foreach ($files as $file) {
    $fileUrl = $dompdfUrl . $file;
    $localPath = 'dompdf/dompdf/' . $file;
    
    // Ne asigurăm că directorul există
    $dir = dirname($localPath);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Descărcăm fișierul
    $content = @file_get_contents($fileUrl);
    if ($content === false) {
        echo "Eroare la descărcarea: $fileUrl<br>";
        continue;
    }
    file_put_contents($localPath, $content);
    echo "Fișier descărcat: $file<br>";
}

echo "DomPDF a fost instalat cu succes!";
?> 