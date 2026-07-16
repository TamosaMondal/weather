<?php
/**
 * actions/city_places.php
 * -------------------------
 * Given a free-text location (?q=...), geocodes it via Open-Meteo
 * and returns nearby notable places (name + thumbnail) sourced from
 * Wikipedia's geosearch. Used to populate the "famous places" side
 * panel while the user is filling in the add-city form. Works for
 * essentially any place worldwide, not just a fixed city list.
 */
require_once '../includes/bootstrap.php';
require_login();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['found' => false]);
    exit;
}

$location = geocode_city($q);
if ($location === null) {
    echo json_encode(['found' => false]);
    exit;
}

$places = wikipedia_landmarks_near($location['latitude'], $location['longitude'], 6);

// Fall back to the small static list (names only, best-effort thumbnail
// lookup) if geosearch didn't turn up anything useful.
if (empty($places)) {
    $names = famous_places_for_city($location['name']);
    $thumbs = fetch_landmark_thumbnails_multi($names);
    foreach ($names as $name) {
        $places[] = [
            'title'     => $name,
            'thumbnail' => $thumbs[$name] ?? null,
        ];
    }
}

echo json_encode([
    'found'     => true,
    'city_name' => $location['name'],
    'country'   => $location['country'],
    'places'    => $places,
]);
