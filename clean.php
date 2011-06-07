<?php
/*
 * ReopenTracker
 *
 * This is a fork of the OpenTracker project, which impliments PDO driver for SQLite
 * database support and is easily changed to MySQL if that is the preferred database.
 *
 * This application requires PHP5.
 */
require_once 'config.inc.php';
require 'db.php';

$otdb = new otdb;
// Delete records that have an expire time over 3 days old.
$otdb->query("DELETE FROM ". DB_TABLE ." WHERE expire_time < '". time() - (60 * 60 * 24 * 3) ."'");
