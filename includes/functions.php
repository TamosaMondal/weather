<?php
/**
 * functions.php
 * --------------
 * Small reusable helper functions used across the app.
 */

// Redirect visitors who are not logged in
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Escape output safely for HTML
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Simple redirect + stop execution
function redirect($path) {
    header('Location: ' . $path);
    exit;
}

/**
 * Small helper to GET a URL and decode the JSON response.
 * Returns null on any network/HTTP/JSON failure.
 */
function http_get_json($url, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Without a connect timeout, a slow/unreachable host can burn the
    // *entire* $timeout just trying to open the TCP connection before
    // curl even gets a chance to fail. Capping this separately means a
    // dead host fails fast instead of stalling the whole request.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $timeout));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError || $httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * Runs several GET-JSON requests concurrently instead of one-after-
 * another. Takes an assoc array of ['key' => ['url' => ..., 'timeout' => ...]]
 * and returns ['key' => decoded JSON or null].
 *
 * This is what turns the old "geocode, THEN weather, THEN AQI" chain
 * (worst case: sum of all three timeouts) into "geocode, then
 * weather+AQI at the same time" (worst case: the slowest of the two).
 */
function http_get_json_multi(array $requests) {
    $mh = curl_multi_init();
    $handles = [];

    foreach ($requests as $key => $req) {
        $ch = curl_init($req['url']);
        $timeout = $req['timeout'] ?? 10;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $timeout));
        if (!empty($req['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $req['headers']);
        }
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 1.0);
        }
    } while ($running && $status === CURLM_OK);

    $results = [];
    foreach ($handles as $key => $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $results[$key] = ($response === false || $response === null || $curlError || $httpCode !== 200)
            ? null
            : json_decode($response, true);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}

/**
 * Turns a free-text city name into a lat/lon + display name using
 * Open-Meteo's geocoding API. Returns null if nothing matches.
 */
function geocode_city($city) {
    $url = GEOCODE_BASE_URL . '?name=' . urlencode($city) . '&count=1&language=en&format=json';
    $data = http_get_json($url, 8);

    if (!isset($data['results'][0])) {
        return null;
    }

    $r = $data['results'][0];

    return [
        'name'      => $r['name'] ?? $city,
        'country'   => $r['country_code'] ?? ($r['country'] ?? ''),
        'latitude'  => $r['latitude'],
        'longitude' => $r['longitude'],
    ];
}

/**
 * Like geocode_city(), but returns several candidate matches instead of
 * just the first one. This powers the location-suggestions dropdown
 * beneath the search box, so the user can pick the exact "Springfield"
 * or "Paris" they mean instead of silently getting whichever one the
 * API decides is first.
 */
function geocode_city_matches($city, $count = 6) {
    $url = GEOCODE_BASE_URL . '?name=' . urlencode($city) . '&count=' . (int) $count . '&language=en&format=json';
    $data = http_get_json($url, 8);

    if (empty($data['results'])) {
        return [];
    }

    $matches = [];
    foreach ($data['results'] as $r) {
        $matches[] = [
            'name'      => $r['name'] ?? $city,
            'admin1'    => $r['admin1'] ?? '',
            'country'   => $r['country_code'] ?? ($r['country'] ?? ''),
            'country_name' => $r['country'] ?? '',
            'latitude'  => $r['latitude'],
            'longitude' => $r['longitude'],
        ];
    }
    return $matches;
}

/**
 * Maps Open-Meteo's WMO weather codes to a human description and an emoji icon.
 * See: https://open-meteo.com/en/docs (WMO Weather interpretation codes)
 */
function weather_code_to_info($code) {
    $map = [
        0  => ['Clear sky', '☀️'],
        1  => ['Mainly clear', '🌤️'],
        2  => ['Partly cloudy', '⛅'],
        3  => ['Overcast', '☁️'],
        45 => ['Fog', '🌫️'],
        48 => ['Depositing rime fog', '🌫️'],
        51 => ['Light drizzle', '🌦️'],
        53 => ['Moderate drizzle', '🌦️'],
        55 => ['Dense drizzle', '🌦️'],
        56 => ['Light freezing drizzle', '🌧️'],
        57 => ['Dense freezing drizzle', '🌧️'],
        61 => ['Slight rain', '🌧️'],
        63 => ['Moderate rain', '🌧️'],
        65 => ['Heavy rain', '🌧️'],
        66 => ['Light freezing rain', '🌧️'],
        67 => ['Heavy freezing rain', '🌧️'],
        71 => ['Slight snow fall', '🌨️'],
        73 => ['Moderate snow fall', '🌨️'],
        75 => ['Heavy snow fall', '🌨️'],
        77 => ['Snow grains', '🌨️'],
        80 => ['Slight rain showers', '🌦️'],
        81 => ['Moderate rain showers', '🌦️'],
        82 => ['Violent rain showers', '⛈️'],
        85 => ['Slight snow showers', '🌨️'],
        86 => ['Heavy snow showers', '🌨️'],
        95 => ['Thunderstorm', '⛈️'],
        96 => ['Thunderstorm with slight hail', '⛈️'],
        99 => ['Thunderstorm with heavy hail', '⛈️'],
    ];

    return $map[$code] ?? ['Unknown', '🌡️'];
}

