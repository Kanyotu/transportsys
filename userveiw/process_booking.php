<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'database.php';

// Get QR data from URL
$qr_data = $_GET['qr'] ?? '';
$demo_mode = isset($_GET['demo']) && $_GET['demo'] === 'true';

if (empty($qr_data) && !$demo_mode) {
    header("Location: scan_qr.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($demo_mode) {
    // Demo mode - show sample data
    $trip = [
        'tripid' => 1,
        'routename' => 'Demo Route - Nairobi CBD to Westlands',
        'platenumber' => 'KBC 123A',
        'sacconame' => 'Demo SACCO',
        'routeid' => 1
    ];
    
    // Get demo stages
    $stages = [
        ['stageid' => 1, 'stagename' => 'Nairobi CBD'],
        ['stageid' => 2, 'stagename' => 'Westlands'],
        ['stageid' => 3, 'stagename' => 'Parklands'],
        ['stageid' => 4, 'stagename' => 'Kileleshwa']
    ];
    
} else {
    // Parse QR data
    $params = [];
    $parts = explode(';', $qr_data);
    foreach ($parts as $part) {
        $pair = explode(':', $part);
        if (count($pair) == 2) {
            $params[trim($pair[0])] = trim($pair[1]);
        }
    }
    
    $sacco_id = $params['sacco'] ?? null;
    $bus_id = $params['bus'] ?? null;
    $trip_id = $params['trip'] ?? null;
    
    // Validate and fetch trip details
    $sql = "SELECT t.*, r.routename, b.platenumber, s.sacconame 
            FROM trips t
            JOIN routes r ON t.routeid = r.routeid
            JOIN buses b ON t.busid = b.busid
            JOIN saccos s ON b.saccoid = s.saccoid
            WHERE t.tripid = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: scan_qr.php?error=invalid_trip");
        exit();
    }
    
    $trip = $result->fetch_assoc();
    $stmt->close();
    
    // Get stages for this route
    $sql = "SELECT * FROM stages WHERE routeid = ? ORDER BY stageorder";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trip['routeid']);
    $stmt->execute();
    $stages_result = $stmt->get_result();
    $stages = [];
    while($row = $stages_result->fetch_assoc()) {
        $stages[] = $row;
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $from_stage = $_POST['from_stage'] ?? null;
    $to_stage = $_POST['to_stage'] ?? null;
    
    if (!$from_stage || !$to_stage || $from_stage == $to_stage) {
        $error = "Please select valid start and end points";
    } else {
        if ($demo_mode) {
            // Demo fare calculation
            $fare_amount = 100;
            
            // Create demo session
            $_SESSION['demo_session'] = [
                'from_stage' => $from_stage,
                'to_stage' => $to_stage,
                'fare' => $fare_amount,
                'trip' => $trip
            ];
            
            header("Location: demo_payment.php");
            exit();
            
        } else {
            // Calculate fare
            $sql = "SELECT amount FROM fares 
                    WHERE routeid = ? AND fromstageid = ? AND tostageid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $trip['routeid'], $from_stage, $to_stage);
            $stmt->execute();
            $fare_result = $stmt->get_result();
            
            if ($fare_result->num_rows > 0) {
                $fare = $fare_result->fetch_assoc();
                
                // Create trip session
                $sql = "INSERT INTO tripsessions (userid, tripid, fromstageid, tostageid, fareamount, status, expiresat)
                        VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiid", $user_id, $trip_id, $from_stage, $to_stage, $fare['amount']);
                
                if ($stmt->execute()) {
                    $session_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Redirect to payment
                    header("Location: initiate_payment.php?session=$session_id");
                    exit();
                } else {
                    $error = "Failed to create booking session";
                }
            } else {
                $error = "Fare not found for selected route";
            }
        }
    }
}

// Close connection if not in demo mode
if (!$demo_mode) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Journey | SafiriPay</title>
    <?php include 'header.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #0f5132;
            --secondary: #20c997;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
        }

        .booking-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .trip-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .trip-card h1 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trip-info {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .info-item i {
            color: var(--primary);
            width: 20px;
        }

        .journey-form {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 500;
        }

        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--secondary);
        }

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
            width: 100%;
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #0c4128;
            transform: translateY(-2px);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }

        .demo-badge {
            background: #ffeaa7;
            color: #e67e22;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .booking-container {
                padding: 1rem;
                margin: 1rem auto;
            }
            
            .trip-card, .journey-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="booking-container">
        <?php if ($demo_mode): ?>
            <div class="demo-badge">
                <i class="fas fa-vial"></i> Demo Mode
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Trip Information -->
        <div class="trip-card">
            <h1><i class="fas fa-route"></i> Select Your Journey</h1>
            
            <div class="trip-info">
                <div class="info-item">
                    <i class="fas fa-bus"></i>
                    <div>
                        <strong>Route:</strong> <?php echo htmlspecialchars($trip['routename']); ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-shuttle-van"></i>
                    <div>
                        <strong>Bus:</strong> <?php echo htmlspecialchars($trip['platenumber']); ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-building"></i>
                    <div>
                        <strong>SACCO:</strong> <?php echo htmlspecialchars($trip['sacconame']); ?>
                    </div>
                </div>
                
                <?php if (isset($trip['starttime'])): ?>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <strong>Departure:</strong> <?php echo date('h:i A', strtotime($trip['starttime'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Journey Selection Form -->
        <form method="POST" class="journey-form">
            <div class="form-group">
                <label for="from_stage"><i class="fas fa-map-marker-alt"></i> Starting Point</label>
                <select name="from_stage" id="from_stage" required>
                    <option value="">Select where you'll board</option>
                    <?php foreach ($stages as $stage): ?>
                        <option value="<?php echo $stage['stageid']; ?>">
                            <?php echo htmlspecialchars($stage['stagename']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="to_stage"><i class="fas fa-flag-checkered"></i> Destination</label>
                <select name="to_stage" id="to_stage" required>
                    <option value="">Select your destination</option>
                    <?php foreach ($stages as $stage): ?>
                        <option value="<?php echo $stage['stageid']; ?>">
                            <?php echo htmlspecialchars($stage['stagename']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-arrow-right"></i> Continue to Payment
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fromSelect = document.getElementById('from_stage');
            const toSelect = document.getElementById('to_stage');
            
            // When from stage is selected, remove it from to stage options
            fromSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                
                // Reset all options
                Array.from(toSelect.options).forEach(option => {
                    option.style.display = 'block';
                });
                
                // Hide the selected from option in to select
                if (selectedValue) {
                    const optionToHide = toSelect.querySelector(`option[value="${selectedValue}"]`);
                    if (optionToHide) {
                        optionToHide.style.display = 'none';
                    }
                    
                    // If to select has the same value, clear it
                    if (toSelect.value === selectedValue) {
                        toSelect.value = '';
                    }
                }
            });
            
            // Pre-select first and last stages for demo
            <?php if ($demo_mode && count($stages) > 1): ?>
                fromSelect.value = '<?php echo $stages[0]["stageid"]; ?>';
                toSelect.value = '<?php echo $stages[count($stages)-1]["stageid"]; ?>';
                
                // Trigger change event to update to select
                fromSelect.dispatchEvent(new Event('change'));
            <?php endif; ?>
        });
    </script>
</body>
</html>