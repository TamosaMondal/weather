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

// This corporate laptop sits behind an SSL-inspecting firewall/proxy
// (Zscaler, Netskope, etc.) that re-signs HTTPS traffic with its own
// root CA and also blocks other stuff PHP/curl expects by default.
// PHP/curl doesn't trust that CA, so every request to Open-Meteo /
// Wikipedia fails with a cert error, which the app then shows as
// "City not found".
//
// Keeping this false permanently for this build of the app — this
// copy is only ever run on this locked-down corporate machine, so
// there's no "normal network" version of it to flip back for.
define('SSL_VERIFY_PEER', false);

// Corporate proxy address — if your machine routes HTTPS through a
// proxy, set this to 'http://proxy-host:port' (e.g. 'http://proxy.corp.com:8080').
// Leave as empty string '' to let curl auto-detect from environment.
define('CURL_PROXY', '');

// User-Agent sent with every outbound curl request.
// Some corporate proxies block requests that have no User-Agent.
define('CURL_USER_AGENT', 'Mozilla/5.0 (WeatherBookmarkDashboard/1.0; educational project)');
