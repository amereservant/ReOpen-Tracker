<?php
/**
 * ReOpenTracker - Configuration
 *
 * This contains the settings for the ReOpenTracker application.
 *
 * @requires PHP5
 *
 * The ReOpenTracker is a project designed to easily be built on and implemented into
 * other projects.  
 * By itself, it provides the user with a minimalistic Bittorrent tracker but offers
 * no way of controlling torrent tracking and could be used by others to torrent
 * copywritten material without your knowledge.
 *
 * The ReOpenTracker project derived from the {@link http://www.whitsoftdev.com/opentracker/ OpenTracker}
 * which is where it gets it's name from and to whom credit is due for the base code
 * for this project.
 *
 * @category    Bittorrent
 * @package     ReOpenTracker
 * @version     1.0.1
 * @author      David Miles <david@amereservant.com>
 * @link        https://github.com/amereservant/ReOpen-Tracker ReOpenTracker @ GitHub
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported
 */
 
/**
 * MySQL Database Connection Settings
 *
 * If you are using a MySQL database, fill in the information below.
 * If you wish to use an SQLite database, then leave the <b>DB_USER</b> and <b>DB_PASS</b>
 * constants set to '' and it will try to use the SQLite database instead.
 */
// MySQL database host name
define( 'DB_HOST', 'localhost' );

// MySQL database username
define( 'DB_USER', '' );

// MySQL database password
define( 'DB_PASS', '' );

// Database Name (also used as SQLite filename)
define( 'DB_NAME'  , 'reopentracker' );

// Database Table Name
define( 'DB_TABLE' , 'peers' );

// SQLITE Database Filename
define( 'SQLITE_FILE', DB_NAME .'.sdb' );

// Peers should wait at least this many seconds between announcements
define( 'MIN_ANNOUNCE_INTERVAL', 300 );

/*
 * Maximum desired announcements per minute for all peers combined
 * (announce interval will be increased if necessary to achieve this)
 */
define( 'MAX_ANNOUNCE_RATE', 500 );

/*
 * Consider a peer dead if it has not announced in a number of seconds equal
 * to this many times the calculated announce interval at the time of its last
 * announcement (must be greater than 1; recommend 1.2)
 */
define( 'EXPIRE_FACTOR', 1.2 );

/*
 * Peers should wait at least this many times the current calculated announce
 * interval between scrape requests
 */
define( 'SCRAPE_FACTOR', 0.5 );

/*
 * Should we require a certain announce protocol?
 *   "standard"     allows all protocols
 *   "no_peer_id"   allows only no_peer_id and compact
 *   "compact"      allows only compact
 */
define( 'REQUIRE_ANNOUNCE_PROTOCOL', 'standard' );

// Abbreviate Directory Separator
if( !defined('DS') ) define( 'DS', DIRECTORY_SEPARATOR );

// Base Directory Path
define( 'ROT_PATH', realpath(dirname(__FILE__)) . DS );

// System Directory Path
define( 'ROT_SYSTEM_PATH', ROT_PATH .'system'. DS );

