<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Fetch user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {

            // --- Set PHP session variables ---
            $_SESSION['player_id'] = $row['id'];
            $_SESSION['player_name'] = $row['username'];
            $_SESSION['session_id'] = uniqid(); // unique game session ID

            // --- Insert a new game session ---
            $insertGame = $conn->prepare("INSERT INTO games (player_id, session, score, sessionstatus) VALUES (?, ?, 0, 'active')");
            $insertGame->bind_param("is", $_SESSION['player_id'], $_SESSION['session_id']);
            $insertGame->execute();
            $insertGame->close();

            // --- Return info to JS ---
            echo json_encode([
                "status" => "ok",
                "username" => $row['username'],
                "player_id" => $row['id']
            ]);
            exit();
        }
    }

    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password"
    ]);
}
?>
