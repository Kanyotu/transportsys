<?php
include 'header.php';
include 'checkinguserindb.php';
include 'database.php';
$username = $_SESSION['username'];
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("SELECT COUNT(*) FROM trips WHERE userid = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_trips);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) 
    FROM payments 
    WHERE userid = ? 
    AND MONTH(createdat) = MONTH(CURRENT_DATE())
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($monthly_spent);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) FROM trips 
    WHERE userid = ? AND status = 'booked'
");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($active_bookings);
$stmt->fetch();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SafiriPay | Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>

  <main class="container">

    <section class="welcome-card">
      <h2>Welcome back <?php echo htmlspecialchars($_SESSION["username"])?> 👋</h2>
      <p>Manage your transport payments and track your trips easily.</p>
    </section>

    <section class="stats">
      <div class="stat-card">
        <h3>Total Trips</h3>
        <p><?php echo $total_trips ?></p>
      </div>

      <div class="stat-card">
        <h3>Spent This Month</h3>
        <p><?php echo number_format($monthly_spent) ?></p>
      </div>

      <div class="stat-card">
        <h3>Active Bookings</h3>
        <p><?php echo $active_bookings ?></p>
      </div>
    </section>

    <section class="actions">
      <a href="#" class="action-btn">🚍 Book a Trip</a>
      <a href="#" class="action-btn">📊 View Usage</a>
      <a href="#" class="action-btn">💳 Payment History</a>
      <a href="profile.php" class="action-btn">👤 Profile</a>
    </section>

  </main>

</body>
</html>
