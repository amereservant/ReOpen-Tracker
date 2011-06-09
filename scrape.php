<?php
/*
 * ReopenTracker
 *
 * This is a fork of the OpenTracker project, which impliments PDO driver for SQLite
 * database support and is easily changed to MySQL if that is the preferred database.
 *
 * This application requires PHP5.
 */
header('Content-Type: text/plain; charset=utf-8');
require 'config.inc.php';
require ROT_SYSTEM_PATH .'class.reopendb.php';
require ROT_SYSTEM_PATH .'class.reopentracker.php';
require ROT_SYSTEM_PATH .'functions.reopentracker.php';
//log_request('scrape');
$rotdb = new reopen_tracker;

$rotdb->scrape();
