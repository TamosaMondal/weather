<?php
/**
 * actions/delete_city.php  (DELETE)
 * ------------------------------------
 * Removes a saved city from the logged-in user's dashboard.
 */
require_once '../includes/bootstrap.php';
require_login();

$id = (int) ($_POST['id'] ?? 0);

$db = getDB();
$stmt = $db->prepare('DELETE FROM cities WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);

$_SESSION['flash'] = 'City removed from your dashboard.';
redirect('../dashboard.php');