/**
 * Maps a US AQI value to a category label (used for color-coding in the UI).
 */
function aqi_to_category($aqi) {
    if ($aqi === null) return '';
    if ($aqi <= 50)  return 'Good';
    if ($aqi <= 100) return 'Moderate';
    if ($aqi <= 150) return 'Unhealthy for Sensitive Groups';
    if ($aqi <= 200) return 'Unhealthy';
    if ($aqi <= 300) return 'Very Unhealthy';
    return 'Hazardous';
}

/**
 * Small built-in lookup of well-known landmarks for a handful of
 * famous cities. This is intentionally static (no external API) so
 * suggestions are instant. Falls back to an empty list for cities
 * that aren't in the map.
 */
function famous_places_for_city($city) {
    static $map = [
        'paris'        => ['Eiffel Tower', 'Louvre Museum', 'Notre-Dame Cathedral'],
        'london'       => ['Big Ben', 'Tower Bridge', 'British Museum'],
        'new york'     => ['Statue of Liberty', 'Times Square', 'Central Park'],
        'tokyo'        => ['Tokyo Tower', 'Senso-ji Temple', 'Shibuya Crossing'],
        'rome'         => ['Colosseum', 'Trevi Fountain', 'Vatican Museums'],
        'cairo'        => ['Pyramids of Giza', 'Egyptian Museum', 'Khan el-Khalili'],
        'sydney'       => ['Sydney Opera House', 'Harbour Bridge', 'Bondi Beach'],
        'dubai'        => ['Burj Khalifa', 'Palm Jumeirah', 'Dubai Mall'],
        'beijing'      => ['Great Wall of China', 'Forbidden City', 'Tiananmen Square'],
        'moscow'       => ['Red Square', 'Kremlin', 'Saint Basil\'s Cathedral'],
        'rio de janeiro' => ['Christ the Redeemer', 'Copacabana Beach', 'Sugarloaf Mountain'],
        'barcelona'    => ['Sagrada Familia', 'Park Guell', 'La Rambla'],
        'istanbul'     => ['Hagia Sophia', 'Blue Mosque', 'Grand Bazaar'],
        'kolkata'      => ['Victoria Memorial', 'Howrah Bridge', 'Indian Museum'],
        'mumbai'       => ['Gateway of India', 'Marine Drive', 'Elephanta Caves'],
        'delhi'        => ['India Gate', 'Red Fort', 'Qutub Minar'],
        'agra'         => ['Taj Mahal', 'Agra Fort'],
        'san francisco' => ['Golden Gate Bridge', 'Alcatraz Island', 'Fisherman\'s Wharf'],
        'los angeles'  => ['Hollywood Sign', 'Griffith Observatory', 'Santa Monica Pier'],
        'berlin'       => ['Brandenburg Gate', 'Berlin Wall Memorial', 'Museum Island'],
        'amsterdam'    => ['Anne Frank House', 'Rijksmuseum', 'Van Gogh Museum'],
        'athens'       => ['Acropolis', 'Parthenon', 'Ancient Agora'],
        'singapore'    => ['Marina Bay Sands', 'Gardens by the Bay', 'Sentosa Island'],
        'hong kong'    => ['Victoria Peak', 'Victoria Harbour', 'Tian Tan Buddha'],
        'bangkok'      => ['Grand Palace', 'Wat Arun', 'Chatuchak Market'],
    ];

    $key = strtolower(trim($city));
    return $map[$key] ?? [];
}

/**
 * Fetches a small thumbnail image URL for a landmark from Wikipedia's
 * REST summary API. No API key required. Returns null if the page
 * doesn't exist or has no thumbnail. Cached per-request in a static
 * array since the same landmark can appear in multiple suggestions.
 */
function fetch_landmark_thumbnail($landmarkName) {
    static $cache = [];

    if (isset($cache[$landmarkName])) {
        return $cache[$landmarkName];
    }

    $url = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($landmarkName);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    // Wikimedia requires a descriptive User-Agent on REST API requests.
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: WeatherBookmarkDashboard/1.0 (educational project)']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $thumb = null;
    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['thumbnail']['source'])) {
            $thumb = $data['thumbnail']['source'];
        }
    }

    $cache[$landmarkName] = $thumb;
    return $thumb;
}

