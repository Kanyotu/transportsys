<?php
session_start();
include'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $monthlybudget = $_POST['monthlybudget'];
    $userid = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE users SET email=?, monthlybudget=? WHERE userid=?");
    $stmt->bind_param("ssi", $email, $monthlybudget, $userid);
    if ($stmt->execute()) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile.";
    }
}