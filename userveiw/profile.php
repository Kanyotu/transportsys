<?php

include 'header.php';
include 'checkinguserindb.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userid= $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);   
if (!$stmt) {
    echo "Database error.".$conn->error;
    exit();
}
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

} else {
    echo "User not found.";
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM trips WHERE userid = ?");  
if (!$stmt) {
    echo "Database error.".$conn->error;
    exit();
}

$stmt->bind_param("i", $userid);
$stmt->execute();
$stmt->bind_result($total_trips);
$stmt->fetch();
$stmt->close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SafiriPay | Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="profile.css">

</head>
<body>


  <main class="container">

    <!-- PROFILE CARD -->
    <section class="profile-card">
      <div class="avatar">
        <span>👤</span>
      </div>

      <h2><?php echo $_SESSION['username']?></h2>
      <p class="phone">📞 <?php echo htmlspecialchars($user['phoneno'])?></p>

      <div class="status verified">
        ✔ Verified Passenger
      </div>
    </section>

    <!-- PROFILE DETAILS -->
    <section class="details-card">
      <h3>Account Information</h3>
    

      <div class="detail">
        <span>Registered On</span>
        <p><?php echo htmlspecialchars($user['datejoined'])?></p>
      </div>

      <div class="detail">
        <span>Total Trips</span>
        <p><?php echo htmlspecialchars($total_trips)?></p>
      </div>
       <?php 
     if (!$user['email']&& empty($user['email'])&& is_null($user['monthlybudget'])) {
        echo "

        <div class=\"detail\"> 
        <form class=\"settings-form\" action=\"updateprofile.php\" method=\"POST\">
        <span>Email</span><br>
        <input type=\"email\" placeholder=\"Add your email address\" name=\"email\"/><br>
        <span>Monthly Budget</span><br>
        <input type=\"number\" name=\"monthlybudget\" placeholder=\"Set a monthly budget\"/><br><br>
        <input type=\"submit\" value=\"Edit profile\" class=\"edit-btn\"/>
        </form>
        </div>

        ";
      }else{
        echo "
        <div class=\"detail\">
        <span>Email</span>
        <p>".htmlspecialchars($user['email'])."</p>
        </div>

        <div class=\"detail\">
        <span>Monthly Budget</span>
        <p>".htmlspecialchars(number_format($user['monthlybudget']))."</p>
        </div>
        ";
      }
      ?>

      <!-- <a href="#" class="edit-btn">Edit Profile</a> -->
    </section>

    <!-- SAFETY SECTION -->
    <section class="safety-card">
      <h3>Safety & Trust</h3>

      <ul>
        <li>✔ Phone number verified</li>
        <li>✔ Cashless payment enabled</li>
        <li>✔ Trip history recorded</li>
      </ul>

      <button class="panic-btn">🚨 Emergency Help</button>
    </section>



  </main>

</body>
</html>
