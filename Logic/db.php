<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "risingwaters";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
