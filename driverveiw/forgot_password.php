<?php
session_start();
include 'database.php';

$error = "";
$success = "";
$step = 1; // 1: Verify Identity, 2: Reset Password
$driver_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify'])) {
        $phoneno = mysqli_real_escape_string($conn, $_POST['phoneno']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        $sql = "SELECT driverid FROM drivers WHERE phoneno = '$phoneno' AND email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['reset_driver_id'] = $row['driverid'];
            $step = 2;
        } else {
            $error = "Identity verification failed. Please check your phone and email.";
        }
    } elseif (isset($_POST['reset'])) {
        if (!isset($_SESSION['reset_driver_id'])) {
            header("Location: forgot_password.php");
            exit();
        }
        
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
            $step = 2;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $driver_id = $_SESSION['reset_driver_id'];
            
            $stmt = $conn->prepare("UPDATE drivers SET hashedpassword = ? WHERE driverid = ?");
            $stmt->bind_param("si", $hashed_password, $driver_id);
            
            if ($stmt->execute()) {
                $success = "Password reset successfully! You can now login.";
                unset($_SESSION['reset_driver_id']);
                $step = 3; // Success state
            } else {
                $error = "Error updating password. Please try again.";
                $step = 2;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Driver Portal</title>
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
        }
        .login-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            color: var(--text-main);
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
        }
        .success-msg {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            font-size: 0.9rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-key"></i>
            <h1>Reset Password</h1>
            <?php if ($step == 1): ?>
                <p>Verify your identity to reset your account password.</p>
            <?php elseif ($step == 2): ?>
                <p>Enter your new password below.</p>
            <?php else: ?>
                <p>Account recovered! You're ready to hit the road.</p>
            <?php endif; ?>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
            <a href="login.php" class="btn btn-primary" style="width: 100%; text-decoration: none; display: inline-block; padding: 1rem; box-sizing: border-box;">Go to Login</a>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="POST">
                <div class="input-group" style="text-align: left;">
                    <label>Phone Number</label>
                    <div style="position: relative;">
                        <i class="fas fa-phone" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="phoneno" placeholder="07XXXXXXXX" required style="padding-left: 3.5rem;">
                    </div>
                </div>
                <div class="input-group" style="text-align: left;">
                    <label>Email Address</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="email" name="email" placeholder="driver@example.com" required style="padding-left: 3.5rem;">
                    </div>
                </div>
                <button type="submit" name="verify" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">Verify Identity</button>
                <div style="margin-top: 1.5rem;">
                    <a href="login.php" style="color: var(--text-muted); font-size: 0.875rem; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Back to Login
                    </a>
                </div>
            </form>
        <?php elseif ($step == 2): ?>
            <form method="POST">
                <div class="input-group" style="text-align: left;">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="password" name="password" placeholder="••••••••" required style="padding-left: 3.5rem;">
                    </div>
                </div>
                <div class="input-group" style="text-align: left;">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-check-circle" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="password" name="confirm_password" placeholder="••••••••" required style="padding-left: 3.5rem;">
                    </div>
                </div>
                <button type="submit" name="reset" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">Update Password</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 3rem; font-size: 0.875rem; color: var(--text-muted); font-weight: 500;">
            &copy; <?php echo date('Y'); ?> SafiriPay Systems.
        </div>
    </div>
    <script src="darkmode.js"></script>
</body>
</html>
