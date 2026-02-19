<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get user info from database
$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Close database connection (we'll open a new one when needed)
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #0f5132;
            --primary-dark: #0c4128;
            --secondary: #20c997;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Scan Container */
        .scan-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }

        /* Header */
        .scan-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .scan-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .scan-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* QR Scanner Area */
        .qr-scanner-area {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            text-align: center;
        }

        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .scanner-instructions {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid var(--secondary);
        }

        .scanner-instructions h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .scanner-instructions ol {
            text-align: left;
            margin-left: 1.5rem;
            color: var(--gray);
        }

        .scanner-instructions li {
            margin-bottom: 0.5rem;
        }

        /* Manual Entry */
        .manual-entry {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .manual-entry h2 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .manual-entry p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(15, 81, 50, 0.2);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #1ba87e;
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
        }

        /* Recent SACCOs */
        .recent-saccos {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
        }

        .recent-saccos h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sacco-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .sacco-card {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .sacco-card:hover {
            background: white;
            border-color: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .sacco-icon {
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1rem;
        }

        .sacco-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .sacco-route {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid var(--light-gray);
            border-top: 5px solid var(--secondary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        .loading-text {
            color: white;
            font-size: 1.2rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* QR Code Display */
        .qr-display {
            text-align: center;
            margin: 2rem 0;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }

        .qr-display img {
            max-width: 200px;
            margin: 1rem 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .scan-container {
                padding: 1rem;
                margin: 1rem auto;
            }
            
            .qr-scanner-area,
            .manual-entry,
            .recent-saccos {
                padding: 1.5rem;
            }
            
            .scan-header h1 {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 5px;
            }
            
            .sacco-grid {
                grid-template-columns: 1fr;
            }
            
            #qr-reader {
                width: 100%;
            }
        }

        /* Camera Permissions */
        .camera-permission {
            text-align: center;
            padding: 2rem;
            background: #fff3cd;
            border-radius: 10px;
            border: 1px solid #ffeaa7;
            margin-bottom: 1.5rem;
        }

        .camera-permission i {
            color: #e67e22;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Scan Result */
        .scan-result {
            background: #d1ecf1;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #bee5eb;
            display: none;
        }

        .scan-result.success {
            background: #d4edda;
            border-color: #c3e6cb;
        }

        .scan-result.error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="scan-container">
        <!-- Header -->
        <div class="scan-header">
            <h1><i class="fas fa-qrcode"></i> Scan Bus QR Code</h1>
            <p>Point your camera at the bus QR code to begin your journey</p>
        </div>

        <!-- Camera Permissions Notice -->
        <div class="camera-permission">
            <i class="fas fa-camera"></i>
            <h3>Camera Access Required</h3>
            <p>Please allow camera access to scan QR codes. Your camera feed is processed locally and never stored.</p>
        </div>

        <!-- QR Scanner Area -->
        <div class="qr-scanner-area">
            <div id="qr-reader"></div>
            
            <div class="scanner-instructions">
                <h3><i class="fas fa-info-circle"></i> How to Scan</h3>
                <ol>
                    <li>Allow camera access when prompted</li>
                    <li>Hold your phone steady about 6-12 inches from the QR code</li>
                    <li>Ensure good lighting and avoid glare</li>
                    <li>The scanner will automatically detect the code</li>
                </ol>
            </div>

            <!-- Scan Result -->
            <div id="scan-result" class="scan-result">
                <div id="result-content"></div>
            </div>
        </div>

        <!-- OR Divider -->
        <div style="text-align: center; margin: 2rem 0; position: relative;">
            <div style="height: 1px; background: var(--light-gray);"></div>
            <span style="background: white; padding: 0 1rem; position: relative; top: -12px; color: var(--gray);">OR</span>
        </div>

        <!-- Manual Entry -->
        <div class="manual-entry">
            <h2><i class="fas fa-keyboard"></i> Enter Manually</h2>
            <p>If you can't scan, enter the QR code data manually</p>
            
            <form id="manual-form">
                <div class="input-group">
                    <label for="sacco-id"><i class="fas fa-bus"></i> SACCO ID</label>
                    <input type="text" id="sacco-id" placeholder="Enter SACCO ID (e.g., SAC001)" required>
                </div>
                
                <div class="input-group">
                    <label for="bus-id"><i class="fas fa-bus-alt"></i> Bus ID</label>
                    <input type="text" id="bus-id" placeholder="Enter Bus ID (e.g., BUS123)" required>
                </div>
                
                <div class="input-group">
                    <label for="trip-id"><i class="fas fa-route"></i> Trip ID</label>
                    <input type="text" id="trip-id" placeholder="Enter Trip ID (e.g., TRIP456)" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Process Manual Entry
                </button>
            </form>
        </div>

        <!-- Recent SACCOs -->
        <div class="recent-saccos">
            <h2><i class="fas fa-history"></i> Recent SACCOs</h2>
            <p>Quickly select from your recently used transport operators</p>
            
            <div class="sacco-grid" id="recent-saccos">
                <!-- Dynamic content will be loaded here -->
                <div class="sacco-card" onclick="selectRecentSacco('Matatu Express', 'Nairobi CBD - Westlands')">
                    <div class="sacco-icon">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="sacco-name">Matatu Express</div>
                    <div class="sacco-route">Nairobi CBD - Westlands</div>
                </div>
                
                <div class="sacco-card" onclick="selectRecentSacco('City Shuttle', 'Thika - Nairobi')">
                    <div class="sacco-icon">
                        <i class="fas fa-shuttle-van"></i>
                    </div>
                    <div class="sacco-name">City Shuttle</div>
                    <div class="sacco-route">Thika - Nairobi</div>
                </div>
                
                <div class="sacco-card" onclick="selectRecentSacco('Metro Transit', 'Kikuyu - Town')">
                    <div class="sacco-icon">
                        <i class="fas fa-subway"></i>
                    </div>
                    <div class="sacco-name">Metro Transit</div>
                    <div class="sacco-route">Kikuyu - Town</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Processing your request...</div>
    </div>

    <script>
        // Initialize QR Scanner
        let html5QrcodeScanner;
        
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
            
            // Show loading
            showLoading();
            
            // Process the QR data
            processQRData(decodedText);
        }

        function onScanFailure(error) {
            // Handle scan failure
            console.warn(`QR scan failed: ${error}`);
        }

        // Initialize scanner when page loads
        document.addEventListener('DOMContentLoaded', function() {
            try {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader", 
                    { 
                        fps: 10, 
                        qrbox: { width: 250, height: 250 },
                        rememberLastUsedCamera: true,
                        showTorchButtonIfSupported: true
                    }, 
                    false
                );
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            } catch (error) {
                console.error("Error initializing QR scanner:", error);
                showError("QR scanner initialization failed. Please refresh the page.");
            }
            
            // Load recent SACCOs from localStorage
            loadRecentSaccos();
            
            // Setup manual form submission
            document.getElementById('manual-form').addEventListener('submit', function(e) {
                e.preventDefault();
                processManualEntry();
            });
        });

        function processQRData(qrData) {
            console.log("QR Data received:", qrData);
            
            // Parse QR data (expected format: "sacco:[id];bus:[id];trip:[id]")
            const params = {};
            const parts = qrData.split(';');
            
            parts.forEach(part => {
                const [key, value] = part.split(':');
                if (key && value) {
                    params[key.trim()] = value.trim();
                }
            });
            
            // Validate required parameters
            if (!params.sacco || !params.bus || !params.trip) {
                hideLoading();
                showError("Invalid QR code format. Please scan a valid bus QR code.");
                return;
            }
            
            // Save to recent SACCOs
            saveToRecentSaccos(params.sacco, "Unknown Route");
            
            // Send to server for processing
            fetch('process_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'qr_data': qrData,
                    'sacco_id': params.sacco,
                    'bus_id': params.bus,
                    'trip_id': params.trip
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showSuccess("QR code processed successfully! Redirecting...");
                    setTimeout(() => {
                        window.location.href = `process_booking.php?qr=${encodeURIComponent(qrData)}`;
                    }, 1500);
                } else {
                    showError(data.message || "Failed to process QR code.");
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showError("Network error. Please check your connection and try again.");
            });
        }

        function processManualEntry() {
            const saccoId = document.getElementById('sacco-id').value.trim();
            const busId = document.getElementById('bus-id').value.trim();
            const tripId = document.getElementById('trip-id').value.trim();
            
            if (!saccoId || !busId || !tripId) {
                showError("Please fill in all fields.");
                return;
            }
            
            showLoading();
            
            // Format as QR data
            const qrData = `sacco:${saccoId};bus:${busId};trip:${tripId}`;
            
            // Save to recent SACCOs
            saveToRecentSaccos(saccoId, "Manual Entry");
            
            // Send to server
            fetch('process_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'qr_data': qrData,
                    'sacco_id': saccoId,
                    'bus_id': busId,
                    'trip_id': tripId
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showSuccess("Manual entry processed successfully! Redirecting...");
                    setTimeout(() => {
                        window.location.href = `process_booking.php?qr=${encodeURIComponent(qrData)}`;
                    }, 1500);
                } else {
                    showError(data.message || "Failed to process manual entry.");
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showError("Network error. Please check your connection and try again.");
            });
        }

        function selectRecentSacco(saccoName, route) {
            // For demo purposes - in real app, this would fetch actual data
            showLoading();
            
            // Demo data
            const demoQR = `sacco:${saccoName.replace(/\s+/g, '').toUpperCase()};bus:BUS001;trip:TRIP001`;
            
            setTimeout(() => {
                hideLoading();
                showSuccess(`Selected ${saccoName}. Loading trip details...`);
                
                // In real app, redirect to booking page
                setTimeout(() => {
                    window.location.href = `process_booking.php?qr=${encodeURIComponent(demoQR)}&demo=true`;
                }, 1000);
            }, 1000);
        }

        // LocalStorage functions for recent SACCOs
        function saveToRecentSaccos(saccoName, route) {
            let recentSaccos = JSON.parse(localStorage.getItem('recentSaccos') || '[]');
            
            // Remove if already exists
            recentSaccos = recentSaccos.filter(s => s.name !== saccoName);
            
            // Add to beginning
            recentSaccos.unshift({
                name: saccoName,
                route: route,
                timestamp: new Date().toISOString()
            });
            
            // Keep only last 6
            if (recentSaccos.length > 6) {
                recentSaccos = recentSaccos.slice(0, 6);
            }
            
            localStorage.setItem('recentSaccos', JSON.stringify(recentSaccos));
            loadRecentSaccos();
        }

        function loadRecentSaccos() {
            const recentSaccos = JSON.parse(localStorage.getItem('recentSaccos') || '[]');
            const container = document.getElementById('recent-saccos');
            
            if (recentSaccos.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--gray);">
                        <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No recent SACCOs yet. Scan a QR code to add one here.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = recentSaccos.map(sacco => `
                <div class="sacco-card" onclick="selectRecentSacco('${sacco.name}', '${sacco.route}')">
                    <div class="sacco-icon">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="sacco-name">${sacco.name}</div>
                    <div class="sacco-route">${sacco.route}</div>
                    <div class="sacco-time" style="font-size: 0.8rem; color: var(--gray); margin-top: 0.5rem;">
                        ${formatTimeAgo(new Date(sacco.timestamp))}
                    </div>
                </div>
            `).join('');
        }

        function formatTimeAgo(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} min ago`;
            if (diffHours < 24) return `${diffHours} hr ago`;
            return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        }

        // UI Helper Functions
        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function showSuccess(message) {
            const resultDiv = document.getElementById('scan-result');
            const contentDiv = document.getElementById('result-content');
            
            resultDiv.className = 'scan-result success';
            contentDiv.innerHTML = `
                <h3 style="color: #155724; margin-bottom: 10px;">
                    <i class="fas fa-check-circle"></i> Success!
                </h3>
                <p style="color: #155724;">${message}</p>
            `;
            resultDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 5000);
        }

        function showError(message) {
            const resultDiv = document.getElementById('scan-result');
            const contentDiv = document.getElementById('result-content');
            
            resultDiv.className = 'scan-result error';
            contentDiv.innerHTML = `
                <h3 style="color: #721c24; margin-bottom: 10px;">
                    <i class="fas fa-exclamation-circle"></i> Error
                </h3>
                <p style="color: #721c24;">${message}</p>
            `;
            resultDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 5000);
        }

        // Torch control (if supported)
        let torchEnabled = false;
        
        function toggleTorch() {
            if (html5QrcodeScanner && html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
                const videoElement = document.querySelector('#qr-reader video');
                if (videoElement && videoElement.srcObject) {
                    const track = videoElement.srcObject.getVideoTracks()[0];
                    if (track && track.getCapabilities().torch) {
                        torchEnabled = !torchEnabled;
                        track.applyConstraints({
                            advanced: [{ torch: torchEnabled }]
                        });
                    }
                }
            }
        }

        // Handle page visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Pause scanner when page is hidden
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.pause();
                }
            } else {
                // Resume scanner when page is visible
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.resume();
                }
            }
        });
    </script>
</body>
</html>