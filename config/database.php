<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_assessment');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to prevent special character issues
$conn->set_charset("utf8mb4");

// Function to prevent SQL injection
function sanitize($conn, $input) {
    if (is_array($input)) {
        return array_map(function($item) use ($conn) {
            return sanitize($conn, $item);
        }, $input);
    }
    return $conn->real_escape_string($input);
}

// Function to prevent XSS
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?> 