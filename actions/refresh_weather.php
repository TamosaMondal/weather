<?php
/**
 * actions/refresh_weather.php  (part of READ)
 * -----------------------------------------------
 * Re-fetches the latest weather for one saved city from
 * Open-Meteo and updates the stored temperature/description/AQI.
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

$weather = fetch_weather($city['city_name']);

if ($weather === null) {
    $_SESSION['flash'] = 'Could not refresh weather right now. Please try again later.';
    redirect('../dashboard.php');
}

$stmt = $db->prepare(
    'UPDATE cities
     SET temperature = ?, weather_desc = ?, weather_icon = ?, latitude = ?, longitude = ?, aqi = ?, aqi_category = ?, updated_at = CURRENT_TIMESTAMP
     WHERE id = ? AND user_id = ?'
);
$stmt->execute([
    $weather['temperature'],
    $weather['weather_desc'],
    $weather['weather_icon'],
    $weather['latitude'],
    $weather['longitude'],
    $weather['aqi'],
    $weather['aqi_category'],
    $id,
    $_SESSION['user_id'],
]);

$_SESSION['flash'] = 'Weather refreshed.';
redirect('../dashboard.php');
