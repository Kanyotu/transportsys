<?php
include 'adminveiw/database.php';

// Phase 2 Database Updates
$queries = [
    "ALTER TABLE tripsessions ADD COLUMN boarding_status ENUM('pending', 'boarded') DEFAULT 'pending' AFTER status",
    "CREATE TABLE IF NOT EXISTS trip_issues (
        issueid INT AUTO_INCREMENT PRIMARY KEY,
        tripid INT NOT NULL,
        driverid INT NOT NULL,
        description TEXT NOT NULL,
        status ENUM('reported', 'resolved') DEFAULT 'reported',
        createdat DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tripid) REFERENCES trips(tripid),
        FOREIGN KEY (driverid) REFERENCES drivers(driverid)
    )"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "Successfully executed: $query<br>";
    } else {
        echo "Error executing query ($query): " . $conn->error . "<br>";
    }
}

$conn->close();
?>
