<?php
$conn = new mysqli('localhost', 'root', '', 'publictransportdatabase');
if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}
?>