<?php
// ==========================================
// BACKEND: PHP API Endpoint
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) &&$_POST['action'] === 'save_attendance') {
    header('Content-Type: application/json');

    $studentId = trim($_POST['student_id'] ?? '');

    if (empty($studentId)) {
        echo json_encode(['status' => 'error', 'message' => 'No Student ID provided.']);
        exit;
    }

    $dbHost = 'localhost';$dbName = 'school_db';
    $dbUser = 'root';$dbPass = '';

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser,$dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt =$pdo->prepare("INSERT INTO attendance (student_id, scanned_at) VALUES (:student_id, NOW())");
        $stmt->execute([':student_id' =>$studentId]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Attendance logged successfully.',
            'student_id' => $studentId
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    // Stop execution so the rest of the HTML isn't returned in the JSON fetch response
    exit;
}
?>

<!-- ==========================================
     FRONTEND: HTML & JavaScript Scanner
     ========================================== -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Attendance Scanner</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            text-align: center; 
            padding: 20px; 
            background-color: #f8f9fa;
            margin: 0;
        }
        h2 { margin-bottom: 20px; color: #333; }
        #reader { 
            width: 100%; 
            max-width: 450px; 
            margin: 0 auto; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: #000;
        }
        .result-box {
            display: none;
            margin: 20px auto 0;
            padding: 20px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            max-width: 450px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .btn-scan {
            margin-top: 15px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: bold;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        .btn-scan:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>

    <h2>Student Attendance Scanner</h2>
    
    <div id="reader"></div>
    
    <div id="result-container" class="result-box">
        <div id="result-text"></div>
        <p id="db-status" style="font-weight: bold; margin-top: 10px;"></p>
        <button class="btn-scan" onclick="resetScanner()">Scan Next Student</button>
    </div>

    <script>
        let html5QrcodeScanner;

        function qrBoxFunction(viewfinderWidth, viewfinderHeight) {
            let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
            let qrboxSize = Math.floor(minEdgeSize * 0.7);
            return { width: qrboxSize, height: qrboxSize };
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (navigator.vibrate) {
                navigator.vibrate(200); 
            }

            html5QrcodeScanner.pause(true);

            document.getElementById('result-text').innerHTML = `
                <h3 style="margin: 0 0 10px 0; color: #333;">Card Detected</h3>
                <p style="font-size: 1.2rem; margin: 0;"><strong>Student ID:</strong> ${decodedText}</p>
            `;
            
            const statusEl = document.getElementById('db-status');
            statusEl.style.color = '#666';
            statusEl.innerText = 'Saving to database...';
            document.getElementById('result-container').style.display = 'block';

            // Post back to the exact same file (attendance.php)
            fetch('attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=save_attendance&student_id=' + encodeURIComponent(decodedText)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.style.color = '#198754';
                    statusEl.innerText = '✓ Recorded in Database';
                } else {
                    statusEl.style.color = '#dc3545';
                    statusEl.innerText = '⚠ ' + (data.message || 'Failed to record');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusEl.style.color = '#dc3545';
                statusEl.innerText = '⚠ Server Connection Error';
            });
        }

        function resetScanner() {
            document.getElementById('result-container').style.display = 'none';
            html5QrcodeScanner.resume();
        }

        const config = {
            fps: 15,
            qrbox: qrBoxFunction,
            videoConstraints: {
                facingMode: "environment",
                width: { min: 640, ideal: 1280, max: 1920 },
                height: { min: 480, ideal: 720, max: 1080 },
                focusMode: "continuous"
            }
        };

        html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>