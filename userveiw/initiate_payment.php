<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'database.php';
include 'checkinguserindb.php';

$session_id = $_GET['session'] ?? null;
if (!$session_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch session and trip details
$sql = "SELECT ts.*, t.routename, b.platenumber, s.sacconame, fs.stagename as from_stage, ts.stagename as to_stage, st.seatnumber
        FROM tripsessions ts
        JOIN trips tr ON ts.tripid = tr.tripid
        JOIN routes r ON tr.routeid = r.routeid
        JOIN buses b ON tr.busid = b.busid
        JOIN saccos s ON b.saccoid = s.saccoid
        JOIN stages fs ON ts.fromstageid = fs.stageid
        JOIN stages ts ON ts.tostageid = ts.stageid
        LEFT JOIN seats st ON ts.seatid = st.seatid
        WHERE ts.sessionid = ? AND ts.userid = ?";

// Corrected query with proper aliases
$sql = "SELECT ts.*, r.routename, b.platenumber, s.sacconame, 
               f_stg.stagename as from_stage_name, t_stg.stagename as to_stage_name, 
               st.seatnumber
        FROM tripsessions ts
        JOIN trips tr ON ts.tripid = tr.tripid
        JOIN routes r ON tr.routeid = r.routeid
        JOIN buses b ON tr.busid = b.busid
        JOIN saccos s ON b.saccoid = s.saccoid
        JOIN stages f_stg ON ts.fromstageid = f_stg.stageid
        JOIN stages t_stg ON ts.tostageid = t_stg.stageid
        LEFT JOIN seats st ON ts.seatid = st.seatid
        WHERE ts.sessionid = ? AND ts.userid = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $session_id, $_SESSION['user_id']);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiate Payment | SafiriPay Premium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="darkmode.css">
    <script src="darkmode.js"></script>
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #064e3b;
            --glass: rgba(255, 255, 255, 0.7);
            --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfeff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .payment-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header { margin-bottom: 2rem; text-align: center; }
        .header h1 { font-weight: 800; color: var(--primary-dark); margin: 0; }
        .header p { color: var(--primary); font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

        .summary-box {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .summary-item .label { color: #64748b; }
        .summary-item .value { font-weight: 600; color: #1e293b; }

        .total-row {
            border-top: 1px dashed #cbd5e1;
            padding-top: 1rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-amount { font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); }

        .pay-btn {
            background: linear-gradient(135deg, #059669 0%, #064e3b 100%);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.2);
        }

        .pay-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(5, 150, 105, 0.3);
        }

        .mpesa-logo {
             height: 24px;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="header">
            <p>Trip Summary & Checkout</p>
            <h1>Confirm Payment</h1>
        </div>

        <div class="summary-box">
            <div class="summary-item">
                <span class="label">Route</span>
                <span class="value"><?php echo htmlspecialchars($data['routename']); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">From - To</span>
                <span class="value"><?php echo htmlspecialchars($data['from_stage_name']); ?> → <?php echo htmlspecialchars($data['to_stage_name']); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Bus & SACCO</span>
                <span class="value"><?php echo htmlspecialchars($data['platenumber'] . ' (' . $data['sacconame'] . ')'); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Seat Number</span>
                <span class="value"><?php echo $data['seatnumber'] ? htmlspecialchars($data['seatnumber']) : '<span style="color:var(--primary)">Open Seating</span>'; ?></span>
            </div>
            
            <div class="total-row">
                <span class="label">Total Amount</span>
                <span class="total-amount">Ksh <?php echo number_format($data['fareamount'], 2); ?></span>
            </div>
        </div>

        <button class="pay-btn" onclick="alert('Initiating M-Pesa STK Push... Check your phone!')">
            <i class="fas fa-mobile-alt"></i> Pay with M-Pesa
        </button>
        
        <p style="text-align: center; color: #64748b; font-size: 0.8rem; margin-top: 1.5rem;">
            <i class="fas fa-shield-alt"></i> Secure payment powered by SafiriPay
        </p>
    </div>
</body>
</html>
