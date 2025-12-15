<?php
session_start();
header('Content-Type: application/json');

// Check active session
if (!isset($_SESSION['player_id']) || !isset($_SESSION['session_id'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No active game session']);
    exit;
}

$playerId = $_SESSION['player_id'];
$sessionId = $_SESSION['session_id'];

// Validate action
if (!isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Action not specified']);
    exit;
}

$action = $_POST['action'];
$scoreChange = match($action) {
    'success' => 100,
    'retry' => -50,
    default => null
};

if ($scoreChange === null) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Invalid action']);
    exit;
}

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'DB connection failed']);
    exit;
}

// Ensure game exists
$checkStmt = $conn->prepare("SELECT * FROM games WHERE player_id=? AND session=? AND sessionstatus='active'");
$checkStmt->bind_param("is", $playerId, $sessionId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status'=>'error','message'=>'No active game found']);
    exit;
}
$checkStmt->close();

// Update score
$updateStmt = $conn->prepare("UPDATE games SET score = COALESCE(score,0) + ? WHERE player_id=? AND session=? AND sessionstatus='active'");
$updateStmt->bind_param("iis", $scoreChange, $playerId, $sessionId);
$updateStmt->execute();

// Fetch updated score
$selectStmt = $conn->prepare("SELECT COALESCE(score,0) AS score FROM games WHERE player_id=? AND session=? AND sessionstatus='active'");
$selectStmt->bind_param("is", $playerId, $sessionId);
$selectStmt->execute();
$res = $selectStmt->get_result();
$row = $res->fetch_assoc();

echo json_encode(['status'=>'success','new_score'=>(int)$row['score']]);

$updateStmt->close();
$selectStmt->close();
$conn->close();
?>
