<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
include 'database.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    $phone = trim($phone);
    $password = $_POST['password']; 

    $sql = "SELECT * FROM users WHERE phoneno = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error.");
    }
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['hashedpassword'])) {
            $_SESSION['user_id'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            $conn->close();
            echo "<script>alert('Login successful! Welcome, " . htmlspecialchars($_SESSION['username']) . "'); window.location.href = 'dashboard.php';</script>";
            // header("Location: dashboard.php");
            // exit();
        } else {
            echo "<script>alert('Incorrect password.');</script>";
        }
    } else {
        echo "<script>alert('No user found with that phone number..$phone ');</script>";
    }
    
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SafiriPay | Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="login.css">
  
</head>
<body>
  <div class="login-container">
    <div class="brand">
      <h1>Safiri<span>Pay</span></h1>
      <p>Smart Public Transport Payments</p>
    </div>

    <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'])?>" method="POST">
      <div class="input-group">
        <label>Phone Number</label>
        <input name="phone" type="text" id="phone" placeholder="07XXXXXXXX" required>
      </div>

      <div class="input-group">
        <label>Password</label>
        <input name="password" type="password" id="password" placeholder="••••••••" required>
      </div>

      <button type="submit">Login</button>

      <p class="register">
        Don’t have an account? <a href="register.php">Register</a>
      </p>
    </form>
  </div>
</body>
</html>
