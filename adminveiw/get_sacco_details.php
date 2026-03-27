<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
include 'database.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch SACCO basics
    $stmt = $conn->prepare("SELECT * FROM saccos WHERE saccoid = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $sacco = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sacco) {
        echo json_encode(['error' => 'SACCO not found']);
        exit();
    }

    // Fetch Manager
    $stmt = $conn->prepare("SELECT username, phoneno, email FROM users WHERE saccoid = ? AND type = 'sacco' LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $manager = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Stats
    $res = $conn->query("SELECT COUNT(*) as count FROM buses WHERE saccoid = $id");
    $bus_count = $res->fetch_assoc()['count'];

    $res = $conn->query("SELECT COUNT(*) as count FROM routes WHERE saccoid = $id");
    $route_count = $res->fetch_assoc()['count'];

    echo json_encode([
        'sacco' => $sacco,
        'manager' => $manager ?: ['username' => 'N/A', 'phoneno' => 'N/A', 'email' => 'N/A'],
        'stats' => [
            'buses' => $bus_count,
            'routes' => $route_count
        ]
    ]);
} else {
    echo json_encode(['error' => 'No ID provided']);
}
?>
