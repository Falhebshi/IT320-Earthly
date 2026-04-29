<?php
require_once 'auth.php';
include("db_config.php");
/* ── Get all plants ── */
$plants = [];
$result = mysqli_query($conn, "SELECT * FROM plant");
while ($row = mysqli_fetch_assoc($result)) {
    $plants[] = $row;
}

/* ── Load selected plant ── */
$selectedPlant = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = mysqli_query($conn, "SELECT * FROM plant WHERE plant_id=$id");
    $selectedPlant = mysqli_fetch_assoc($res);
}

/* ── Update plant ── */
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

    /* ── Image upload (optional) ── */
    if (!empty($_FILES['image']['name'])) {
        $imageName = $_FILES['image']['name'];
        $tmp = $_FILES['image']['tmp_name'];
        move_uploaded_file($tmp, "images/" . $imageName);

        $query = "UPDATE plant SET
            common_name='$common_name',
            scientific_name='$scientific_name',
            category='$category',
            difficulty='$difficulty',
            origin='$origin',
            watering='$watering',
            light='$light',
            soil='$soil',
            temperature='$temperature',
            humidity='$humidity',
            size='$size',
            pet_safe='$pet_safe',
            about='$about',
            tip='$tip',
            fun_fact='$fun_fact',
            image='$imageName'
            WHERE plant_id=$id";
    } else {
        $query = "UPDATE plant SET
            common_name='$common_name',
            scientific_name='$scientific_name',
            category='$category',
            difficulty='$difficulty',
            origin='$origin',
            watering='$watering',
            light='$light',
            soil='$soil',
            temperature='$temperature',
            humidity='$humidity',
            size='$size',
            pet_safe='$pet_safe',
            about='$about',
            tip='$tip',
            fun_fact='$fun_fact'
            WHERE plant_id=$id";
    }

    mysqli_query($conn, $query);
    header("Location: admin-dashboard.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Plant</title>
</head>
<body>

<h2>Edit Plant</h2>

<!-- ── Select Plant ── -->
<form method="GET">
    <select name="id">
        <option>Select plant</option>
        <?php foreach($plants as $p): ?>
            <option value="<?= $p['plant_id'] ?>">
                <?= $p['common_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Load</button>
</form>

<hr>

<?php if($selectedPlant): ?>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="plant_id" value="<?= $selectedPlant['plant_id'] ?>">

<input name="common_name" value="<?= $selectedPlant['common_name'] ?>" placeholder="Common Name"><br>
<input name="scientific_name" value="<?= $selectedPlant['scientific_name'] ?>" placeholder="Scientific Name"><br>
<input name="category" value="<?= $selectedPlant['category'] ?>"><br>
<input name="difficulty" value="<?= $selectedPlant['difficulty'] ?>"><br>
<input name="origin" value="<?= $selectedPlant['origin'] ?>"><br>
<input name="watering" value="<?= $selectedPlant['watering'] ?>"><br>
<input name="light" value="<?= $selectedPlant['light'] ?>"><br>
<input name="soil" value="<?= $selectedPlant['soil'] ?>"><br>
<input name="temperature" value="<?= $selectedPlant['temperature'] ?>"><br>
<input name="humidity" value="<?= $selectedPlant['humidity'] ?>"><br>
<input name="size" value="<?= $selectedPlant['size'] ?>"><br>

<select name="pet_safe">
    <option <?= $selectedPlant['pet_safe']==1 ? 'selected':'' ?>>1</option>
    <option <?= $selectedPlant['pet_safe']==0 ? 'selected':'' ?>>0</option>
</select><br>

<textarea name="about"><?= $selectedPlant['about'] ?></textarea><br>
<textarea name="tip"><?= $selectedPlant['tip'] ?></textarea><br>
<textarea name="fun_fact"><?= $selectedPlant['fun_fact'] ?></textarea><br>

<input type="file" name="image"><br>

<button type="submit">Save Changes</button>

</form>

<?php endif; ?>

</body>
</html>
