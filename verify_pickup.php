<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'driverveiw/database.php';

$trip_id = 4; // Long distance trip (Murang'a - Nairobi, Route 3)
$test_user_id = 3; // Verified existing user

// Cleanup first
$conn->query("DELETE FROM tripsessions WHERE tripid = $trip_id AND userid = $test_user_id AND fareamount = 155.00");

// Route 3 stages: Maragua (9), Mukuyu (10), Kenol (11), Sabasaba (12)
$from_stage = 11; // Kenol (Route 3)
$to_stage = 9;   // Maragua (Route 3)

// 2. Insert a test booking
$sql_insert = "INSERT INTO tripsessions (userid, tripid, saccoid, busid, fromstageid, tostageid, fareamount, status)
              VALUES ($test_user_id, $trip_id, 2, 4, $from_stage, $to_stage, 155.00, 'paid')";
if (!$conn->query($sql_insert)) {
    die("Insert failed: " . $conn->error);
}
echo "Inserted test booking for User $test_user_id on Trip $trip_id.\n";

// 3. Run the pickup route SQL
$sql = "
    SELECT s.stageid, s.stagename, s.stageorder,
           (SELECT COUNT(*) FROM tripsessions ts WHERE ts.tripid = $trip_id AND ts.fromstageid = s.stageid AND ts.status = 'paid') as pickups,
           (SELECT COUNT(*) FROM tripsessions ts WHERE ts.tripid = $trip_id AND ts.tostageid = s.stageid AND ts.status = 'paid') as dropoffs
    FROM stages s
    WHERE s.routeid = (SELECT routeid FROM trips WHERE tripid = $trip_id)
    ORDER BY s.stageorder ASC
";

$res = $conn->query($sql);
if (!$res) {
    die("Query failed: " . $conn->error);
}

$found_pickup = false;
$found_dropoff = false;
echo "--- Route Plan Output ---\n";
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['stageid']} | Stage: {$row['stagename']} | P: {$row['pickups']} | D: {$row['dropoffs']}\n";
    if ($row['stageid'] == $from_stage && $row['pickups'] > 0) $found_pickup = true;
    if ($row['stageid'] == $to_stage && $row['dropoffs'] > 0) $found_dropoff = true;
}

if ($found_pickup && $found_dropoff) {
    echo "SUCCESS: Pickup and Dropoff verified.\n";
} else {
    echo "FAILURE: Pickup($found_pickup) or Dropoff($found_dropoff) mismatch.\n";
}

// Cleanup
$conn->query("DELETE FROM tripsessions WHERE tripid = $trip_id AND userid = $test_user_id AND fareamount = 155.00");
?>
