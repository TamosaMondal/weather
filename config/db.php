<?php
/**
 * db.php
 * -------
 * Opens (and if needed, creates) the SQLite database and its tables.
 * Using SQLite keeps setup simple - no MySQL server required.
 */

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            city_name TEXT NOT NULL,
            alias TEXT,
            country TEXT,
            temperature REAL,
            weather_desc TEXT,
            weather_icon TEXT,
            latitude REAL,
            longitude REAL,
            aqi INTEGER,
            aqi_category TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");

        // Migrate older databases (created before lat/lon/AQI existed)
        // by adding any missing columns.
        $existingColumns = array_column($pdo->query('PRAGMA table_info(cities)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        $newColumns = [
            'latitude'     => 'REAL',
            'longitude'    => 'REAL',
            'aqi'          => 'INTEGER',
            'aqi_category' => 'TEXT',
            'pinned'       => 'INTEGER DEFAULT 0',
        ];
        foreach ($newColumns as $column => $type) {
            if (!in_array($column, $existingColumns, true)) {
                $pdo->exec("ALTER TABLE cities ADD COLUMN $column $type");
            }
        }
    }

    return $pdo;
}
