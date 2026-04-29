<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: plant-catalog.php');
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$plant_id = $_POST['plant_id'] ?? null;

if (!$plant_id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO user_plant (user_id, plant_id, nickname, location, note)
    VALUES (?, ?, '', '', '')
");

if ($stmt->execute([$user_id, $plant_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

exit;
?>