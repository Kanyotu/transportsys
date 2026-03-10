<?php
session_start();
include 'database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneno = mysqli_real_escape_string($conn, $_POST['phoneno']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE phoneno = '$phoneno' AND type = 'admin'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['hashedpassword'])) {
            $_SESSION['admin_id'] = $row['userid'];
            $_SESSION['admin_username'] = $row['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Admin account not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SafiriPay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, var(--sidebar-bg), var(--bg-main));
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 3rem;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--glass-border);
            text-align: center;
        }
        .login-header i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        .login-header h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }
        .login-header p {
            color: var(--text-muted);
            margin-bottom: 2.5rem;
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-bus-alt"></i>
            <h1>SafiriPay Admin</h1>
            <p>Enter your credentials to access the portal</p>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group" style="text-align: left;">
                <label>Phone Number</label>
                <div style="position: relative;">
                    <i class="fas fa-phone" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="phoneno" placeholder="0712345678" required style="padding-left: 3rem;">
                </div>
            </div>
            <div class="input-group" style="text-align: left;">
                <label>Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="password" name="password" placeholder="••••••••" required style="padding-left: 3rem;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Sign In</button>
        </form>
        
        <div style="margin-top: 2rem; font-size: 0.875rem; color: var(--text-muted);">
            &copy; <?php echo date('Y'); ?> SafiriPay Systems. All rights reserved.
        </div>
    </div>
</body>
</html>
