<?php
/**
 * ReOpenTracker - Database
 *
 * This provides the database functionality of the ReOpenTracker application.
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
class reopen_db extends PDO
{
   /**
    * Class Constructor
    *
    * This tests for the values of <b>DB_USER</b> and <b>DB_PASS</b> to determine
    * which database driver to use, tests to see if that database driver is available,
    * then tries to istantiate the parent PDO class based on the data.
    *
    * If the {@link DB_USER} or {@link DB_PASS} constants are set, it will automatically
    * try to connect to a MySQL database, otherwise it will resort to using a SQLite one.
    *
    * @param    void
    * @return   void
    * @since    1.0.1
    * @access   public
    */
    public function __construct()
    {
        try
        {
            $drivers = PDO::getAvailableDrivers();

            // Are we using MySQL?
            if( strlen(DB_USER) > 1 && strlen(DB_PASS) > 1 )
            {
                if( !in_array('mysql', $drivers) )
                    errorexit('The PDO extension does not have the mysql driver!');
                
                parent::__construct( 'mysql:dbname='. DB_DB .';host='. DB_HOST, DB_USER, DB_PASS,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) );
            }
            else
            // Must be using SQLite..
            {
                if( !in_array('sqlite', $drivers) )
                    errorexit('The PDO extension does not have the sqlite driver!');
                    
                parent::__construct('sqlite:' . SQLITE_FILE, '', '', 
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            }
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage());
        }
    }

   /**
    * Update/Insert Peer Info
    *
    * This updates the peer info in the database.
    *
    * @param    array   $data   The data from the $_GET array.
    * @return   bool            TRUE on success, or dies on error
    * @since    1.0
    * @access   public
    */
    public function updatePeer( $data, $ip, $expire_time )
    {
        $sql = "REPLACE INTO ". DB_TABLE ." (info_hash, ip, port, peer_id, uploaded, " .
               "downloaded, left, update_time, expire_time) VALUES ( :hash, :ip, :port, " .
               ":peer_id, :uploaded, :downloaded, :left, :update, :expire)";
        $row_count = false;
        $time      = time();
        $expire    = $time + $expire_time;
        
        try
        {
            $stmt = $this->prepare($sql);

            $stmt->bindParam( ':hash',       $data['info_hash'],    PDO::PARAM_STR );
            $stmt->bindParam( ':ip',         $ip,                   PDO::PARAM_INT );
            $stmt->bindParam( ':port',       $_GET['port'],         PDO::PARAM_INT );
            $stmt->bindParam( ':peer_id',    $_GET['peer_id'],      PDO::PARAM_INT );
            $stmt->bindParam( ':uploaded',   $_GET['uploaded'],     PDO::PARAM_INT );
            $stmt->bindParam( ':downloaded', $_GET['downloaded'],   PDO::PARAM_INT );
            $stmt->bindParam( ':left',       $_GET['left'],         PDO::PARAM_INT );
            $stmt->bindParam( ':update',     $time,                 PDO::PARAM_INT );
            $stmt->bindParam( ':expire',     $expire,               PDO::PARAM_INT );
            $stmt->execute();
            $row_count = $stmt->rowCount();
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage());
        }
        
        return ($row_count !== FALSE && $row_count > 0 ? TRUE : FALSE);
    }

   /**
    * Get Peers
    *
    * This retrieves all current peers based on the $limit value.
    *
    * @param    string  $hash       The torrent SHA1 Hash to retrieve peers for.
    * @param    integer $num_to_get The number of peers to get.  Defaults to 50.
    * @return   string              A binary string of the result data.
    * @since    1.0
    */
    public function getPeers( $hash, $num_to_get=50 )
    {
        $sql   = "SELECT ip, port, peer_id FROM ". DB_TABLE ." WHERE info_hash = :hash AND " .
                 "expire_time > ". time() ." LIMIT :getnum";
        
        try
        {
            $stmt = $this->prepare($sql);

            $stmt->bindParam( ':hash', $hash, PDO::PARAM_STR );
            $stmt->bindParam( ':getnum', $num_to_get, PDO::PARAM_INT );
            $stmt->execute();
            $peers = NULL;
            
            while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
            {
                if( isset($_REQUEST['compact']) && strlen($_REQUEST['compact']) > 0 )
                    $peers .= pack('Nn', $row['ip'], $row['port']);

                elseif( isset($_REQUEST['no_peer_id']) && strlen($_REQUEST['no_peer_id']) > 0 )
                    $peers[] = array('ip' => long2ip($row['ip']), 'port' => intval($row['port']));

                else    
                    $peers[] = array('ip' => long2ip($row['ip']), 'port' => intval($row['port']), 
                                     'peer id' => $row['peer_id']);
            }
            return $peers;
        }
        catch(PDOException $e){
            errorexit($e->getMessage());
        }
        return FALSE;
    }
    
   /**
    * Get Total Number Of All Peers
    * 
    * This returns the count of ALL current peers based on the expire time being current, 
    * regardless of which torrent they are for.
    * It is used for calculating the announce interval.
    *
    * @param    void
    * @return   integer     The number of peers
    * @access   protected
    * @since    1.0.1
    */
    protected function get_total_peers()
    {
        try
        {
            $stmt   = $this->query( "SELECT COUNT(*) FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'" );
            $result = $stmt->fetch(PDO::FETCH_NUM);
            return $result[0];
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage());
        }
    }
    
   /**
    * Get Announce Rate
    *
    * This returns the count of ALL current peers based on the update time being current,
    * regardless of which torrent they are for.
    *
    * It is also used for calculating the announce interval.
    *
    * @param    void
    * @return   integer     The number of peers
    * @access   protected
    * @since    1.0.1
    */
    protected function get_announce_rate()
    {
        try
        {
            $stmt = $this->query( "SELECT COUNT(*) FROM ". DB_TABLE ." WHERE update_time > '". (time() - 60) ."'" );
            $result = $stmt->fetch(PDO::FETCH_NUM);
            return $result[0];
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage());
        }
    }
    
   /**
    * Get Current Info Hashes
    *
    * This returns ALL of the current unique info hashes.
    *
    * @param    void
    * @return   array       An array of all of the current unique info hashes
    * @access   protected
    * @since    1.0.1
    */
    protected function get_current_hashes()
    {
        $hashes = array();
        try
        {
            $stmt   = $this->query( "SELECT DISTINCT info_hash FROM ". DB_TABLE .
	                  " WHERE expire_time > '". time() ."'" );
	        while( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE )
	        {
		        $hashes[] = $row['info_hash'];
	        }
	        return $hashes;
	    }
	    catch(PDOException $e)
	    {
	        errorexit($e->getMessage());
	    }
	    return $hashes;
    }
}
