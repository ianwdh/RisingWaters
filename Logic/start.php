<?php
// start.php

session_start();

// Database connection (adjust with your own credentials)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rising_waters"; // your database name

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get player name from form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        // Escape to prevent SQL injection
        $name = $conn->real_escape_string($name);

        // Insert player into database
        $sql = "INSERT INTO players (name, status, start_time) VALUES ('$name', 'unfinished', NOW())";

        if ($conn->query($sql) === TRUE) {
            // Save player ID in session
            $_SESSION['player_id'] = $conn->insert_id;
            $_SESSION['player_name'] = $name;

            // Redirect to game page (replace with your actual game file)
            header("Location: ../Pages/House.html");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Please enter a valid name.";
    }
}

$conn->close();
?>