<?php

require __DIR__ . '/vendor/autoload.php';

use Zxing\QrReader;

// --- Database Configuration ---
$dbHost = 'localhost';
$dbName = 'school_system';
$dbUser = 'root'; // Replace with your DB username
$dbPass = '';     // Replace with your DB password

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
    
    $tmpFilePath = $_FILES['qr_image']['tmp_name'];

    try {
        // 1. Extract Student ID from QR Code
        $qrcode = new QrReader($tmpFilePath);
        $studentId = trim($qrcode->text());

        if (!empty($studentId)) {
            
            // 2. Connect to MySQL using PDO
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // 3. Optional: Prevent duplicate logs on the same day (uncomment if needed)
            /*
            $checkStmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND DATE(scanned_at) = CURDATE()");
            $checkStmt->execute([$studentId]);
            if ($checkStmt->fetch()) {
                echo "<h3>Already Scanned Today!</h3>";
                echo "<p>Student ID: " . htmlspecialchars($studentId) . " has already been logged today.</p>";
                echo '<br><a href="index.html">← Scan Next Student</a>';
                exit;
            }
            */

            // 4. Insert Attendance Record
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, scanned_at) VALUES (:student_id, NOW())");
            $stmt->execute([':student_id' => $studentId]);

            echo "<h3>Attendance Recorded!</h3>";
            echo "<p><strong>Student ID:</strong> " . htmlspecialchars($studentId) . "</p>";
            echo "<p><strong>Date & Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

        } else {
            echo "<h3>Failed to scan</h3>";
            echo "<p>No readable Student ID found in the photo. Please try again.</p>";
        }

    } catch (PDOException $e) {
        echo "<p>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    } catch (Exception $e) {
        echo "<p>Decoding Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

} else {
    echo "<p>No image submitted.</p>";
}

echo '<br><a href="index.html">← Scan Next Student</a>';