<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $currentUser = $_SESSION['player_name'] ?? '';
    $newUsername = $_POST["username"] ?? '';
    $newPassword = $_POST["password"] ?? '';

    if (empty($currentUser) || empty($newUsername) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit();
    }

    // Check if new username is taken (exclude current user)
    $checkSql = "SELECT id FROM users WHERE username = ? AND username != ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ss", $newUsername, $currentUser);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'taken', 'message' => 'Username is taken']);
        exit();
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update username and password
    $updateSql = "UPDATE users SET username = ?, password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sss", $newUsername, $hashedPassword, $currentUser);

    if ($stmt->execute()) {
        $_SESSION['player_name'] = $newUsername; // update session
        echo json_encode(['status' => 'ok', 'message' => 'Edited successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
    }
}
?>
