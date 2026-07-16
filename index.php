<?php
/**
 * index.php
 * ----------
 * Entry point of the app. Just routes the visitor
 * to the dashboard (if logged in) or the login page.
 */
require_once 'includes/bootstrap.php';

redirect(is_logged_in() ? 'dashboard.php' : 'login.php');
