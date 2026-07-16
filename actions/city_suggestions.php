<?php
/**
 * actions/city_suggestions.php
 * -----------------------------
 * Given a partial location name (?q=...), returns a short list of
 * matching places from Open-Meteo's geocoding API (name, region,
 * country, lat/lon). This powers the autocomplete dropdown that
 * appears directly beneath the "Add a City" search box, separate
 * from the "famous places" side panel (city_places.php).
 *
 * Returning lat/lon here lets the frontend pass them straight through
 * to add_city.php when the user clicks a suggestion, so we don't have
 * to re-geocode (and re-guess) the same text a second time.
 */
require_once '../includes/bootstrap.php';
require_login();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['matches' => []]);
    exit;
}

$matches = geocode_city_matches($q, 6);

echo json_encode(['matches' => $matches]);
