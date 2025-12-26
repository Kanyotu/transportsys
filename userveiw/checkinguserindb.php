<?php
include 'database.php';
$userid = $_SESSION['user_id'];
if (!$userid) {
    header("Location: login.php");
    exit();
}
$sql = "SELECT * FROM users WHERE userid = ?";
$stmt = $conn->prepare($sql);  
if (!$stmt) {
    echo "Database error.".$conn->error;
    exit();
} 
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
if (!$result->num_rows === 1) {
    $conn->close();
    header("Location: login.php");
    exit();
}