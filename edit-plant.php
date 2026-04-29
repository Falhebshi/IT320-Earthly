<?php
require_once 'auth.php';
require_once 'db.php';

$stmt = $pdo->prepare("SELECT * FROM plant");
$stmt->execute();
$plants = $stmt->fetchAll();

$selectedPlant = null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM plant WHERE plant_id = ?");
    $stmt->execute([$_GET['id']]);
    $selectedPlant = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['plant_id'];

    $common_name = $_POST['common_name'];
    $scientific_name = $_POST['scientific_name'];
    $category = $_POST['category'];
    $difficulty = $_POST['difficulty'];
    $origin = $_POST['origin'];
    $watering = $_POST['watering'];
    $light = $_POST['light'];
    $soil = $_POST['soil'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $size = $_POST['size'];
    $pet_safe = $_POST['pet_safe'];
    $about = $_POST['about'];
    $tip = $_POST['tip'];
    $fun_fact = $_POST['fun_fact'];

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $imagePath = "images/" . $imageName;
        $tmp = $_FILES['image']['tmp_name'];

        move_uploaded_file($tmp, $imagePath);

        $stmt = $pdo->prepare("
            UPDATE plant SET
                common_name = ?,
                scientific_name = ?,
                category = ?,
                difficulty = ?,
                origin = ?,
                watering = ?,
                light = ?,
                soil = ?,
                temperature = ?,
                humidity = ?,
                size = ?,
                pet_safe = ?,
                about = ?,
                tip = ?,
                fun_fact = ?,
                image = ?
            WHERE plant_id = ?
        ");

        $stmt->execute([
            $common_name,
            $scientific_name,
            $category,
            $difficulty,
            $origin,
            $watering,
            $light,
            $soil,
            $temperature,
            $humidity,
            $size,
            $pet_safe,
            $about,
            $tip,
            $fun_fact,
            $imagePath,
            $id
        ]);

    } else {
        $stmt = $pdo->prepare("
            UPDATE plant SET
                common_name = ?,
                scientific_name = ?,
                category = ?,
                difficulty = ?,
                origin = ?,
                watering = ?,
                light = ?,
                soil = ?,
                temperature = ?,
                humidity = ?,
                size = ?,
                pet_safe = ?,
                about = ?,
                tip = ?,
                fun_fact = ?
            WHERE plant_id = ?
        ");

        $stmt->execute([
            $common_name,
            $scientific_name,
            $category,
            $difficulty,
            $origin,
            $watering,
            $light,
            $soil,
            $temperature,
            $humidity,
            $size,
            $pet_safe,
            $about,
            $tip,
            $fun_fact,
            $id
        ]);
    }

    header("Location: admin-dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Plant</title>
</head>
<body>

<h2>Edit Plant</h2>

<form method="GET">
    <select name="id">
        <option value="">Select plant</option>
        <?php foreach($plants as $p): ?>
            <option value="<?= htmlspecialchars($p['plant_id']) ?>">
                <?= htmlspecialchars($p['common_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Load</button>
</form>

<hr>

<?php if($selectedPlant): ?>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="plant_id" value="<?= htmlspecialchars($selectedPlant['plant_id']) ?>">

<input name="common_name" value="<?= htmlspecialchars($selectedPlant['common_name']) ?>" placeholder="Common Name"><br>
<input name="scientific_name" value="<?= htmlspecialchars($selectedPlant['scientific_name']) ?>" placeholder="Scientific Name"><br>
<input name="category" value="<?= htmlspecialchars($selectedPlant['category']) ?>"><br>
<input name="difficulty" value="<?= htmlspecialchars($selectedPlant['difficulty']) ?>"><br>
<input name="origin" value="<?= htmlspecialchars($selectedPlant['origin']) ?>"><br>
<input name="watering" value="<?= htmlspecialchars($selectedPlant['watering']) ?>"><br>
<input name="light" value="<?= htmlspecialchars($selectedPlant['light']) ?>"><br>
<input name="soil" value="<?= htmlspecialchars($selectedPlant['soil']) ?>"><br>
<input name="temperature" value="<?= htmlspecialchars($selectedPlant['temperature']) ?>"><br>
<input name="humidity" value="<?= htmlspecialchars($selectedPlant['humidity']) ?>"><br>
<input name="size" value="<?= htmlspecialchars($selectedPlant['size']) ?>"><br>

<select name="pet_safe">
    <option value="1" <?= $selectedPlant['pet_safe']==1 ? 'selected':'' ?>>1</option>
    <option value="0" <?= $selectedPlant['pet_safe']==0 ? 'selected':'' ?>>0</option>
</select><br>

<textarea name="about"><?= htmlspecialchars($selectedPlant['about']) ?></textarea><br>
<textarea name="tip"><?= htmlspecialchars($selectedPlant['tip']) ?></textarea><br>
<textarea name="fun_fact"><?= htmlspecialchars($selectedPlant['fun_fact']) ?></textarea><br>

<input type="file" name="image"><br>

<button type="submit">Save Changes</button>

</form>

<?php endif; ?>

</body>
</html>
