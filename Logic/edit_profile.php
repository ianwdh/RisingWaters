<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$currentUser = $_SESSION['player_name'] ?? '';
$newUsername = $_POST['username'] ?? '';
$newPassword = $_POST['password'] ?? '';
$newProfilePicture = $_POST['profilepicture'] ?? '';

if (empty($currentUser)) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    exit();
}

/* ===============================
   USERNAME CHECK (if changed)
================================ */
if (!empty($newUsername) && $newUsername !== $currentUser) {

    $checkSql = "SELECT id FROM users WHERE username = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $newUsername);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['status' => 'taken', 'message' => 'Username is taken']);
        exit();
    }
}

/* ===============================
   BUILD UPDATE QUERY DYNAMICALLY
================================ */
$fields = [];
$params = [];
$types = "";

/* Username */
if (!empty($newUsername)) {
    $fields[] = "username = ?";
    $params[] = $newUsername;
    $types .= "s";
}

/* Password */
if (!empty($newPassword)) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $fields[] = "password = ?";
    $params[] = $hashedPassword;
    $types .= "s";
}

/* Profile Picture */
if (!empty($newProfilePicture)) {
    $allowedPictures = [
        "WhiteMale",
        "BlackMale",
        "AsianMale",
        "NormalMale",
        "WhiteFemale",
        "BlackFemale",
        "AsianFemale",
        "IndianFemale"
    ];

    if (!in_array($newProfilePicture, $allowedPictures)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid profile picture']);
        exit();
    }

    $fields[] = "profilepicture = ?";
    $params[] = $newProfilePicture;
    $types .= "s";
}

if (empty($fields)) {
    echo json_encode(['status' => 'error', 'message' => 'Nothing to update']);
    exit();
}

/* WHERE CLAUSE */
$params[] = $currentUser;
$types .= "s";

$sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

/* ===============================
   EXECUTE UPDATE
================================ */
if ($stmt->execute()) {

    if (!empty($newUsername)) {
        $_SESSION['player_name'] = $newUsername;
    }

    if (!empty($newProfilePicture)) {
        $_SESSION['profilepicture'] = $newProfilePicture;
    }

    echo json_encode([
        'status' => 'ok',
        'message' => 'Edited successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database update failed'
    ]);
}
