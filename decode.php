<?php

require __DIR__ . '/vendor/autoload.php';

use Zxing\QrReader;

// Check if a file was uploaded without errors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
    
    $tmpFilePath = $_FILES['qr_image']['tmp_name'];

    try {
        // Read directly from the temporary uploaded file location
        $qrcode = new QrReader($tmpFilePath);
        $decodedText = $qrcode->text();

        if (!empty($decodedText)) {
            echo "<h3>QR Code Successfully Scanned!</h3>";
            echo "<p><strong>Payload:</strong> " . htmlspecialchars($decodedText) . "</p>";
        } else {
            echo "<h3>Failed to decode</h3>";
            echo "<p>No clear QR code was detected in the photo. Please try again with better lighting or focus.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error processing image: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

} else {
    echo "<p>Invalid submission or no image uploaded.</p>";
}

echo '<br><a href="index.html">← Take Another Photo</a>';