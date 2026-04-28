<?php
require_once 'auth.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM streak WHERE user_id = ?");
$stmt->execute([$user_id]);
$streak = $stmt->fetch();

if (!$streak) {
    $insert = $pdo->prepare("
        INSERT INTO streak (user_id, current_streak, highest_streak, last_care_date)
        VALUES (?, 0, 0, NULL)
    ");
    $insert->execute([$user_id]);

    $stmt = $pdo->prepare("SELECT * FROM streak WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $streak = $stmt->fetch();
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM care_task ct
    JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
    WHERE up.user_id = ? AND ct.task_date = ?
");
$stmt->execute([$user_id, $today]);
$totalTodayTasks = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM care_task ct
    JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
    WHERE up.user_id = ? AND ct.task_date = ? AND ct.status = 'completed'
");
$stmt->execute([$user_id, $today]);
$completedTodayTasks = $stmt->fetchColumn();

$todayStatus = ($totalTodayTasks > 0 && $completedTodayTasks == $totalTodayTasks) ? 'Done' : 'Pending';

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ct.task_date)
    FROM care_task ct
    JOIN user_plant up ON ct.user_plant_id = up.user_plant_id
    WHERE up.user_id = ?
      AND ct.status = 'completed'
      AND YEARWEEK(ct.task_date, 1) = YEARWEEK(CURDATE(), 1)
");
$stmt->execute([$user_id]);
$weekCount = $stmt->fetchColumn();

$completionRate = round(($weekCount / 7) * 100);
$nextMilestone = $streak['current_streak'] < 10 ? 10 : (($streak['current_streak'] < 30) ? 30 : 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watering Streak — Earthly</title>
    <style>
        body { font-family: Arial, sans-serif; background:#FAFAF5; margin:0; color:#2B2B2B; }
        nav { padding:20px 5%; background:white; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        nav a { text-decoration:none; color:#2D5A3D; margin-left:15px; font-weight:bold; }
        .page { padding:40px 5%; }
        .hero { background:#2D5A3D; color:white; padding:30px; border-radius:20px; margin-bottom:25px; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:25px; }
        .card { background:white; padding:20px; border-radius:15px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .card h3 { color:#2D5A3D; margin-top:0; }
        .big { font-size:36px; font-weight:bold; color:#2D5A3D; }
        .progress { background:#e7ece5; border-radius:20px; height:18px; overflow:hidden; }
        .fill { height:100%; background:#2D5A3D; width:<?php echo $completionRate; ?>%; }
        .days { display:grid; grid-template-columns:repeat(7,1fr); gap:10px; margin-top:15px; }
        .day { padding:15px; background:#FAFAF5; border-radius:12px; text-align:center; }
        .done { background:#2D5A3D; color:white; }
    </style>
</head>
<body>

<nav>
    <div><strong>Earthly</strong></div>
    <div>
        <a href="user-dashboard.php">Dashboard</a>
        <a href="plant-catalog.html">Catalog</a>
        <a href="manage-plants.html">Manage My Plants</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page">

    <div class="hero">
        <h1>Your consistency matters.</h1>
        <p>Complete your daily care tasks to keep your watering streak active.</p>
    </div>

    <div class="stats">
        <div class="card">
            <h3>Current Streak</h3>
            <div class="big"><?php echo $streak['current_streak']; ?></div>
            <p>days</p>
        </div>

        <div class="card">
            <h3>Longest Streak</h3>
            <div class="big"><?php echo $streak['highest_streak']; ?></div>
            <p>days</p>
        </div>

        <div class="card">
            <h3>Today’s Status</h3>
            <div class="big"><?php echo $todayStatus; ?></div>
            <p><?php echo $completedTodayTasks; ?> / <?php echo $totalTodayTasks; ?> tasks completed today</p>
        </div>

        <div class="card">
            <h3>Next Milestone</h3>
            <div class="big"><?php echo $nextMilestone; ?></div>
            <p>days goal</p>
        </div>
    </div>

    <div class="card">
        <h3>This Week Progress</h3>
        <p><?php echo $weekCount; ?> / 7 days completed</p>

        <div class="progress">
            <div class="fill"></div>
        </div>

        <p>Completion Rate: <?php echo $completionRate; ?>%</p>

        <div class="days">
            <?php
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            for ($i = 0; $i < 7; $i++):
            ?>
                <div class="day <?php echo ($i < $weekCount) ? 'done' : ''; ?>">
                    <?php echo $days[$i]; ?>
                    <br>
                    <?php echo ($i < $weekCount) ? '✓' : '—'; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="card" style="margin-top:25px;">
        <h3>How the streak works</h3>
        <p>
            When all watering tasks for today are completed, the system updates the current streak.
            If the user continues completing daily care tasks, the streak increases.
            If a day is missed, the streak starts again.
        </p>
    </div>

</div>

</body>
</html>
