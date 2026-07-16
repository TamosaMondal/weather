<?php
/**
 * logout.php
 * -----------
 * Destroys the session and sends the user back to the login page.
 */
require_once 'includes/bootstrap.php';

$_SESSION = [];
session_destroy();

redirect('login.php');
