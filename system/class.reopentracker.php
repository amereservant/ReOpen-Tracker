<?php
/**
 * ReOpenTracker - Bittorrent Tracker
 *
 * This class serves the core functionality of the ReOpenTracker application.
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
 * @version     1.0.2
 * @author      David Miles <david@amereservant.com>
 * @link        https://github.com/amereservant/ReOpen-Tracker ReOpenTracker @ GitHub
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported
 */
class reopen_tracker extends reopen_db
{
  /**
   * Announce Interval
   *
   * @var       integer
   * @access    private
   * @since     1.0.1
   */
   private $_announce_interval; 
   
   /**
    * Scrape Interval
    *
    * @var      integer
    * @access   private
    * @since    1.0.1
    */
    private $_scrape_interval;
    
   /**
    * Class Constructor
    *
    * @param    void
    * @access   public
    */
    public function __construct()
    {
        parent::__construct();
    }
    
   /**
    * Announce
    *
    * This method takes care of all of the <b>Announce</b> page logic for the tracker.
    * For more details, see {@link http://wiki.theory.org/BitTorrentSpecification#Tracker_HTTP.2FHTTPS_Protocol}
    * for a complete list of request and response parameters.
    *
    * @param    void
    * @return   string  Outputs the bencode-encoded string of either the error or torrent
    *                   tracker info.
    * @access   public
    * @since    1.0.1
    */
    public function announce()
    {
        // Validate the incomming request
        validate_request();
        
        // Check if the announce method is allowed
        if( REQUIRE_ANNOUNCE_PROTOCOL == 'no_peer_id' )
        {
            if( !isset($_GET['compact']) || strlen($_GET['compact']) < 1 ||
                !isset($_GET['no_peer_id']) || strlen($_GET['no_peer_id']) < 1 )
                errorexit('Standard announces not allowed; use no_peer_id or compact option');
        }
        elseif( REQUIRE_ANNOUNCE_PROTOCOL == 'compact' )
        {
            if( !isset($_GET['compact']) || strlen($_GET['compact']) < 1 )
                errorexit('Tracker requires use of compact option.');
        }
        
        $getip = empty($_GET['ip']) ? $_SERVER['REMOTE_ADDR'] : $_GET['ip'];
        $ip    = resolve_ip( $getip );
        
        // This usually tests true when testing on localhost
        if( $ip === FALSE )
            errorexit('Unable to resolve host name '. $getip);
        
        $expire_time = $this->get_expire_time();
        // Update/insert peer details in database
        $this->updatePeer( $_GET, $ip, $expire_time );
        
        // Retrieve peers from the database for the requested torrent
        $numwant = empty($_GET['numwant']) ? 50 : intval($_GET['numwant']);
        $peers   = $this->getPeers( $_GET['info_hash'], $numwant );
        
        // Retrieve seeders/leechers
        $this->set_num_seeders_leechers( $_GET['info_hash'] );
        
        // _announce_interval is set by the {@link get_expire_time()} method.
        exit( bencode(array('interval'  => intval($this->_announce_interval), 
                             'peers'    => $peers,
                             'incomplete' => $this->leechers,
                             'complete'  => $this->seeders)) );
    }
    
   /**
    * Scrape Page
    *
    * This renders the data for the scrape requests.
    * For more information and a complete list of parameters rendered by the scrape page,
    * visit {@link http://wiki.theory.org/BitTorrentSpecification#Tracker_.27scrape.27_Convention}.
    *
    * @param    void
    * @return   string  bencode-encoded string containing the scrape page return parameters
    * @access   public
    * @since    1.0.0
    */
    public function scrape()
    {
        $this->get_scrape_interval();
        
        // Determine which info hashes to scrape
        if( empty($_GET['info_hash']) )
        {
	        $hashes = $this->get_current_hashes();
        }
        else
        {
	        parse_str( str_replace('info_hash=', 'info_hash[]=', $_SERVER['QUERY_STRING']), $array );
	        $hashes = $array['info_hash'];
        }
        
        // Retrieve statistics for each desired info hash
        $files = array();
        $sql   = "SELECT info_hash, remaining, expire_time FROM ". DB_TABLE ." WHERE info_hash = :hash";
        
        // Itterate over the hashes and query the database
        foreach( $hashes as $hash )
        {
	        $values[0][] = array('key' => ':hash', 'value' => $hash, 'type' => PDO::PARAM_STR);
	    }
	    
	    $complete   = 0;
        $incomplete = 0;
        $downloaded = 0;
	    foreach( $this->do_prepared_statement($sql, $values, 'rows', __LINE__) as $row )
	    {
	        if( $row['remaining'] == 0 )
	            $complete++;
	        else
	            $incomplete++;
	            
	        $downloaded++;
	        
	        $files[$row['info_hash']] = array('complete' => $complete, 
	                                          'incomplete' => $incomplete, 
	                                          'downloaded' => $downloaded );
	    }

        // return data to client
        exit( bencode(array('files' => $files, 
                            'flags' => array('min_request_interval' => intval($this->_scrape_interval))
                           )));
    }
       
   /**
    * Get Calculated Scrape Interval
    *
    * @param    void
    * @return   integer
    * @access   protected
    * @since    1.0.1
    */
    protected function get_scrape_interval()
    {
        $num_peers     = $this->get_total_peers();
        $announce_rate = $this->get_announce_rate();
        
        $this->_scrape_interval = max( $num_peers * $announce_rate / 
            (MAX_ANNOUNCE_RATE * MAX_ANNOUNCE_RATE) * 60, MIN_ANNOUNCE_INTERVAL ) * 
            SCRAPE_FACTOR;
        return $this->_scrape_interval;
    }
        
    
   /**
    * Get Calculated Expire Time
    *
    * This calculates and returns the expire time by also calculating the announce interval.
    * It will set the {@link $_announce_interval} property as well.
    *
    * @param    void
    * @return   integer     The calculated expire time
    * @access   protected
    * @since    1.0.1
    */
    protected function get_expire_time()
    {
        $num_peers     = $this->get_total_peers();
        $announce_rate = $this->get_announce_rate();
        
        $this->_announce_interval = max($num_peers * $announce_rate / 
            (MAX_ANNOUNCE_RATE * MAX_ANNOUNCE_RATE) * 60, MIN_ANNOUNCE_INTERVAL);
        
        // Calculate expiration time offset
        if( isset($_GET['event']) && ($_GET['event'] == 'stopped') )
            $expire_time = 0;
        else
            $expire_time = $this->_announce_interval * EXPIRE_FACTOR;
        
        return $expire_time;
    }
}
