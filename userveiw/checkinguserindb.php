<?php
session_start();
include 'database.php';
$userid = $_SESSION['user_id'];
if (!$userid) {
    header("Location: login.php");
    exit();
}
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);   
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
if (!$result->num_rows === 1) {
    $conn->close();
    header("Location: login.php");
    exit();
}