<?php
if(!defined('SQLITE_FILE')) define('SQLITE_FILE', './test.sdb'); // SQLite Filename and path
class otdb extends PDO
{
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

            $stmt->bindParam( ':hash', $data['info_hash'], PDO::PARAM_STR );
            $stmt->bindParam( ':ip', $ip, PDO::PARAM_INT );
            $stmt->bindParam( ':port', $_GET['port'], PDO::PARAM_INT );
            $stmt->bindParam( ':peer_id', $_GET['peer_id'], PDO::PARAM_INT );
            $stmt->bindParam( ':uploaded', $_GET['uploaded'], PDO::PARAM_INT );
            $stmt->bindParam( ':downloaded', $_GET['downloaded'], PDO::PARAM_INT );
            $stmt->bindParam( ':left', $_GET['left'], PDO::PARAM_INT );
            $stmt->bindParam( ':update', $time, PDO::PARAM_INT );
            $stmt->bindParam( ':expire', $expire, PDO::PARAM_INT );
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

    public function get_transaction_by_id($id)
    {
        $rows = array();
        try
        {
            $stmt = $this->prepare("SELECT t.id, t.date_processed AS date, t.amount, t.client, " . 
                "t.trans_type, t.deposit_type, t.trans_check_num, t.payment_type, t.receipt, t.merchant, " .
                "t.description FROM transactions t WHERE t.id=:id ORDER BY t.date_processed ASC");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
            {
                $rows[] = $row;
            }
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            return false;
        }
        return $rows;
    }

    public function get_transaction_by_client($client_id)
    {
        $rows = array();
        try
        {
            $stmt = $this->prepare("SELECT t.id, t.date_processed AS date, t.amount, t.client, c.name as client_name, " . 
                "t.trans_type, t.deposit_type, t.trans_check_num, t.payment_type, t.receipt, t.merchant, " .
                "t.description FROM clients c LEFT JOIN transactions t ON c.id=t.client WHERE c.id=:id ORDER BY t.date_processed ASC");
            $stmt->bindParam(':id', $client_id, PDO::PARAM_INT);
            $stmt->execute();
            
            while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
            {
                $rows[] = $row;
            }
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            return false;
        }
        return $rows;
    }
        
    public function get_merchants()
    {
        $stmt = $this->query("SELECT * FROM merchants");
        $rows = array();
        while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
        {
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function get_clients()
    {
        $stmt = $this->query("SELECT * FROM clients");
        $rows = array();
        while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
        {
            $rows[] = $row;
        }
        return $rows;
    }
    
    private function _check_client_exists( $name )
    {
        try
        {
            $stmt = $this->prepare("SELECT * FROM clients WHERE name=:name");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR, 120);
            $stmt->execute();
            
            $rows = array();
            while(($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
            {
                $rows[] = $row;
            }
            return count($rows) > 0;
        }
        catch(PDOException $e)
        {
            die($e->getMessage());
        }
        return false;
    }

    public function add_merchant($name, $description, $abbreviation)
    {
        try
        {
            $stmt = $this->prepare("INSERT INTO merchants(name, description, abbreviation) VALUES ".
                "(:name, :description, :abbreviation)");
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':abbreviation', $abbreviation, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch(PDOException $e)
        {
            die($e->getMessage());
        }
        return false;
    }
    
    public function make_tables()
    {
        if($this->check_table_exists( 'transactions' ) !== false) 
            die('Table `transactions` already exists!');
        if($this->check_table_exists( 'clients' ) !== false) 
            die('Table `clients` already exists!');
        if($this->check_table_exists( 'merchants' ) !== false) 
            die('Table `merchants` already exists!');
        
        $table['sql1'] = "CREATE TABLE transactions (" .
            "id INTEGER PRIMARY KEY NOT NULL, ".
            "date_processed INTEGER(10) NULL, ".
            "amount DECIMAL(7) NOT NULL, ". 
            "client INTEGER(2) NULL, ". // Names and info in another table
            "trans_type INTEGER(1) NOT NULL, ". // No table for desc.
            "deposit_type INTEGER(1) NULL, ".   // No table for desc.
            "trans_check_num VARCHAR(24) NULL, ".
            "payment_type INTEGER(1) NULL, ".   // No table for desc.
            "merchant INTEGER(2) NULL, ".       // Merchants in another table
            "receipt VARCHAR(120) NULL, ".
            "description VARCHAR(240) NULL )";
            
        $table['sql2'] = "CREATE TABLE clients (".
            "id INTEGER PRIMARY KEY NOT NULL, ".
            "name VARCHAR(120) NOT NULL, ".
            "wedding_date INTEGER(10) NOT NULL, ".
            "total_due VARCHAR(10) NOT NULL )";
        
        $table['sql3'] = "CREATE TABLE merchants (".
            "id INTEGER PRIMARY KEY NOT NULL, ".
            "name VARCHAR(120) NOT NULL, ".
            "description VARCHAR(240) NULL, ".
            "abbreviation VARCHAR(50) NULL )";
        
       foreach($table as $sql => $create)
       {
            try
            {
                $this->exec($create);
            }
            catch(PDOException $e) {
                die('Create table from `'. $sql .'` failed.');
            }
        }
        return true;
    }
    
   /**
    * Check If Table Exists
    *
    * This method is used to check if a database table already exists or not.
    *
    * @param string $table_name The name of the table to check for
    * @return   bool        'true' if it does, 'false' if it doesn't
    * @access   protected
    */
    protected function check_table_exists( $table_name )
    {
        $sql = "SELECT name FROM sqlite_master WHERE name=:name";
        try {
            $stmt = $this->prepare($sql);
            $stmt->bindParam(':name', $table_name, PDO::PARAM_STR);
            $stmt->execute();
            $count = strlen($stmt->fetchColumn()); // If count fails with MySQL, change this
        }
        catch(PDOException $e) {
            if(DEBUG) echo $e->getMessage();
            exit('DB Query Failed.');
        }
        return $count > 0;
    }
}
