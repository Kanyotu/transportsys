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
$platenumber = trim($_POST['platenumber'] ?? '');
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

// Sanitize description and plate number
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
$platenumber = htmlspecialchars($platenumber, ENT_QUOTES, 'UTF-8');

// Debug logging (temporary)
$debug_file = 'complaint_debug.log';
file_put_contents($debug_file, "Attempting insert: User $user_id, Type $type, Plate $platenumber\n", FILE_APPEND);

// Insert complaint into database (let date and status use defaults)
$sql = "INSERT INTO complaint (userid, type, platenumber, description) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    file_put_contents($debug_file, "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    die("Database error: " . $conn->error);
}

$stmt->bind_param("isss", $user_id, $type, $platenumber, $description);

if ($stmt->execute()) {
    file_put_contents($debug_file, "Execute success. ID: " . $stmt->insert_id . "\n", FILE_APPEND);
    // Get the inserted complaint ID
    $complaint_id = $stmt->insert_id;
    
    // Store success message in session
/* ... existing session code ... */
    $_SESSION['complaint_success'] = true;
    $_SESSION['complaint_id'] = $complaint_id;
    $_SESSION['complaint_type'] = $type;
    
    $stmt->close();
    $conn->close();
    header("Location: complaint_success.php");
    exit();
    
} else {
    file_put_contents($debug_file, "Execute failed: " . $stmt->error . "\n", FILE_APPEND);
    // Handle database error
    $_SESSION['complaint_error'] = "Failed to submit complaint. Database error: " . $stmt->error;
    header("Location: submit_complaint.php");
    exit();
}
?>