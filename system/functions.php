<?php
/**
 * ReOpen Tracker - Functions
 *
 * These functions provide the necessary functionality of ReOpen Tracker.
 *
 * ReOpen Tracker is a derivative of {@link http://www.whitsoftdev.com/opentracker/ Open Tracker} 
 * with the purpose of adding SQLite database support via the PHP PDO extension and
 * cleaning up the code.
 */

/**
 * 
 */
function errorexit($reason)
{
	exit(bencode(array('failure reason' => $reason)));
}

/**
 * Resolve/Validate IP
 *
 * This attempts to check the IP to see if it's valid as well as convert an IPv4 address
 * into a proper address.
 *
 * @param   string  $ip     An IPv4 IP address to validate
 * @return  
 * @since   1.0
 */
function resolve_ip($host){
	$ip = ip2long($host);
	if (($ip === false) || ($ip == -1)) {
		$ip = ip2long(gethostbyname($host));
		if (($ip === false) || ($ip == -1)) {
			return false;
		}
	}
	return $ip;
}

/**
 * Validate Request
 *
 * This validates the tracker request and makes sure it's a proper request.
 * It tests it for the request fields as outlined at {@link http://www.bittorrent.org/beps/bep_0003.html#tracker-get-requests-have-the-following-keys}
 * and if it's invalid, it will return die with an bencoded error message.
 *
 * @param   void
 * @return  bool    TRUE if successfully validated, dies if not
 * @since   1.0
 */
function validate_request()
{
    $req_keys = array('info_hash', 'port', 'peer_id', 'uploaded', 'downloaded', 'left');
    $exit_msg = 'Invalid Request.  See http://www.bittorrent.org/beps/bep_0003.html#tracker-get-requests-have-the-following-keys';
    
    foreach($req_keys as $key)
    {
        if( !isset($_GET[$key]) ) errorexit($exit_msg);
    }
    // Test that string values are not empty
    if( strlen($_GET['info_hash']) < 1 || strlen($_GET['peer_id']) < 1 )
        errorexit($exit_msg);

    // Test integer values are integers
    if( !is_numeric($_GET['port']) || !is_numeric($_GET['downloaded']) || !is_numeric($_GET['left']) ||
        !is_numeric($_GET['uploaded']) )
        errorexit($exit_msg);

    // Make sure a valid event is set if the event key isset.
    if( isset($_GET['event']) && $_GET['event'] != 'completed' && $_GET['event'] != 'started' 
        && $_GET['event'] != 'stopped' )
        errorexit($exit_msg);

    return TRUE;
}
