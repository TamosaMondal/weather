<?php
/**
 * bootstrap.php
 * --------------
 * Every page in the app includes this file FIRST.
 * It starts the session and loads config, database, and helper functions.
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
