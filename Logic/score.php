<?php
// score.php
session_start();

// Make sure the player is logged in or has a name in session
if (!isset($_SESSION['player_name'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Player not set in session']);
    exit;
}

$playerName = $_SESSION['player_name'];

// Check the action type from POST or GET
if (!isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Action not specified']);
    exit;
}

$action = $_POST['action']; // expected values: 'success' or 'retry'

// Connect to DB
$host = "localhost";
$user = "root";
$pass = "";
$db   = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

// Determine score change
$scoreChange = 0;
if ($action === 'success') {
    $scoreChange = 100;
} elseif ($action === 'retry') {
    $scoreChange = -50;
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Update the player's score
$sql = "UPDATE players SET score = score + ? WHERE name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $scoreChange, $playerName);

if ($stmt->execute()) {
    // Return new score
    $result = $conn->query("SELECT score FROM players WHERE name = '$playerName'");
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'new_score' => $row['score']]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update score']);
}

$stmt->close();
$conn->close();
?>
