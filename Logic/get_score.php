<?php
session_start();
header('Content-Type: application/json');

// Ensure player and active session exist
if (!isset($_SESSION['player_id']) || !isset($_SESSION['session_id'])) {
    echo json_encode(['score' => 0]);
    exit;
}

$playerId = $_SESSION['player_id'];
$sessionId = $_SESSION['session_id'];

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['score' => 0]);
    exit;
}

// Get current score for this active session
$stmt = $conn->prepare("SELECT COALESCE(score,0) AS score FROM games WHERE player_id=? AND session=? LIMIT 1");
$stmt->bind_param("ii", $playerId, $sessionId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['score' => (int)$row['score']]);

$stmt->close();
$conn->close();
?>
