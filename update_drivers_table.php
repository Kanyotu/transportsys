<?php
include 'adminveiw/database.php';

// Add email and hashedpassword columns to drivers table
$queries = [
    "ALTER TABLE drivers ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER dname",
    "ALTER TABLE drivers ADD COLUMN hashedpassword VARCHAR(255) DEFAULT NULL AFTER phoneno",
    "ALTER TABLE drivers ADD COLUMN saccoid INT(11) DEFAULT NULL AFTER hashedpassword",
    "ALTER TABLE drivers ADD CONSTRAINT fk_driver_sacco FOREIGN KEY (saccoid) REFERENCES saccos(saccoid)"
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