/**
 * Same as fetch_landmark_thumbnail(), but looks up several landmark
 * names at once instead of one-after-another. The famous-places
 * fallback list has up to 3 names, and each lookup had its own 5s
 * timeout — sequentially that's up to 15s just for thumbnails. Doing
 * them concurrently caps it at ~5s total instead.
 * Returns ['Landmark Name' => thumbnail url|null].
 */
function fetch_landmark_thumbnails_multi(array $names) {
    if (empty($names)) {
        return [];
    }

    $requests = [];
    foreach ($names as $name) {
        $requests[$name] = [
            'url' => 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode($name),
            'timeout' => 5,
            'headers' => ['User-Agent: WeatherBookmarkDashboard/1.0 (educational project)'],
        ];
    }

    $results = http_get_json_multi($requests);

    $thumbs = [];
    foreach ($results as $name => $data) {
        $thumbs[$name] = $data['thumbnail']['source'] ?? null;
    }
    return $thumbs;
}

/**
 * Finds notable Wikipedia pages (landmarks, attractions, etc.) near a
 * lat/lon using MediaWiki's geosearch, together with thumbnails, in a
 * single request. Works for essentially any coordinates worldwide,
 * not just the cities in famous_places_for_city(). Returns an array
 * of ['title' => ..., 'thumbnail' => url|null] or [] on failure.
 */
function wikipedia_landmarks_near($lat, $lon, $limit = 6) {
    $url = 'https://en.wikipedia.org/w/api.php'
        . '?action=query'
        . '&generator=geosearch'
        . '&ggscoord=' . urlencode($lat . '|' . $lon)
        . '&ggsradius=10000'
        . '&ggslimit=' . (int) $limit
        . '&prop=pageimages'
        . '&piprop=thumbnail'
        . '&pithumbsize=300'
        . '&format=json';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: WeatherBookmarkDashboard/1.0 (educational project)']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return [];
    }

    $data = json_decode($response, true);
    $pages = $data['query']['pages'] ?? [];
    if (!$pages) {
        return [];
    }

    // geosearch doesn't guarantee order across the 'pages' map, so
    // resort by the original geosearch distance if present.
    $ordered = array_values($pages);
    usort($ordered, function ($a, $b) {
        return ($a['index'] ?? 0) <=> ($b['index'] ?? 0);
    });

    $places = [];
    foreach ($ordered as $page) {
        $places[] = [
            'title'     => $page['title'],
            'thumbnail' => $page['thumbnail']['source'] ?? null,
        ];
    }

    return $places;
}

/**
 * Fetches current weather + AQI for a city name using Open-Meteo
 * (geocoding -> forecast -> air-quality). Returns an associative
 * array on success, or null on failure.
 */
function fetch_weather($city) {
    $location = geocode_city($city);
    if ($location === null) {
        return null;
    }
    return fetch_weather_for_location($location);
}

/**
 * Same as fetch_weather(), but skips the geocoding step because the
 * caller already has an exact lat/lon (e.g. the user picked one
 * specific match from the location-suggestions dropdown, so there's
 * no ambiguity left to resolve and no need to spend a network call
 * re-discovering what they already told us).
 *
 * $location needs: name, country, latitude, longitude.
 */
function fetch_weather_for_location(array $location) {
    $lat = $location['latitude'];
    $lon = $location['longitude'];

    $weatherUrl = FORECAST_BASE_URL . '?latitude=' . $lat . '&longitude=' . $lon
        . '&current=temperature_2m,weather_code&timezone=auto';
    $aqiUrl = AIR_QUALITY_BASE_URL . '?latitude=' . $lat . '&longitude=' . $lon . '&current=us_aqi';

    // Forecast and AQI don't depend on each other, so fetch them at the
    // same time. Previously these ran back-to-back (up to 10s + 8s = 18s
    // added on top of the geocode call), which is the main reason
    // "Adding city..." could sit there for a long time.
    $results = http_get_json_multi([
        'weather' => ['url' => $weatherUrl, 'timeout' => 10],
        'aqi'     => ['url' => $aqiUrl, 'timeout' => 8],
    ]);

    $weatherData = $results['weather'];
    if (!isset($weatherData['current']['temperature_2m'])) {
        return null;
    }

    [$desc, $icon] = weather_code_to_info($weatherData['current']['weather_code'] ?? -1);

    // AQI is best-effort: if it fails, we still return the weather.
    $aqi = null;
    $aqiData = $results['aqi'];
    if (isset($aqiData['current']['us_aqi'])) {
        $aqi = (int) $aqiData['current']['us_aqi'];
    }

    return [
        'city_name'    => $location['name'],
        'country'      => $location['country'],
        'latitude'     => $lat,
        'longitude'    => $lon,
        'temperature'  => $weatherData['current']['temperature_2m'],
        'weather_desc' => $desc,
        'weather_icon' => $icon,
        'aqi'          => $aqi,
        'aqi_category' => aqi_to_category($aqi),
    ];
}
