<?php
// finish.php

session_start();

// Check if player session exists
if (!isset($_SESSION['player_id'])) {
    die("No active player session.");
}

// Database connection (adjust with your credentials)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$player_id = $_SESSION['player_id'];

// Update player status to finished and record end time
$sql = "UPDATE players 
        SET status = 'finished', end_time = NOW() 
        WHERE id = $player_id";

if ($conn->query($sql) === TRUE) {
    // Optionally, you can destroy the session if game is fully done
    // session_destroy();

    // Redirect to leaderboard page
    header("Location: ../Logic/leaderboard.php");
    exit();
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>
