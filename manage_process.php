<?php
require_once 'auth.php';
require_once 'db.php';

$user_id = $_SESSION['user_id'];

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE user_plant SET nickname = ?, location = ?, note = ? WHERE user_plant_id = ? AND user_id = ?");
    $stmt->execute([$_POST['nickname'], $_POST['location'], $_POST['note'], $_POST['id'], $user_id]);
    echo json_encode(['success' => true]);
}

if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM user_plant WHERE user_plant_id = ? AND user_id = ?");
    $stmt->execute([$_POST['id'], $user_id]);
    echo json_encode(['success' => true]);
}
?>