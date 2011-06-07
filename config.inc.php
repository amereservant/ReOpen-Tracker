<?php
/*
 * ReopenTracker
 *
 * This is a fork of the OpenTracker project, which impliments PDO driver for SQLite
 * database support and is easily changed to MySQL if that is the preferred database.
 *
 * This application requires PHP5.
 *
 * @version     1.0.0
 */
 
/**
 * MySQL Database Connection Settings
 *
 * If you are using a MySQL database, fill in the information below.
 * If you wish to use an SQLite database, then leave the <b>DB_USER</b> and <b>DB_PASS</b>
 * constants set to '' and it will try to use the SQLite database instead.
 */
define( 'DB_HOST', 'localhost' );       // Database Host Name
define( 'DB_USER', '' );                // Dabase User Name
define( 'DB_PASS', '' );                // Database Password
define( 'DB_DB'  , 'reopentracker' );   // Database Name (Also used by SQLite as the filename)
define( 'DB_TABLE' , 'peers' );         // Database Table Name

// SQLITE Database Filename
define( 'SQLITE_FILE', DB_DB .'.sdb' );

/*
 * Peers should wait at least this many seconds between announcements
 */
$min_announce_interval = 900; // seconds

/*
 * Maximum desired announcements per minute for all peers combined
 * (announce interval will be increased if necessary to achieve this)
 */
$max_announce_rate = 500; // announcements per minute

/*
 * Consider a peer dead if it has not announced in a number of seconds equal
 * to this many times the calculated announce interval at the time of its last
 * announcement (must be greater than 1; recommend 1.2)
 */
$expire_factor = 1.2;

/*
 * Peers should wait at least this many times the current calculated announce
 * interval between scrape requests
 */
$scrape_factor = 0.5;

/*
 * Should we require a certain announce protocol?
 *   "standard" allows all protocols
 *   "no_peer_id" allows only no_peer_id and compact
 *   "compact" allows only compact
 */
$require_announce_protocol = 'standard';

