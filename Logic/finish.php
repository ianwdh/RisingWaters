<?php
// finish.php

session_start();

// Check if player session exists
if (!isset($_SESSION['player_id']) || !isset($_SESSION['session_id'])) {
    die("No active player session.");
}

// Database connection
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
$session_id = $_SESSION['session_id'];

// Update game session to finished and record end time
$sql = "UPDATE games 
        SET sessionstatus = 'finished', end_time = NOW() 
        WHERE player_id = ? AND session = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $player_id, $session_id);

if ($stmt->execute()) {
    // Optionally, destroy session if game fully done
    // session_destroy();

    // Redirect to leaderboard page
    header("Location: ../Logic/leaderboard.php");
    exit();
} else {
    echo "Error updating game session: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
