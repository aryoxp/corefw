<?php defined('CORE') or die('Can\'t access directly!');

class CoreDatabaseMysqli implements IDatabase {

	private $dbConfig;

	private $port = 3306;
	private $client_flags = NULL;
	
	private $link;
	private $link_errno;
	private $connectionName;
	
	private $lastQuery;
	private $insertId;
	private $affectedRows;

	private $error; // error objects container
	
  public function __construct( $config, $connectionName = null ){
    $this->dbConfig = $config;
    $this->connectionName = $connectionName;
    //$this->error = new error();	
  }
  
  public function connect(){
		/*
		MySQLi has no pconnect function, 
		so that if configuration says we should use persistent connection, 
		then prepend the host with p:
		*/
		if( $this->dbConfig->persistent ){
			$this->dbConfig->host = "p:".$this->dbConfig->host;
		} 	
		
		/*
		Start the mysqli connection with given parameter 
		from specified mysqli configuration
		*/
		ob_start();
		$this->link = new mysqli(
      $this->dbConfig->host 
        . (
          $this->dbConfig->port 
          ? ":" . $this->dbConfig->port
          : ''
        ),
			$this->dbConfig->user,
			$this->dbConfig->password,
			$this->dbConfig->database
		);
		$this->lastError = ob_get_contents();
    ob_end_clean();
    
    if ($this->link->connect_errno) {
      throw new CoreError("Failed to connect to MySQL: " . $this->link->connect_error);
    }

		/*
		Put connection resource to this driver $link attribute
		*/
		$this->link_errno = $this->link->connect_errno;
		
		/*
		And return the connection resources
		*/
		return $this->link;
  }

  public function disconnect(){	
    return $this->link->close();
  }

  public function getVersion() {
		return $this->getVar( "SELECT version() AS version" );
  }
	public function getInsertId() {
		return $this->insertId;
  }
  public function getAffectedRows() {
    return $this->affectedRows;
  }
	public function getError(){
		return $this->error;
  }
  public function getLastQuery() {
    return $this->lastQuery;
  }

	// transaction sets
  public function begin(){
    $this->query("START TRANSACTION");
    $this->query("BEGIN");       
  }

  public function commit(){
    $this->query("COMMIT");
  }

  public function rollback(){
    $this->query("ROLLBACK");
  }


    
  /**
   * Main query function
   */
  public function query($sql, $asObject = true){

    if( !$this->link ) $this->connect();
    if($this->link) {
      $this->lastQuery = $sql;
      // execute query
      $result = $this->link->query($sql); // var_dump($result);
      if($result === false) {
        $this->error = $this->link->error;
        throw new CoreError($this->error);
      } else {
        // process result
        if( preg_match( "/^\(?(select|show)/i", $sql ) ) {
          $rows = array();
          do {
            if($asObject)
              $row = @$result->fetch_object();
            else $row = @$result->fetch_array();
            if($row) $rows[] = $row;
          } while ($row);
          return $rows;
        } else {
          $this->insertId = $this->link->insert_id;
          $this->affectedRows = $this->link->affected_rows;
        }
      }
      return $result;

    } else {
      // var_dump($this->link);
      $this->lastError = $this->link->connect_error;
      $this->error->database("Unable to connect to database. ".$this->lastError);       
      return false;
    }
    
  }
        
  public function getVar( $query ) {
    // var_dump($query);
		if($result = $this->query( $query )) {
			$result = (array) $result[0];
			$keys = array_keys( $result );
			return $result[$keys[0]];
    } else return NULL;
    
  }
    
  public function getRow( $query ) {	
	
    $result = $this->query( $query ); // var_dump($result);
		if( $result && count( $result ) ) {
			return $result[0];
		}
    return NULL;	
    	
  }
  	
  // functional template
  public function escape( $string ) {
    return addslashes( $string );
  }
    

		
} // End database_mysqli Class