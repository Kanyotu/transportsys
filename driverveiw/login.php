<?php
session_start();
include 'database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneno = mysqli_real_escape_string($conn, $_POST['phoneno']);
    $password = $_POST['password'];

    // Check drivers table. Assuming phone number is used for identification.
    $sql = "SELECT * FROM drivers WHERE phoneno = '$phoneno'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        // Verify password if hashedpassword column exists and is populated
        if (isset($row['hashedpassword']) && $row['hashedpassword']) {
            if (password_verify($password, $row['hashedpassword'])) {
                $_SESSION['driver_id'] = $row['driverid'];
                $_SESSION['driver_name'] = $row['dname'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            // If password is not set yet (initially), maybe allow first-time setup or error
            $error = "Account not fully set up. Please contact management.";
        }
    } else {
        $error = "Driver account not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Login | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="driver.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, var(--sidebar-bg), var(--bg-main));
            padding: 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 3.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--glass-border);
            text-align: center;
            position: relative;
        }
        .login-header i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 10px rgba(249, 115, 22, 0.3));
        }
        .login-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .login-header p {
            color: var(--text-muted);
            margin-bottom: 3rem;
            font-size: 1rem;
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            font-size: 0.9rem;
            font-weight: 700;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .top-actions {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="top-actions">
            <button class="theme-toggle" id="theme-toggle"><i class="fas fa-moon"></i></button>
        </div>
        <div class="login-header">
            <i class="fas fa-id-card"></i>
            <h1>Driver Portal</h1>
            <p>Ready for your next trip? Login to proceed.</p>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group" style="text-align: left;">
                <label>Phone Number</label>
                <div style="position: relative;">
                    <i class="fas fa-phone" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="phoneno" placeholder="0712345678" required style="padding-left: 3.5rem;">
                </div>
            </div>
            <div class="input-group" style="text-align: left;">
                <label>Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="password" name="password" placeholder="••••••••" required style="padding-left: 3.5rem;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">Sign In to Portal</button>
        </form>
        
        <div style="margin-top: 3rem; font-size: 0.875rem; color: var(--text-muted); font-weight: 500;">
            &copy; <?php echo date('Y'); ?> SafiriPay Systems. Keep moving forward!
        </div>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
