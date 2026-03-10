<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$session_id = $_GET['session'] ?? null;
if (!$session_id) {
    // If no session, check if we are browsing long-distance trips
    $sql = "SELECT t.*, r.routename, b.platenumber, s.sacconame, b.saccoid 
            FROM trips t
            JOIN routes r ON t.routeid = r.routeid
            JOIN buses b ON t.busid = b.busid
            JOIN saccos s ON b.saccoid = s.saccoid
            WHERE t.trip_type = 'long' AND t.status = 'active'";
    $trips_result = $conn->query($sql);
    $available_trips = [];
    while($row = $trips_result->fetch_assoc()) {
        $available_trips[] = $row;
    }
} else {

// Fetch session details
$sql = "SELECT ts.*, t.busid, b.platenumber, r.routename 
        FROM tripsessions ts
        JOIN trips t ON ts.tripid = t.tripid
        JOIN buses b ON t.busid = b.busid
        JOIN routes r ON t.routeid = r.routeid
        WHERE ts.sessionid = ? AND ts.userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
$stmt->execute();
$session_data = $stmt->get_result()->fetch_assoc();

if (!$session_data) {
    header("Location: dashboard.php");
    exit();
}

// Fetch available seats for this bus
$sql = "SELECT * FROM seats WHERE busid = ? ORDER BY seatnumber";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_data['busid']);
$stmt->execute();
$seats_result = $stmt->get_result();
$seats = [];
while ($row = $seats_result->fetch_assoc()) {
    $seats[] = $row;
}

