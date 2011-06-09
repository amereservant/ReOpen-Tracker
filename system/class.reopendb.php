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
 * @version     1.0.2
 * @author      David Miles <david@amereservant.com>
 * @link        https://github.com/amereservant/ReOpen-Tracker ReOpenTracker @ GitHub
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-ShareAlike 3.0 Unported
 * @todo        Can the database calls be minimalized and made to itterate over a property
 *              value of a single database call?
 */
class reopen_db extends PDO
{
   /**
    * Number of Seeders
    *
    * @var      integer
    * @access   protected
    * @since    1.0.1
    */
    protected $seeders = NULL;
    
   /**
    * Number of Leechers
    *
    * @var      integer
    * @access   protected
    * @since    1.0.1
    */
    protected $leechers = NULL;
    
   /**
    * Database Driver
    *
    * @var      string
    * @access   protected
    * @since    1.0.1
    */
    protected $driver;
   
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
                
                parent::__construct( 'mysql:dbname='. DB_NAME .';host='. DB_HOST, DB_USER, DB_PASS,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) );
                
                $this->driver = 'mysql';
            }
            else
            // Must be using SQLite..
            {
                if( !in_array('sqlite', $drivers) )
                    errorexit('The PDO extension does not have the sqlite driver!');
                    
                parent::__construct('sqlite:' . SQLITE_FILE, '', '', 
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                
                $this->driver = 'sqlite';
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
        // Try to update it first
        $sql = "UPDATE ". DB_TABLE ." SET peer_id=:peer_id, " .
               "uploaded=:uploaded, downloaded=:downloaded, remaining=:left, update_time=:update, " .
               "expire_time=:expire WHERE info_hash=:hash AND port=:port AND ip=:ip";
        $row_count = false;
        $time      = time();
        $expire    = $time + $expire_time;
        $str       = PDO::PARAM_STR;
        $num       = PDO::PARAM_INT;
        $values = array(
                    array('key' => ':hash',       'value' => $data['info_hash'], 'type' => $str),
                    array('key' => ':ip',         'value' => $ip,                'type' => $num),
                    array('key' => ':port',       'value' => $data['port'],      'type' => $num),
                    array('key' => ':peer_id',    'value' => $data['peer_id'],   'type' => $str),
                    array('key' => ':uploaded',   'value' => $data['uploaded'],  'type' => $int),
                    array('key' => ':downloaded', 'value' => $data['downloaded'],'type' => $int),
                    array('key' => ':left',       'value' => $data['left'],      'type' => $int),
                    array('key' => ':update',     'value' => $time,              'type' => $int),
                    array('key' => ':expire',     'value' => $expire,            'type' => $int)
                 );
        $row_count = $this->do_prepared_statement( $sql, $values, 'count', __LINE__ );
        
        // If we didn't update it, let's create it
        if($row_count < 1)
        {
            $sql = "INSERT INTO ". DB_TABLE . " (info_hash, ip, port, peer_id, uploaded, " .
               "downloaded, remaining, update_time, expire_time) VALUES ( :hash, :ip, :port, " .
               ":peer_id, :uploaded, :downloaded, :left, :update, :expire)";
               
            $row_count = $this->do_prepared_statement( $sql, $values, 'count', __LINE__ ); 
        }
        return ($row_count !== FALSE && $row_count > 0 ? TRUE : FALSE);
    }

   /**
    * Execute A Prepared Statement
    *
    * This prepares, binds parameters, then executes the prepared statement and returns
    * the row count for how many rows were effected by the query.
    *
    * @param    string  $sql    The SQL syntax with the placeholder values to bind the
    *                           parameters to.
    * @param    array   $data   The parameters to be bound.  The array should be as follows:
    *                           <code>
    *                               array( array( 'key' => ':param1',
    *                                             'value' => 'sample',
    *                                             'length' => NULL, // Optional max-length value
    *                                             'type'   => PDO::PARAM_STR // Can be PDO::PARAM_NUM as well
    *                                           )
    *                               )
    *                             
    *                               OR (See {@see reopen_tracer::scrape()} for an example of the next)
    *                               
    *                               array( array( array( 'key' => ':param1',
    *                                                    'value' => 'sample',
    *                                                    'length' => NULL, // Optional max-length value
    *                                                    'type'   => PDO::PARAM_STR // Can be PDO::PARAM_NUM as well
    *                                             ),
    *                                             array( 'key' => ':param2',
    *                                                    'value' => 'sample2',
    *                                                    'length' => 120, // Optional max-length value
    *                                                    'type'   => PDO::PARAM_STR // Can be PDO::PARAM_NUM as well
    *                                             ),
    
    *                           </code>
    * @param    string  $return             The return type, either <b>count</b> or <b>rows</b>.
    * @param    integer $line_called_from   This parameter can be set, primarily for debugging purposes,
    *                                       by passing it the magic constant __LINE__ when calling it
    *                                       so the line the error occurs on can be easily found.
    * @return   integer                     The number of rows effected by the query.
    * @access   protected
    * @since    1.0.2
    */
    protected function do_prepared_statement( $sql, $data, $return='count', $line_called_from=0 )
    {
        $multi  = FALSE;
        $output = NULL;
        try
        {
            $stmt = $this->prepare($sql);
            foreach( $data as $val )
            {
                // If we're running multiple rows of queries on the same prepared statement ...
                if(isset($val[0]))
                {
                    $multi = TRUE;
                    foreach($val as $set)
                    {
                        if( isset($set['length']) && !is_null($set['length']) )
                            $stmt->bindParam($set['key'], $set['value'], $set['type'], $set['length']);
                        else
                            $stmt->bindParam($set['key'], $set['value'], $set['type']);
                    }
                    $stmt->execute();
                    // Make an associative array of the return values
                    if($return == 'rows')
                    {
                        while( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE )
                        {
                            $output[] = $row;
                        }
                    }
                    // Make an array of the row count affected by the last DELETE, INSERT, or UPDATE statement.
                    // SELECT queries are considered inconsistent at returning the count.
                    else
                    {
                        $output[] = $stmt->rowCount();
                    }
                }
                // Just run a single query on the prepared statement ...
                else
                {
                    if( isset($val['length']) && !is_null($val['length']) )
                        $stmt->bindParam($val['key'], $val['value'], $val['type'], $val['length']);
                    else
                        $stmt->bindParam($val['key'], $val['value'], $val['type']);
            
                }
            }
            $stmt->execute();      
            
            if($return == 'rows')
            {
                while( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE )
                {
                    $output[] = $row;
                }
            }
            else
            {
                $output = $stmt->rowCount();
            }
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage() .' LINE:'. $line_called_from);
        }
        return $output;
    }
   
   /**
    * Do RAW Query
    *
    * This allows for executing raw SQL statements where binding parameters isn't necessary.
    * You can specify whether the results should be returned as a <b>COUNT</b> result value
    * or an array of <b>rows</b> of results.
    *
    * @param    string  $sql    The SQL query to perform
    * @param    string  $type   The return type.  For COUNT queries, specify 'count',
    *                           otherwise specify 'rows' to return an array of results.
    * @return   integer|array   An integer if $type parameter is set to 'count',
    *                           or an array with any matching values if matches were found.
    * @access   protected
    * @since    1.0.2
    */
    protected function do_raw_query( $sql, $type='rows', $line_called_from )
    {
        try
        {
            $stmt = $this->query( $sql );
            
            if( $type == 'count' )
            {
                $result = $stmt->fetch( PDO::FETCH_NUM );
                return $result[0];
            }
            else
            {
                $rows = array();
                while( ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE )
                {
                    $rows[] = $row;
                }
                return $rows;
            }
        }
        catch(PDOException $e)
        {
            errorexit($e->getMessage() .' LINE: '. $line_called_from);
        }
    }
    
   /**
    * Get Peers
    *
    * This retrieves all current peers based on the $limit value.
    *
    * @param    string  $hash       The torrent SHA1 Hash to retrieve peers for.
    * @param    integer $num_to_get The number of peers to get.  Defaults to 50.
    * @return   string|array        A binary string or an array of the result data.
    * @since    1.0.0
    */
    public function getPeers( $hash, $num_to_get=50 )
    {
        $sql   = "SELECT ip, port, peer_id FROM ". DB_TABLE ." WHERE info_hash = :hash AND " .
                 "expire_time > ". time() ." LIMIT :getnum";
        $str   = PDO::PARAM_STR;
        $num   = PDO::PARAM_INT;
        
        $values = array(
                    array('key' => ':hash',   'value' => $hash,       'type' => $str),
                    array('key' => ':getnum', 'value' => $num_to_get, 'type' => $num)
                 );
        $rows   = $this->do_prepared_statement( $sql, $values, 'rows', __LINE__ );
        
        if( is_null($rows) ) return NULL;
        $peers = NULL;
        
        foreach( $rows as $row )
        {
            if( isset($_REQUEST['compact']) && strlen($_REQUEST['compact']) > 0 )
                $peers  .= pack('Nn', $row['ip'], $row['port']);

            elseif( isset($_REQUEST['no_peer_id']) && strlen($_REQUEST['no_peer_id']) > 0 )
                $peers[] = array('ip' => long2ip($row['ip']), 'port' => intval($row['port']));

            else    
                $peers[] = array('ip' => long2ip($row['ip']), 'port' => intval($row['port']), 
                                 'peer id' => $row['peer_id']);
        }
        return $peers;
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
        $sql = "SELECT COUNT(*) FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'";
        
        return $this->do_raw_query( $sql, 'count', __LINE__ );
    }
    
   /**
    * Set Number Of Seeders and Leechers
    *
    * This method ensures the number of seeders and leechers has been set and if not,
    * it will make the neccessary query to do so.
    *
    * @param    string  $hash   The info hash for the torrent we're retrieving the count for.
    * @return   bool            TRUE if successfully set
    * @access   protected
    * @since    1.0.1
    */
    protected function set_num_seeders_leechers( $hash )
    {
        if( !is_null($this->seeders) && !is_null($this->leechers) ) return TRUE;
        
        $sql      = "SELECT remaining FROM ". DB_TABLE ." WHERE info_hash=:hash";
        $seeders  = 0;
        $leechers = 0;
        $values   = array(
                        array('key' => ':hash', 'value' => $hash, 'type' => PDO::PARAM_STR)
                    );
        $rows     = $this->do_prepared_statement( $sql, $values, 'rows', __LINE__ );
        $peers    = NULL;
        
        if( !is_null($rows) )
        {
            foreach( $rows as $row )
            {
                if( $row['remaining'] != 0 )
                    $leechers++;
                else
                    $seeders++;
            }
        }
        $this->seeders  = $seeders;
        $this->leechers = $leechers;
        return TRUE;
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
    * @return   integer     The number of total current tracker peers
    * @access   protected
    * @since    1.0.1
    */
    protected function get_announce_rate()
    {
        $sql = "SELECT COUNT(*) FROM ". DB_TABLE ." WHERE update_time > '". (time() - 60) ."'";
        
        return $this->do_raw_query( $sql, 'count', __LINE__ );
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
        $sql = "SELECT DISTINCT info_hash FROM ". DB_TABLE ." WHERE expire_time > '". time() ."'";
        
        return $this->do_raw_query( $sql, 'rows', __LINE__ );
    }
}
