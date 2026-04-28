<?php
require_once 'auth.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $task_id = (int) $_POST['task_id'];

    $stmt = $pdo->prepare("
        UPDATE care_task ct
        JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
        SET ct.status = 'completed'
        WHERE ct.task_id = ? AND up.user_id = ?
    ");
    $stmt->execute([$task_id, $user_id]);

    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM care_task ct
        JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
        WHERE up.user_id = ? AND ct.task_date = ? AND ct.status != 'completed'
    ");
    $check->execute([$user_id, $today]);
    $pendingToday = $check->fetchColumn();

    if ($pendingToday == 0) {
        $stmt = $pdo->prepare("SELECT * FROM streak WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $streak = $stmt->fetch();

        if ($streak) {
            if ($streak['last_care_date'] !== $today) {
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $newCurrent = ($streak['last_care_date'] === $yesterday) ? $streak['current_streak'] + 1 : 1;
                $newHighest = max($newCurrent, $streak['highest_streak']);

                $update = $pdo->prepare("
                    UPDATE streak 
                    SET current_streak = ?, highest_streak = ?, last_care_date = ?
                    WHERE user_id = ?
                ");
                $update->execute([$newCurrent, $newHighest, $today, $user_id]);
            }
        } else {
            $insert = $pdo->prepare("
                INSERT INTO streak (user_id, current_streak, highest_streak, last_care_date)
                VALUES (?, 1, 1, ?)
            ");
            $insert->execute([$user_id, $today]);
        }
    }

    header("Location: user-dashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT up.user_plant_id, up.nickname, up.location, up.note,
           p.common_name, p.scientific_name, p.watering, p.light, p.tip, p.image
    FROM user_plant up
    JOIN plant p ON up.plant_id = p.plant_id
    WHERE up.user_id = ?
");
$stmt->execute([$user_id]);
$plants = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT ct.task_id, ct.task_type, ct.status, ct.task_date,
           up.nickname, p.common_name
    FROM care_task ct
    JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
    JOIN plant p ON up.plant_id = p.plant_id
    WHERE up.user_id = ?
    ORDER BY ct.task_date ASC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM streak WHERE user_id = ?");
$stmt->execute([$user_id]);
$streak = $stmt->fetch();

$currentStreak = $streak['current_streak'] ?? 0;
$highestStreak = $streak['highest_streak'] ?? 0;

$totalPlants = count($plants);
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$pendingTasks = $totalTasks - $completedTasks;
$completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard — Earthly</title>
    <style>
        body { font-family: Arial, sans-serif; background:#FAFAF5; margin:0; color:#2B2B2B; }
        nav { padding:20px 5%; background:white; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        nav a { text-decoration:none; color:#2D5A3D; margin-left:15px; font-weight:bold; }
        .page { padding:40px 5%; }
        .hero { background:#2D5A3D; color:white; padding:30px; border-radius:20px; margin-bottom:25px; }
        .hero h1 { margin:0 0 10px; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:25px; }
        .card { background:white; padding:20px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .card h3 { margin-top:0; color:#2D5A3D; }
        .section { margin-bottom:25px; }
        .task { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding:12px 0; }
        .done { color:green; font-weight:bold; }
        .pending { color:#B54A4A; font-weight:bold; }
        button { background:#2D5A3D; color:white; border:0; padding:9px 14px; border-radius:8px; cursor:pointer; }
        .plant-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:15px; }
        .plant img { width:100%; height:180px; object-fit:cover; border-radius:12px; }
        .empty { color:#777; }
    </style>
</head>
<body>

<nav>
    <div><strong>Earthly</strong></div>
    <div>
        <a href="user-dashboard.php">Dashboard</a>
        <a href="plant-catalog.html">Catalog</a>
        <a href="manage-plants.html">Manage My Plants</a>
        <a href="watering-streak.php">Watering Streak</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page">

    <div class="hero">
        <h1>Good day, <?php echo htmlspecialchars($first_name); ?>.</h1>
        <p>Here is your plant dashboard, today’s care tasks, and your watering streak.</p>
    </div>

    <div class="stats">
        <div class="card">
            <h3>Saved Plants</h3>
            <p><?php echo $totalPlants; ?></p>
        </div>
        <div class="card">
            <h3>Total Tasks</h3>
            <p><?php echo $totalTasks; ?></p>
        </div>
        <div class="card">
            <h3>Pending Tasks</h3>
            <p><?php echo $pendingTasks; ?></p>
        </div>
        <div class="card">
            <h3>Current Streak</h3>
            <p><?php echo $currentStreak; ?> days</p>
        </div>
    </div>

    <div class="card section">
        <h3>Care Tasks</h3>

        <?php if (empty($tasks)): ?>
            <p class="empty">No care tasks found yet.</p>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div class="task">
                    <div>
                        <strong><?php echo htmlspecialchars($task['task_type']); ?></strong>
                        for <?php echo htmlspecialchars($task['nickname'] ?: $task['common_name']); ?>
                        <br>
                        <small>Date: <?php echo htmlspecialchars($task['task_date']); ?></small>
                    </div>

                    <div>
                        <?php if ($task['status'] === 'completed'): ?>
                            <span class="done">Completed</span>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                <button type="submit" name="complete_task">Done</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card section">
        <h3>My Plants</h3>

        <?php if (empty($plants)): ?>
            <p class="empty">You have not added plants yet.</p>
        <?php else: ?>
            <div class="plant-grid">
                <?php foreach ($plants as $plant): ?>
                    <div class="card plant">
                        <?php if (!empty($plant['image'])): ?>
                            <img src="images/<?php echo htmlspecialchars($plant['image']); ?>" alt="Plant image">
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($plant['nickname'] ?: $plant['common_name']); ?></h3>
                        <p><em><?php echo htmlspecialchars($plant['scientific_name']); ?></em></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($plant['location'] ?: 'Not set'); ?></p>
                        <p><strong>Watering:</strong> <?php echo htmlspecialchars($plant['watering']); ?></p>
                        <p><strong>Light:</strong> <?php echo htmlspecialchars($plant['light']); ?></p>
                        <p><strong>Tip:</strong> <?php echo htmlspecialchars($plant['tip']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
