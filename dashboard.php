<?php
/**
 * dashboard.php
 * --------------
 * The main app screen (only visible when logged in).
 * - Shows a search form to add ("Create") a new city.
 * - Shows a grid of the user's saved cities ("Read") with the
 *   latest temperature/AQI that was fetched from Open-Meteo.
 * - Each card lets the user rename the city ("Update") or
 *   remove it ("Delete"). A "Refresh" button re-fetches the
 *   latest weather for that one city.
 */
require_once 'includes/bootstrap.php';
require_login();

$db = getDB();
$stmt = $db->prepare('SELECT * FROM cities WHERE user_id = ? ORDER BY pinned DESC, updated_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
?>

<section class="search-section">
    <h2>Add a City</h2>
    <div class="search-layout">
        <form method="POST" action="actions/add_city.php" class="search-form" id="addCityForm">
            <div class="search-input-wrap">
                <input
                    type="text"
                    id="city_name"
                    name="city_name"
                    placeholder="e.g. London, Tokyo, New York"
                    autocomplete="off"
                    required
                >
                <ul class="suggestions-dropdown hidden" id="suggestionsDropdown"></ul>
            </div>
            <input type="hidden" id="city_lat" name="city_lat">
            <input type="hidden" id="city_lon" name="city_lon">
            <input type="hidden" id="city_country" name="city_country">
            <input type="hidden" id="city_display_name" name="city_display_name">
            <button type="submit" class="btn btn-primary" id="addCityBtn">Search &amp; Save</button>
        </form>

        <aside class="places-panel hidden" id="placesPanel">
            <h3 id="placesPanelTitle">Famous places</h3>
            <div class="places-grid" id="placesGrid"></div>
        </aside>
    </div>
</section>

<section class="cities-section">
    <h2>Your Saved Cities</h2>

    <?php if (empty($cities)): ?>
        <p class="empty-state">You haven't saved any cities yet. Search for one above to get started.</p>
    <?php else: ?>
        <div class="city-grid">
            <?php foreach ($cities as $c): ?>
                <div class="city-card<?php echo $c['pinned'] ? ' pinned-card' : ''; ?>">
                    <?php if ($c['pinned']): ?><span class="pinned-badge" title="Pinned">📌</span><?php endif; ?>
                    <div class="city-card-header">
                        <span class="weather-icon" title="<?php echo h($c['weather_desc']); ?>">
                            <?php echo h($c['weather_icon']); ?>
                        </span>
                        <div>
                            <h3 class="alias-display"><?php echo h($c['alias']); ?></h3>
                            <p class="city-sub">
                                <?php echo h($c['city_name']); ?><?php echo $c['country'] ? ', ' . h($c['country']) : ''; ?>
                            </p>
                        </div>
                    </div>

                    <p class="temp" data-temp-c="<?php echo (float) $c['temperature']; ?>"><?php echo round($c['temperature'], 1); ?>&deg;C</p>
                    <p class="desc"><?php echo h(ucfirst($c['weather_desc'])); ?></p>

                    <?php if ($c['aqi'] !== null): ?>
                        <p class="aqi-badge aqi-<?php echo strtolower(str_replace(' ', '-', h($c['aqi_category']))); ?>">
                            AQI <?php echo (int) $c['aqi']; ?> &middot; <?php echo h($c['aqi_category']); ?>
                        </p>
                    <?php endif; ?>

                    <p class="updated" data-updated="<?php echo h($c['updated_at']); ?>">Updated: <?php echo h($c['updated_at']); ?></p>

                    <!-- Update: rename / set a custom alias -->
                    <form class="alias-form hidden" method="POST" action="actions/update_city.php">
                        <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                        <input type="text" name="alias" value="<?php echo h($c['alias']); ?>" placeholder="Custom alias" required>
                        <button type="submit" class="btn btn-small btn-primary">Save</button>
                    </form>

                    <div class="city-actions">
                        <button type="button" class="btn btn-small edit-alias-btn">Rename</button>

                        <form method="POST" action="actions/pin_city.php" class="inline-form">
                            <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                            <button type="submit" class="btn btn-small">
                                <?php echo $c['pinned'] ? 'Unpin' : 'Pin'; ?>
                            </button>
                        </form>

                        <form method="POST" action="actions/refresh_weather.php" class="inline-form refresh-form">
                            <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                            <button type="submit" class="btn btn-small">Refresh</button>
                        </form>

                        <!-- Delete -->
                        <form method="POST" action="actions/delete_city.php" class="inline-form delete-form">
                            <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
