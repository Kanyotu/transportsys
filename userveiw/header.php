<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SafiriPay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="header.css">
  <link rel="icon" href="car.jpeg" type="image/jpeg">
</head>

<body>
<header class="app-header">
  <div class="logo">
    Safiri<span>Pay</span>
  </div>

  <nav class="nav-links">
    <a href="dashboard.php">Dashboard</a>
    <a href="trips.php">Trips</a>

    
    <a href="profile.php">Profile</a>
  </nav>

  <div class="user-actions">
    <span class="user-name">
      <?php echo $_SESSION['username'] ?? 'User'; ?>
    </span>
    <a href="logout.php" class="logout">Logout</a>
  </div>
</header>
