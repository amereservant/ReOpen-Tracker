<?php
/*
 * ReopenTracker
 *
 * This is a fork of the OpenTracker project, which impliments PDO driver for SQLite
 * database support and is easily changed to MySQL if that is the preferred database.
 *
 * This application requires PHP5.
 *
 * @since  1.0.0
 */
file_put_contents('req.txt', print_r($_SERVER, true));
require 'config.inc.php';
require ROT_SYSTEM_PATH .'class.reopendb.php';
require ROT_SYSTEM_PATH .'class.reopentracker.php';
require ROT_SYSTEM_PATH .'functions.reopentracker.php';

header('Content-Type: text/plain');
$rotdb = new reopen_tracker;
file_put_contents('req.txt', print_r($_REQUEST, true));
$rotdb->announce();
