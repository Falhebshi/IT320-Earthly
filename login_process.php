<?php
// ============================================================
// login_process.php — User & Admin Login (US-2, US-11)
// ============================================================
// Checks admin table first, then user table. Both use hashed passwords.
// ============================================================

session_start();
require_once 'db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// ----------------------------------------------------------
// 1. Collect inputs
// ----------------------------------------------------------
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ----------------------------------------------------------
// 2. Validation
// ----------------------------------------------------------
if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter both email and password.';
    header('Location: login.php');
    exit;
}

// ----------------------------------------------------------
// 3. Try ADMIN table first
// ----------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM admin WHERE email = ?');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['email']    = $admin['email'];
    $_SESSION['role']     = 'admin';

    header('Location: admin-dashboard.html');
    exit;
}

// ----------------------------------------------------------
// 4. Try USER table
// ----------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM user WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = 'user';

    header('Location: user-dashboard.php');
    exit;
}

// ----------------------------------------------------------
// 5. Neither matched
// ----------------------------------------------------------
$_SESSION['login_error'] = 'Invalid email or password.';
header('Location: login.php');
exit;
