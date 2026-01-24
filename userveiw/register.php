<?php
include 'database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Full name
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);

    // Phone
    $phone = isset($_POST['phoneno']) ? trim($_POST['phoneno']) : '';
    $phone = preg_replace('/\D/', '', $phone);

   if (!ctype_digit($phone) || strlen($phone) != 10) {
        echo "Invalid phone number";
        exit();
    }

    // Email (optional)
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit();
    }

    // Password
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check duplicate phone
    $query = "SELECT * FROM users WHERE phoneno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();  
    if ($stmt->num_rows > 0) {
        die("Phone number already registered.");
    }

    // Insert user
    $sql = "INSERT INTO users (username, phoneno, email, hashedpassword) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fullname, $phone, $email, $passwordHash);
    if ($stmt->execute()) { 
        $conn->close();
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SafiriPay | Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="login.css">
</head>
<body>

  <div class="login-container">
    <div class="brand">
      <h1>Safiri<span>Pay</span></h1>
      <p>Create your account</p>
    </div>

    <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>" method="POST">

      <div class="input-group">
        <label>Full Name</label>
        <input name="fullname" type="text" id="fullname" placeholder="John Doe" required>
      </div>

      <div class="input-group">
        <label>Phone Number</label>
        <input name="phoneno" type="text" id="phone" placeholder="07XXXXXXXX" required>
      </div>

      <div class="input-group">
        <label>Email (Optional)</label>
        <input name="email" type="email" id="email" placeholder="example@email.com">
      </div>

      <div class="input-group">
        <label>Password</label>
        <input name="password" type="password" id="password" placeholder="••••••••" required>
      </div>

      <div class="input-group">
        <label>Confirm Password</label>
        <input name="confirmPassword" type="password" id="confirmPassword" placeholder="••••••••" required>
      </div>

      <button type="submit">Register</button>

      <p class="register">
        Already have an account? <a href="login.php">Login</a>
      </p>

    </form>
  </div>

</body>
</html>
