<?php
/*
 * ReopenTracker
 *
 * This is a fork of the OpenTracker project, which impliments PDO driver for SQLite
 * database support and is easily changed to MySQL if that is the preferred database.
 *
 * This application requires PHP5.
 */
require_once 'system/functions.php';
require_once 'system/bencoding.inc.php';
require_once 'config.inc.php';
require 'db.php';

header('Content-Type: text/plain');

// Istantiate database
global $otdb;
$otdb = new otdb;

// calculate scrape interval
$stmt       = $otdb->query("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'");
$result     = $stmt->fetch(PDO::FETCH_NUM);
$num_peers  = $result[0];

$stmt           = $otdb->query("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE update_time > '". (time() - 60) ."'");
$result         = $stmt->fetch(PDO::FETCH_NUM);
$announce_rate  = $result[0];

$scrape_interval = max($num_peers * $announce_rate / ($max_announce_rate * $max_announce_rate) * 60, $min_announce_interval) * $scrape_factor;

// determine which info hashes to scrape
if( empty($_GET['info_hash']) )
{
	$hashes = array();
	$stmt   = $otdb->query("SELECT DISTINCT info_hash FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'");
	while( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE )
	{
		$hashes[] = $row['info_hash'];
	}
}
else
{
	parse_str( str_replace('info_hash=', 'info_hash[]=', $_SERVER['QUERY_STRING']), $array );
	$hashes = $array['info_hash'];
}

// retrieve statistics for each desired info hash
$files = array();
$stmt1  = $otdb->prepare("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE info_hash = :hash AND left = 0 AND expire_time > '". time() ."'");
$stmt2  = $otdb->prepare("SELECT COUNT(*) FROM ". DB_TABLE ." WHERE info_hash = :hash AND left > 0 AND expire_time > '". time() ."'");
$stmt3  = $otdb->prepare("SELECT COUNT(*) FROM (SELECT DISTINCT ip, port FROM ". DB_TABLE ." WHERE info_hash = :hash AND left = 0)");

foreach( $hashes as $hash )
{
	$stmt1->bindParam(':hash', $hash, PDO::PARAM_STR);
	$stmt1->execute();
	$result  = $stmt1->fetch(PDO::FETCH_NUM);   
	$complete = intval($result[0]);
	
	$stmt2->bindParam(':hash', $hash, PDO::PARAM_STR);
	$stmt2->execute();
	$result     = $stmt2->fetch(PDO::FETCH_NUM);   
	$incomplete = intval($result[0]);
	
	$stmt3->bindParam(':hash', $hash, PDO::PARAM_STR);
	$stmt3->execute();
	$result     = $stmt3->fetch(PDO::FETCH_NUM);   
	$downloaded = intval($result[0]);
	
	$files[$hash] = array('complete' => $complete, 'incomplete' => $incomplete, 'downloaded' => $downloaded);
}

// return data to client
exit( bencode(array('files' => $files, 'flags' => array('min_request_interval' => intval($scrape_interval)))) );

