<?php

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
    <a href="book.php">book now</a>
    <a href="budget_setting.php">budget setting</a>
    <a href="spending.php">spending</a>

    
    <a href="profile.php">Profile</a>
  </nav>

  <div class="user-actions">
    <span class="user-name">
      <?php echo ucfirst($_SESSION['username']) ?>
    </span>
    <a href="logout.php" class="logout">Logout</a>
  </div>
</header>
