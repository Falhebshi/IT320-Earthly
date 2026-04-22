<?php
// ============================================================
// auth_check.php — Session Guard
// ============================================================
// Include this at the top of any PHP page that requires login.
// If the user is not logged in, they get redirected to login.
//
// Usage:  require_once 'auth.php';
// ============================================================
 
session_start();
 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}