<?php
/**
 * actions/pin_city.php
 * ----------------------
 * Toggles the "pinned" flag on a saved city. Pinned cities are
 * shown first on the dashboard.
 */
require_once '../includes/bootstrap.php';
require_login();

$id = (int) ($_POST['id'] ?? 0);

$db = getDB();
$stmt = $db->prepare('SELECT * FROM cities WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$city = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$city) {
    $_SESSION['flash'] = 'City not found.';
    redirect('../dashboard.php');
}

$newPinned = $city['pinned'] ? 0 : 1;

$stmt = $db->prepare('UPDATE cities SET pinned = ? WHERE id = ? AND user_id = ?');
$stmt->execute([$newPinned, $id, $_SESSION['user_id']]);

$_SESSION['flash'] = $newPinned ? 'City pinned to the top.' : 'City unpinned.';
redirect('../dashboard.php');
