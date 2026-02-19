<?php
// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if this is a successful complaint submission
if (!isset($_SESSION['complaint_success']) || $_SESSION['complaint_success'] !== true) {
    header("Location: submit_complaint.php");
    exit();
}

$complaint_id = $_SESSION['complaint_id'] ?? 'N/A';
$complaint_type = $_SESSION['complaint_type'] ?? '';

// Clear the session variables
unset($_SESSION['complaint_success']);
unset($_SESSION['complaint_id']);
unset($_SESSION['complaint_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Submitted | SafiriPay</title>
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
            --success: #28a745;
        }

        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            max-width: 600px;
            margin: 2rem;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-card {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        /* Success Icon */
        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .success-icon i {
            font-size: 4rem;
            color: #28a745;
        }

        /* Content */
        h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .complaint-id {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            border: 2px dashed var(--secondary);
        }

        .complaint-id .label {
            font-size: 0.9rem;
            color: var(--gray);
            display: block;
            margin-bottom: 0.5rem;
        }

        .complaint-id .id-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            letter-spacing: 2px;
        }

        .message {
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .success-card {
                padding: 2rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <!-- Success Icon -->
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <!-- Content -->
            <h1>Thank You!</h1>
            <p class="message">
                Your complaint has been successfully submitted and will be reviewed by our team.
                We appreciate you taking the time to help us improve our services.
            </p>

            <!-- Complaint ID -->
            <div class="complaint-id">
                <span class="label">Complaint Reference Number</span>
                <span class="id-number">#<?php echo str_pad($complaint_id, 6, '0', STR_PAD_LEFT); ?></span>
                <span style="display: block; margin-top: 0.5rem; font-size: 0.85rem; color: var(--gray);">
                    Type: <?php echo ucfirst($complaint_type); ?> Complaint
                </span>
            </div>

            <!-- What happens next -->
            <div style="text-align: left; margin: 2rem 0; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
                <h3 style="color: var(--primary); margin-bottom: 1rem; font-size: 1rem;">
                    <i class="fas fa-clock"></i> What happens next?
                </h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        Our team reviews your complaint (24-48 hours)
                    </li>
                    <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        Investigation is conducted if necessary
                    </li>
                    <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        You'll receive updates on the resolution
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="my_complaints.php" class="btn btn-primary">
                    <i class="fas fa-list"></i>
                    View My Complaints
                </a>
                <a href="submit_complaint.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i>
                    Submit Another
                </a>
            </div>
        </div>
    </div>
</body>
</html>