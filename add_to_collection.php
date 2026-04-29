<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'care_task_logic.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$plant_id = $_POST['plant_id'] ?? null;

if (!$plant_id) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Load plant details so care task logic can use category, humidity, difficulty, etc.
    $stmt = $pdo->prepare("SELECT * FROM plant WHERE plant_id = ?");
    $stmt->execute([$plant_id]);
    $plant = $stmt->fetch();

    if (!$plant) {
        throw new Exception("Plant not found.");
    }

    // Add plant to user's collection
    $stmt = $pdo->prepare("
        INSERT INTO user_plant (user_id, plant_id, nickname, location, note)
        VALUES (?, ?, '', '', '')
    ");
    $stmt->execute([$user_id, $plant_id]);

    $userPlantId = (int) $pdo->lastInsertId();

    // Create care tasks using separate logic file
    createCareTasksForPlant($pdo, $userPlantId, $plant);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false]);
}

exit;
?>