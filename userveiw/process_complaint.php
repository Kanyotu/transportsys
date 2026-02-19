<?php
// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: submit_complaint.php");
    exit();
}

// Get and validate form data
$type = $_POST['type'] ?? '';
$description = trim($_POST['description'] ?? '');

// Validate complaint type
if (!in_array($type, ['car', 'driver'])) {
    $_SESSION['complaint_error'] = "Invalid complaint type selected.";
    header("Location: submit_complaint.php");
    exit();
}

// Validate description
if (empty($description)) {
    $_SESSION['complaint_error'] = "Please provide a description of your complaint.";
    header("Location: submit_complaint.php");
    exit();
}

if (strlen($description) < 10) {
    $_SESSION['complaint_error'] = "Description must be at least 10 characters long.";
    header("Location: submit_complaint.php");
    exit();
}

if (strlen($description) > 500) {
    $_SESSION['complaint_error'] = "Description must not exceed 500 characters.";
    header("Location: submit_complaint.php");
    exit();
}

// Sanitize description (prevent XSS)
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

// Insert complaint into database
$sql = "INSERT INTO complaint (userid, type, description, date) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("iss", $user_id, $type, $description);

if ($stmt->execute()) {
    // Get the inserted complaint ID
    $complaint_id = $stmt->insert_id;
    
    // Store success message in session
    $_SESSION['complaint_success'] = true;
    $_SESSION['complaint_id'] = $complaint_id;
    $_SESSION['complaint_type'] = $type;
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
    
    // Redirect to success page
    header("Location: complaint_success.php");
    exit();
    
} else {
    // Handle database error
    $_SESSION['complaint_error'] = "Failed to submit complaint. Please try again.";
    header("Location: submit_complaint.php");
    exit();
}
?>