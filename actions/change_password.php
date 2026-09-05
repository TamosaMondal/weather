<?php
/**
 * actions/change_password.php
 * ------------------------------
 * Lets a logged-in user change their password. Requires the
 * current password to be entered correctly first.
 */
require_once '../includes/bootstrap.php';
require_login();

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$db = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($current, $user['password'])) {
    $_SESSION['flash'] = 'Current password is incorrect.';
    redirect('../profile.php');
}

if (strlen($new) < 4) {
    $_SESSION['flash'] = 'New password must be at least 4 characters.';
    redirect('../profile.php');
}

if ($new !== $confirm) {
    $_SESSION['flash'] = 'New passwords do not match.';
    redirect('../profile.php');
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
$stmt->execute([$hash, $_SESSION['user_id']]);

$_SESSION['flash'] = 'Password updated successfully.';
redirect('../profile.php');
