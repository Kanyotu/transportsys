<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// In a real app, you might validate the QR data here
// For now, we'll just return success to allow the flow to continue
$sacco_id = $_POST['sacco_id'] ?? '';
$bus_id = $_POST['bus_id'] ?? '';
$trip_id = $_POST['trip_id'] ?? '';

if (empty($sacco_id) || empty($bus_id) || empty($trip_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing QR data']);
    exit();
}

// Optionally verify if trip exists
$sql = "SELECT tripid FROM trips WHERE tripid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Trip ID']);
}

$stmt->close();
$conn->close();
?>
