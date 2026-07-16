<?php
/**
 * actions/update_city.php  (UPDATE)
 * ------------------------------------
 * Lets the user set a custom alias for a saved city
 * (e.g. rename "New York" to "Office Location").
 */
require_once '../includes/bootstrap.php';
require_login();

$id    = (int) ($_POST['id'] ?? 0);
$alias = trim($_POST['alias'] ?? '');

$db = getDB();

// Make sure the city belongs to the logged-in user before editing it
$stmt = $db->prepare('SELECT * FROM cities WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$city = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$city) {
    $_SESSION['flash'] = 'City not found.';
    redirect('../dashboard.php');
}

$newAlias = $alias !== '' ? $alias : $city['city_name'];

$stmt = $db->prepare('UPDATE cities SET alias = ? WHERE id = ? AND user_id = ?');
$stmt->execute([$newAlias, $id, $_SESSION['user_id']]);

$_SESSION['flash'] = 'City renamed successfully.';
redirect('../dashboard.php');
