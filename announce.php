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
require_once 'system/functions.php';
require_once 'system/bencoding.inc.php';
require_once 'config.inc.php';
require 'db.php';

header('Content-Type: text/plain');

// validate request
validate_request();

// is the announce method allowed?
if ($require_announce_protocol == 'no_peer_id') {
	if (empty($_GET['compact']) && empty($_GET['no_peer_id'])) {
		errorexit('standard announces not allowed; use no_peer_id or compact option');
	}
}
else if ($require_announce_protocol == 'compact') {
	if (empty($_GET['compact'])) {
		errorexit('tracker requires use of compact option');
	}
}

// convert dotted decimal or host name to integer IP
$ip = resolve_ip(empty($_GET['ip']) ? $_SERVER['REMOTE_ADDR'] : $_GET['ip']);

if ($ip === false) {
	errorexit("unable to resolve host name {$_GET['ip']}");
}

// connect to database
global $otdb;
$otdb = new otdb;

// calculate announce interval
$stmt       = $otdb->query("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'");
$result     = $stmt->fetch(PDO::FETCH_NUM);
$num_peers  = $result[0];

$stmt       = $otdb->query("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE update_time > '". (time() - 60) ."'");
$result     = $stmt->fetch(PDO::FETCH_NUM);
$announce_rate = $result[0];

$announce_interval = max($num_peers * $announce_rate / ($max_announce_rate * $max_announce_rate) * 60, $min_announce_interval);

// calculate expiration time offset
if( isset($_GET['event']) && ($_GET['event'] == 'stopped') ) {
	$expire_time = 0;
}
else 
{
	$expire_time = $announce_interval * $expire_factor;
}

// insert/update peer in database
$otdb->updatePeer($_GET, $ip, $expire_time);


// retrieve peers from database
$numwant = empty($_GET['numwant']) ? 50 : intval($_GET['numwant']);

$peers   = $otdb->getPeers($_GET['info_hash'], $numwant);

// return data to client
exit(bencode(array('interval' => intval($announce_interval), 'peers' => $peers)));

