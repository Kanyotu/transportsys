<?php
include 'database.php';

    $username = "kanyotu";
    $password = 123;
    $confirmpassword = 123;
    $email = "";
    $phone = 0700000000;
    $type = "admin"; // Updated from role to type

    if($password != $confirmpassword){
        echo "<script>alert('Passwords do not match');</script>";
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Updated query to use 'type' and 'hashedpassword' (from something.sql) and 'phoneno'
    $stmt = $conn->prepare("INSERT INTO users (username, hashedpassword, email, phoneno, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $email, $phone, $type);
    
    if(!$stmt->execute()) {
        echo "Error: " . $stmt->error;
    } else {
        echo "Admin added successfully";
    }