// If no seats in DB, generate some for demo/initial use
if (empty($seats)) {
    for ($i = 1; $i <= 14; $i++) {
        $seat_num = "S" . $i;
        $seats[] = ['seatid' => $i, 'seatnumber' => $seat_num, 'status' => 'available'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_seat = $_POST['seat_id'] ?? null;
    $disability = $_POST['disability'] ?? 'None';
    $luggage = $_POST['luggage'] ?? 'None';

    if (!$selected_seat) {
        $error = "Please select a seat";
    } else {
        // Calculate luggage fee
        $luggage_fees = [
            'None' => 0,
            'Small' => 0,
            'Medium' => 50,
            'Large' => 100,
            'Extra' => 200
        ];
        $luggage_fee = $luggage_fees[$luggage] ?? 0;
        $total_fare = $session_data['fareamount'] + $luggage_fee;

        $sql = "UPDATE tripsessions SET seatid = ?, disability_type = ?, luggage_type = ?, fareamount = ? WHERE sessionid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdi", $selected_seat, $disability, $luggage, $total_fare, $session_id);
        
        if ($stmt->execute()) {
            // Mark the seat as occupied in the 'seats' table
            $update_seat_sql = "UPDATE seats SET status = 'occupied' WHERE seatid = ?";
            $update_seat_stmt = $conn->prepare($update_seat_sql);
            $update_seat_stmt->bind_param("i", $selected_seat);
            $update_seat_stmt->execute();
            $update_seat_stmt->close();

            header("Location: initiate_payment.php?session=$session_id"); 
            exit();
        } else {
            $error = "Failed to save selection";
        }
    }
}
} // End of session-only block
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Selection | SafiriPay Premium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="darkmode.css">
    <script src="darkmode.js"></script>
    <style>
        :root {
            --primary: #059669; /* Emerald 600 */
            --primary-dark: #064e3b; /* Emerald 900 */
            --accent: #10b981; /* Emerald 500 */
            --aqua: #2dd4bf; /* Teal 400 */
            --glass: rgba(255, 255, 255, 0.7);
            --glass-dark: rgba(255, 255, 255, 0.1);
            --available: #f3f4f6;
            --occupied: #ef4444;
            --selected: #10b981;
            --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfeff 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #1f2937;
        }

        .booking-page {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            min-height: calc(100vh - 80px); /* Adjust for header */
        }

        .premium-container {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Bus Cabin Styling */
        .cabin-section {
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.4);
            border-right: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section-header { margin-bottom: 2rem; }
        .section-header h2 { font-weight: 800; font-size: 1.8rem; margin: 0; color: var(--primary-dark); }
        .section-header p { color: var(--primary); margin: 0.5rem 0 0; font-weight: 600; font-size: 0.9rem; letter-spacing: 0.5px; text-transform: uppercase; }

        .bus-cabin {
            background: #cbd5e1;
            padding: 40px 20px 20px;
            border-radius: 40px 40px 20px 20px;
            position: relative;
            max-width: 320px;
            margin: 0 auto;
            box-shadow: inset 0 4px 10px rgba(0,0,0,0.1);
        }

        .bus-cabin::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: #94a3b8;
            border-radius: 2px;
        }

        .driver-seat {
            position: absolute;
            top: 10px;
            right: 20px;
            width: 35px;
            height: 35px;
            background: #64748b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 30px;
        }

        .seat {
            aspect-ratio: 1;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 0.85rem;
            border-bottom: 4px solid #e2e8f0;
            user-select: none;
        }

        .seat:hover:not(.occupied) {
            transform: scale(1.1);
            background: #f0fdf4;
            border-color: var(--secondary);
        }

        .seat.occupied {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fecaca;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .seat.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary-dark);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .aisle { grid-column: 3; visibility: hidden; }

        /* Options Selection Styling */
        .options-section {
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .legend-premium {
            display: flex;
            gap: 15px;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
        }

        .color-dot { width: 12px; height: 12px; border-radius: 4px; }

        .option-card {
            background: rgba(255, 255, 255, 0.5);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .option-card:focus-within {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .option-card label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .option-card i { color: var(--primary); }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }

        select:focus { border-color: var(--primary); }

        .btn-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.2);
            margin-top: 1rem;
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(5, 150, 105, 0.3);
        }

        .btn-premium:active { transform: translateY(0); }

        .btn-premium:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Error Message */
        .toast-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #fecaca;
            animation: shake 0.5s ease;
        }

        .fare-summary {
            background: rgba(5, 150, 105, 0.05) !important;
            border: 1px dashed var(--primary) !important;
        }

        .fare-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .fare-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: #4b5563;
        }

        .fare-row.total {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(5, 150, 105, 0.2);
            font-weight: 800;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Mobile Viewport */
        @media (max-width: 900px) {
            .booking-page {
                padding: 1rem;
            }
            .premium-container { 
                grid-template-columns: 1fr; 
                border-radius: 16px;
            }
            .cabin-section { 
                border-right: none; 
                border-bottom: 1px solid rgba(0,0,0,0.05); 
                padding: 1.5rem;
            }
            .options-section {
                padding: 1.5rem;
            }
            
            .trip-browser-card {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            .trip-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .section-header h2 {
                font-size: 1.5rem;
            }
            .bus-cabin {
                transform: scale(0.9);
                transform-origin: top center;
                margin-bottom: -20px;
            }
            .seat-grid {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="booking-page">
        <div class="premium-container">
            <?php if (!$session_id): ?>
                <!-- Trip Browser View -->
                <div class="cabin-section" style="grid-column: span 2; border-right: none;">
                    <div class="section-header">
                        <p>Available Journeys</p>
                        <h2>Long Distance Travel</h2>
                    </div>

                    <div class="trip-browser-grid">
                        <?php if (empty($available_trips)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bus-alt"></i>
                                <p>No long-distance trips scheduled at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($available_trips as $trip): ?>
                                <div class="trip-browser-card">
                                    <div class="trip-browser-info">
                                        <h3><?php echo htmlspecialchars($trip['routename']); ?></h3>
                                        <div class="trip-meta">
                                            <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($trip['sacconame']); ?></span>
                                            <span><i class="fas fa-shuttle-van"></i> <?php echo htmlspecialchars($trip['platenumber']); ?></span>
                                            <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($trip['starttime'])); ?></span>
                                        </div>
                                    </div>
                                    <a href="process_booking.php?qr=<?php echo urlencode("sacco:" . $trip['saccoid'] . ";bus:" . $trip['busid'] . ";trip:" . $trip['tripid']); ?>" class="btn-premium" style="width: auto; padding: 12px 24px; text-decoration: none;">
                                        Book Now <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <style>
                    .trip-browser-grid { display: grid; gap: 1.5rem; margin-top: 1rem; }
                    .trip-browser-card { 
                        background: white; 
                        padding: 1.5rem; 
                        border-radius: 16px; 
                        display: flex; 
                        justify-content: space-between; 
                        align-items: center;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                        border: 1px solid rgba(0,0,0,0.05);
                    }
                    .trip-browser-info h3 { margin: 0 0 0.5rem; color: var(--primary-dark); }
                    .trip-meta { display: flex; gap: 1.5rem; color: #64748b; font-size: 0.9rem; }
                    .trip-meta span { display: flex; align-items: center; gap: 6px; }
                </style>
            <?php else: ?>
                <!-- Seat Selection View (Existing) -->
                <div class="cabin-section">
                    <div class="section-header">
                        <p>Bus Cabin View</p>
                        <h2>Select Your Seat</h2>
                    </div>

                    <div class="legend-premium">
                        <div class="legend-item"><div class="color-dot" style="background:#e2e8f0"></div> Available</div>
                        <div class="legend-item"><div class="color-dot" style="background:#fee2e2"></div> Occupied</div>
                        <div class="legend-item"><div class="color-dot" style="background:var(--primary)"></div> Selected</div>
                    </div>

                    <div class="bus-cabin">
                        <div class="driver-seat"><i class="fas fa-user-tie"></i></div>
                        <div class="seat-grid">
                            <?php 
                            $count = 0;
                            foreach ($seats as $seat): 
                                $count++;
                                if (($count + 1) % 4 == 3) { echo '<div class="aisle"></div>'; }
                            ?>
                                <div class="seat <?php echo $seat['status'] == 'occupied' ? 'occupied' : ''; ?>" 
                                     data-id="<?php echo $seat['seatid']; ?>"
                                     onclick="selectSeat(this)">
                                    <?php echo $seat['seatnumber']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <form method="POST" id="bookingForm" class="options-section">
                <input type="hidden" name="seat_id" id="selectedSeatInput">
                
                <div>
                    <div class="section-header">
                        <p>Passenger Details</p>
                        <h2>Travel Preferences</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="toast-error">
                            <i class="fas fa-circle-exclamation"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="option-card">
                        <label><i class="fas fa-wheelchair"></i> Disability Consideration</label>
                        <select name="disability">
                            <option value="None">No special requirements</option>
                            <option value="Wheelchair">Wheelchair User</option>
                            <option value="Visually Impaired">Visually Impaired</option>
                            <option value="Elderly">Elderly / Limited Mobility</option>
                            <option value="Other">Other accessibility needs</option>
                        </select>
                    </div>

                    <div class="option-card">
                        <label><i class="fas fa-suitcase"></i> Luggage Option</label>
                        <select name="luggage">
                            <option value="None">Hand Luggage Only (Small Bag)</option>
                            <option value="Small">Backpack / Laptop Bag (FREE)</option>
                            <option value="Medium">Standard Suitcase (Ksh 50)</option>
                            <option value="Large">Large Trunk / Sack (Ksh 100)</option>
                            <option value="Extra">Multiple Oversized Items</option>
                        </select>
                    </div>

                    <div class="option-card fare-summary">
                        <label><i class="fas fa-receipt"></i> Fare Summary</label>
                        <div class="fare-details">
                            <div class="fare-row">
                                <span>Base Fare</span>
                                <span>Ksh <?php echo number_format($session_data['fareamount'], 2); ?></span>
                            </div>
                            <div class="fare-row">
                                <span>Luggage Fee</span>
                                <span id="luggageFeeDisplay">Ksh 0.00</span>
                            </div>
                            <div class="fare-row total">
                                <span>Total Amount</span>
                                <span id="totalFareDisplay">Ksh <?php echo number_format($session_data['fareamount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-premium" id="confirmBtn" disabled>
                    Complete Booking <i class="fas fa-arrow-right-long"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function selectSeat(element) {
            if (element.classList.contains('occupied')) return;

            // Deselect all
            document.querySelectorAll('.seat').forEach(s => s.classList.remove('selected'));
            
            // Select this one
            element.classList.add('selected');
            document.getElementById('selectedSeatInput').value = element.dataset.id;
            
            // Enable button
            document.getElementById('confirmBtn').disabled = false;
            
            // Subtle haptic feel (if supported)
            if (window.navigator.vibrate) {
                window.navigator.vibrate(50);
            }
        }

        document.querySelector('select[name="luggage"]').addEventListener('change', function() {
            const fees = {
                'None': 0,
                'Small': 0,
                'Medium': 50,
                'Large': 100,
                'Extra': 200
            };
            const baseFare = <?php echo $session_data['fareamount'] ?? 0; ?>;
            const fee = fees[this.value] || 0;
            const total = baseFare + fee;

            document.getElementById('luggageFeeDisplay').textContent = 'Ksh ' + fee.toFixed(2);
            document.getElementById('totalFareDisplay').textContent = 'Ksh ' + total.toFixed(2);
        });
    </script>
</body>
</html>
