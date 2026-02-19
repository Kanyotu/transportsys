<?php
session_start();
include 'checkinguserindb.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT email, monthlybudget,username FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission if needed
    $monthlybudget = $_POST['monthlybudget'] ;
    $email = $_POST['email'] ;

    $sql = "UPDATE users SET email = ?, monthlybudget = ? WHERE userid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdis", $email, $monthlybudget, $username, $user_id);
    if ($stmt->execute()) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile | SafiriPay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="editprofile.css">
</head>
<body>

<div class="edit-container">
  <h2>Edit Profile</h2>

  <form action="updateprofile.php" method="POST">

    <div class="input-group">
      <label>Email Address</label>
      <input 
        type="email" 
        name="email" 
        placeholder="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
        >
    </div>

    <div class="input-group">
      <label>Monthly Budget (KES)</label>
      <input 
        type="number" 
        name="monthlybudget" 
        placeholder="<?php echo htmlspecialchars($user['monthlybudget'] ?? ''); ?>"
        >
    </div>
    <button type="submit" class="save-btn">
      💾 Save Changes
    </button>

    <a href="profile.php" class="cancel-link">Cancel</a>

  </form>
</div>

</body>
</html>

