<?php
// start.php

session_start();

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

// Make sure the user is logged in
if (!isset($_SESSION['player_name'])) {
    die("Error: No user logged in.");
}

$username = $conn->real_escape_string($_SESSION['player_name']);

// Generate a unique 5-digit session number
function generateUniqueSession($conn) {
    do {
        $session = rand(10000, 99999);
        $check = $conn->query("SELECT id FROM games WHERE session = '$session'");
    } while ($check->num_rows > 0);
    return $session;
}

// Get player ID
$sqlUser = "SELECT id FROM users WHERE username = '$username'";
$resultUser = $conn->query($sqlUser);

if ($resultUser->num_rows !== 1) {
    die("Error: User not found.");
}

$rowUser = $resultUser->fetch_assoc();
$player_id = $rowUser['id'];
$_SESSION['player_id'] = $player_id; // store player_id

// Start singleplayer game session
$sessionNumber = generateUniqueSession($conn);
$sqlGame = "INSERT INTO games (player_id, gametype, session, sessionstatus, start_time, end_time)
            VALUES ('$player_id', 'singleplayer', $sessionNumber, 'active', NOW(), NULL)";

if ($conn->query($sqlGame) === TRUE) {
    $_SESSION['session_id'] = $sessionNumber;
    header("Location: ../Pages/House.html"); // first scene
    exit();
} else {
    die("Error creating game session: " . $conn->error);
}

$conn->close();
?>
