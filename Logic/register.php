<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error']);
        exit();
    }

    // Check if username already exists
    $checkSql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Username taken
        echo json_encode(['status' => 'taken']);
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertSql = "INSERT INTO users (username, password, usertype) VALUES (?, ?, 'player')";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ss", $username, $hashedPassword);
    if ($insertStmt->execute()) {
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
