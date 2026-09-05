<?php
/**
 * actions/add_city.php  (CREATE)
 * --------------------------------
 * Receives the city name typed into the dashboard search box,
 * calls Open-Meteo for its current weather + AQI, and
 * inserts a new row into the "cities" table for the logged-in user.
 */
require_once '../includes/bootstrap.php';
require_login();

$city = trim($_POST['city_name'] ?? '');

if ($city === '') {
    $_SESSION['flash'] = 'Please enter a city name.';
    redirect('../dashboard.php');
}

// If the user picked a specific match from the suggestions dropdown,
// the exact coordinates ride along in these hidden fields — use them
// directly instead of re-geocoding the typed text (faster, and avoids
// picking the wrong "Springfield"/"Paris" if the API's top match
// differs from the one the user actually clicked).
$lat = $_POST['city_lat'] ?? '';
$lon = $_POST['city_lon'] ?? '';

if ($lat !== '' && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
    $weather = fetch_weather_for_location([
        'name'      => trim($_POST['city_display_name'] ?? $city),
        'country'   => trim($_POST['city_country'] ?? ''),
        'latitude'  => (float) $lat,
        'longitude' => (float) $lon,
    ]);
} else {
    $weather = fetch_weather($city);
}

if ($weather === null) {
    $_SESSION['flash'] = 'City not found or the weather API is unavailable. Please try again.';
    redirect('../dashboard.php');
}

$db = getDB();
$stmt = $db->prepare(
    'INSERT INTO cities (user_id, city_name, alias, country, temperature, weather_desc, weather_icon, latitude, longitude, aqi, aqi_category)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $_SESSION['user_id'],
    $weather['city_name'],
    $weather['city_name'], // alias defaults to the real city name
    $weather['country'],
    $weather['temperature'],
    $weather['weather_desc'],
    $weather['weather_icon'],
    $weather['latitude'],
    $weather['longitude'],
    $weather['aqi'],
    $weather['aqi_category'],
]);

$_SESSION['flash'] = 'City added to your dashboard.';
redirect('../dashboard.php');
