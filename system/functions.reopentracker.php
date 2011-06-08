<?
/**
 * ReOpenTracker - Core Functions
 *
 * These make up the primary functions for the ReOpenTracker application.
 * These functions consist of the Bencode encoding/decoding functions, which are
 * very slightly (if any) modified from the
 * {@link http://www.whitsoftdev.com/opentracker/ OpenTracker} project so credit
 * is solely due to them for those.
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
 * Bencode Decoder
 *
 * This attempts to decode a bencode-encoded string and returns the decoded data.
 *
 * @calls    _bdecode_r()   and returns the returned value from it.
 * @param    string  $str   The bencode-encoded string to decode
 * @return   mixed
 * @access   public
 * @since    1.0.1
 */
function bdecode($str) {
	$pos = 0;
	return _bdecode_r($str, $pos);
}

/**
 * Bencode Recursive Decoder
 *
 * This itterates over the bencode-encoded data and parses the values.
 * It is called by the {@link bdecode()} function and shouldn't be called directly
 * elsewhere.
 *
 * For information on Bencode, See {@link http://en.wikipedia.org/wiki/Bencode}.
 *
 * @param    string  $str    The bencode-encoded string to decode
 * @param    int     &$pos   The current string position
 * @return   mixed           The parsed bencode-decoded data
 * @access   private
 * @since    1.0.1
 */
function _bdecode_r($str, &$pos)
{
	$strlen = strlen($str);
    if( ($pos < 0) || ($pos >= $strlen) )
    {
        return NULL;
    }
    // Decode a number (begins with i)
    elseif( $str[$pos] == 'i' )
    {
        $pos++;
        $numlen = strspn( $str, '-0123456789', $pos );
        $spos   = $pos;
        $pos   += $numlen;
        if( ($pos >= $strlen) || ($str[$pos] != 'e') )
        {
            return NULL;
        }
        else
        {
            $pos++;
            return intval( substr($str, $spos, $numlen) );
        }
    }
    
    // Decode a dictionary (begins with d)
    elseif( $str[$pos] == 'd' )
    {
        $pos++;
        $ret = array();
        while( $pos < $strlen )
        {
            if( $str[$pos] == 'e')
            {
                $pos++;
                return $ret;
            }
            else
            {
                $key = _bdecode_r($str, $pos);
                if( is_null($key) )
                {
                    return NULL;
                }
                else
                {
                    $val = _bdecode_r($str, $pos);
                    if( is_null($val) )
                        return NULL;
                    elseif( !is_array($key) ) 
                        $ret[$key] = $val;
                }
            }
        }
        return NULL;
    }
    
    // Decode a list  (begins with l)
    elseif( $str[$pos] == 'l' )
    {
        $pos++;
        $ret = array();
        while( $pos < $strlen )
        {
            if( $str[$pos] == 'e' )
            {
                $pos++;
                return $ret;
            }
            else
            {
                $val = _bdecode_r($str, $pos);
                if( is_null($val) )
                    return NULL;
                else
                    $ret[] = $val;
            }
        }
        return NULL;
    }
    else
    {
        $numlen = strspn( $str, '0123456789', $pos );
        $spos   = $pos;
        $pos   += $numlen;
        if( ($pos >= $strlen) || ($str[$pos] != ':') )
        {
            return NULL;
        }
        else
        {
            $vallen = intval( substr($str, $spos, $numlen) );
            $pos++;
            $val    = substr($str, $pos, $vallen);
            if( strlen($val) != $vallen )
            {   
                return NULL;
            }
            else
            {
                $pos += $vallen;
                return $val;
            }
        }
    }
}

/**
 * Bencode Encode Data
 *
 * This converts the input data into a bencode-encoded string and returns it.
 *
 * @param   mixed   $var    The data to encode
 * @return  string          The bencode-encoded string
 * @access  public
 * @since   1.0.0
 */
function bencode($var) {
	if (is_int($var)) {
		return 'i' . $var . 'e';
	}
	else if (is_array($var)) {
		if (count($var) == 0) {
			return 'de';
		}
		else {
			$assoc = false;
			foreach ($var as $key => $val) {
				if (!is_int($key)) {
					$assoc = true;
					break;
				}
			}
			if ($assoc) {
				ksort($var, SORT_REGULAR);
				$ret = 'd';
				foreach ($var as $key => $val) {
					$ret .= bencode($key) . bencode($val);
				}
				return $ret . 'e';
			}
			else {
				$ret = 'l';
				foreach ($var as $val) {
					$ret .= bencode($val);
				}
				return $ret . 'e';
			}
		}
	}
	else {
		return strlen($var) . ':' . $var;
	}
}

/**
 * Error Exit
 *
 * This function is used to call PHP's die() function and bencode-encode the error message
 * so that the torrent client can see why it failed.
 *
 * @param   string  $reason     The error message describing why it failed.
 * @return  void
 * @access  public
 * @since   1.0.0
 */
function errorexit( $reason )
{
    exit( bencode(array( 'failure reason' => $reason )) );
}

/**
 * Resolve/Validate IP
 *
 * This attempts to check the IP to see if it's valid as well as convert an IPv4 address
 * into a proper address.
 *
 * @param   string  $ip     An IPv4 IP address to validate
 * @return  integer         The converted IP address or FALSE on failure
 * @access  public
 * @since   1.0.0
 */
function resolve_ip( $host )
{
    $ip = ip2long($host);
    if( ($ip === false) || ($ip == -1) )
    {
	    $ip = ip2long( gethostbyname($host) );
	    if( ($ip === false) || ($ip == -1) )
	    {
		    return false;
	    }
    }
    return $ip;
}

/**
 * Validate Request
 *
 * This validates the tracker request and makes sure it's a proper request.
 * It tests it for the request fields as outlined at 
 * {@link http://www.bittorrent.org/beps/bep_0003.html#tracker-get-requests-have-the-following-keys}
 * and if it's invalid, it will return die with an bencoded error message.
 *
 * @param   void
 * @return  bool    TRUE if successfully validated, dies if not
 * @access  public
 * @since   1.0.0
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
