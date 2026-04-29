<?php
// ============================================================
// care_task_logic.php — Care Task Generation Logic
// ============================================================
// Creates default care tasks when a user adds a plant.
// This file does not run by itself. Include it where needed.
// ============================================================

function createCareTasksForPlant(PDO $pdo, int $userPlantId, array $plant): void
{
    $today = date('Y-m-d');

    /*
        Basic idea:
        - Every added plant gets a watering task for today.
        - Extra tasks are created based on category / humidity / difficulty.
        - Future tasks will appear on the dashboard when their task_date arrives.
    */

    $tasks = [];

    // Main task: always create watering task for today
    $tasks[] = [
        'type' => 'Watering',
        'date' => $today
    ];

    $category = strtolower($plant['category'] ?? '');
    $humidity = strtolower($plant['humidity'] ?? '');
    $difficulty = strtolower($plant['difficulty'] ?? '');

    // Humidity-loving plants can get misting tasks
    if (
        str_contains($category, 'tropical') ||
        str_contains($category, 'fern') ||
        str_contains($humidity, 'high')
    ) {
        $tasks[] = [
            'type' => 'Misting',
            'date' => $today
        ];
    }

    // Succulents usually need soil dryness checks instead of frequent watering
    if (str_contains($category, 'succulent')) {
        $tasks[] = [
            'type' => 'Soil Check',
            'date' => date('Y-m-d', strtotime('+7 days'))
        ];
    }

    // Medium/intermediate/advanced plants get a follow-up inspection
    if (
        str_contains($difficulty, 'medium') ||
        str_contains($difficulty, 'intermediate') ||
        str_contains($difficulty, 'advanced')
    ) {
        $tasks[] = [
            'type' => 'Health Check',
            'date' => date('Y-m-d', strtotime('+7 days'))
        ];
    }

    // General monthly maintenance task
    $tasks[] = [
        'type' => 'Fertilizing',
        'date' => date('Y-m-d', strtotime('+30 days'))
    ];

    $stmt = $pdo->prepare("
        INSERT INTO care_task (user_plant_id, task_type, status, task_date)
        VALUES (?, ?, 'pending', ?)
    ");

    foreach ($tasks as $task) {
        $stmt->execute([
            $userPlantId,
            $task['type'],
            $task['date']
        ]);
    }
}

function createNextCareTask(PDO $pdo, int $userPlantId, string $taskType): void
{
    $taskTypeLower = strtolower($taskType);

    if ($taskTypeLower === 'watering') {
        $nextDate = date('Y-m-d', strtotime('+7 days'));
    } elseif ($taskTypeLower === 'misting') {
        $nextDate = date('Y-m-d', strtotime('+3 days'));
    } elseif ($taskTypeLower === 'soil check') {
        $nextDate = date('Y-m-d', strtotime('+7 days'));
    } elseif ($taskTypeLower === 'health check') {
        $nextDate = date('Y-m-d', strtotime('+14 days'));
    } elseif ($taskTypeLower === 'fertilizing') {
        $nextDate = date('Y-m-d', strtotime('+30 days'));
    } else {
        $nextDate = date('Y-m-d', strtotime('+7 days'));
    }

    $stmt = $pdo->prepare("
        INSERT INTO care_task (user_plant_id, task_type, status, task_date)
        VALUES (?, ?, 'pending', ?)
    ");

    $stmt->execute([$userPlantId, $taskType, $nextDate]);
}
?>
