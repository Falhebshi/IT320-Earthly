<?php
// ============================================================
// register.php — User Registration (US-1)
// ============================================================
// Pure server-side: validates, inserts, redirects.
// Errors go to $_SESSION['register_error']
// On success → redirect to login.html
// ============================================================

session_start();
require_once 'db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ----------------------------------------------------------
// 1. Collect & trim inputs
// ----------------------------------------------------------
$first_name = trim($_POST['firstName'] ?? '');
$last_name  = trim($_POST['lastName']  ?? '');
$email      = trim($_POST['email']      ?? '');
$password   = $_POST['password']         ?? '';
$confirm    = $_POST['confirmPassword']  ?? '';

// ----------------------------------------------------------
// 2. Validation
// ----------------------------------------------------------
$errors = [];

if ($first_name === '') {
    $errors[] = 'First name is required.';
}
if ($last_name === '') {
    $errors[] = 'Last name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

// Check if email already exists
if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT user_id FROM user WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'An account with this email already exists.';
    }
}

// ----------------------------------------------------------
// 3. If errors → redirect back
// ----------------------------------------------------------
if (!empty($errors)) {
    $_SESSION['register_error'] = implode(' ', $errors);
    // Preserve form data so user doesn't have to re-type
    $_SESSION['register_data'] = [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
    ];
    header('Location: register.php');
    exit;
}

// ----------------------------------------------------------
// 4. Create the account
// ----------------------------------------------------------
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO user (first_name, last_name, email, password) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$first_name, $last_name, $email, $hashed]);

// Also create a streak row for this new user (starts at 0)
$user_id = $pdo->lastInsertId();
$stmt = $pdo->prepare(
    'INSERT INTO streak (user_id, current_streak, highest_streak, last_care_date) VALUES (?, 0, 0, NULL)'
);
$stmt->execute([$user_id]);

// ----------------------------------------------------------
// 5. Success → redirect to login
// ----------------------------------------------------------
$_SESSION['register_success'] = 'Account created successfully! Please log in.';
header('Location: login.php');
exit;