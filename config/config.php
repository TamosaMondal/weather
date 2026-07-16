<?php
/**
 * config.php
 * -----------
 * Central place for all app settings.
 *
 * Weather + AQI data comes from Open-Meteo (https://open-meteo.com).
 * No API key required, so this works fine even when the OpenWeatherMap
 * service is blocked/unreachable (e.g. on a locked-down office laptop).
 */

// Turns a city name into latitude/longitude
define('GEOCODE_BASE_URL', 'https://geocoding-api.open-meteo.com/v1/search');

// Current weather for a lat/lon
define('FORECAST_BASE_URL', 'https://api.open-meteo.com/v1/forecast');

// Current air quality / AQI for a lat/lon
define('AIR_QUALITY_BASE_URL', 'https://air-quality-api.open-meteo.com/v1/air-quality');

// SQLite database file (auto-created on first run, no server needed)
define('DB_PATH', __DIR__ . '/../database.sqlite');
