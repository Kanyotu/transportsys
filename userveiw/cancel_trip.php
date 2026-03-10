<?php
session_start();
include 'database.php';
include 'checkinguserindb.php';

$session_id = $_GET['session'] ?? null;
if (!$session_id) {
    header("Location: trips.php");
    exit();
}

// 1. Fetch the trip session to check status and seat
$sql = "SELECT userid, seatid, status FROM tripsessions WHERE sessionid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session || $session['userid'] != $_SESSION['user_id'] || $session['status'] != 'pending') {
    header("Location: trips.php?error=cannot_cancel");
    exit();
}

// 2. Start transaction
$conn->begin_transaction();

try {
    // 3. Update session status to cancelled
    $update_sql = "UPDATE tripsessions SET status = 'cancelled' WHERE sessionid = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $session_id);
    $update_stmt->execute();

    // 4. Release seat if assigned
    if ($session['seatid']) {
        $release_sql = "UPDATE seats SET status = 'available' WHERE seatid = ?";
        $release_stmt = $conn->prepare($release_sql);
        $release_stmt->bind_param("i", $session['seatid']);
        $release_stmt->execute();
    }

    $conn->commit();
    header("Location: trips.php?cancelled=success");
} catch (Exception $e) {
    $conn->rollback();
    header("Location: trips.php?error=db_error");
}
exit();
?>
