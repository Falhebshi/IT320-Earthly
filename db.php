<?php
// ============================================================
// db.php — Database Connection (Earthly)
// Shared across all PHP files. Include with require_once.
// ============================================================

$host     = 'localhost';
$dbname   = 'earthly-db';
$username = 'root';
$password = '';  // default XAMPP/MAMP password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

if ($pdo) { //for debugging
        echo json_encode(['success' => true, 'message' => 'Database connection established.']); 
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}