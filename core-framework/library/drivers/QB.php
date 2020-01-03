<?php

class QB extends CoreService {
    

    const OP_AND = 0;
    const OP_OR = 1;
    const INSERT_IGNORE = 2;

    const ORDER_ASC = 10;
    const ORDER_DESC = 11;
    const ORDER_RAND = 12;

    const COMMAND_TYPE_SELECT = 30;
    const COMMAND_TYPE_INSERT = 31;
    const COMMAND_TYPE_UPDATE = 32;
    const COMMAND_TYPE_DELETE = 33;
    const COMMAND_TYPE_INSERT_MULTIVALUES = 34;
    const COMMAND_TYPE_DELETE_MULTIVALUES = 35;
    const COMMAND_TYPE_RAW = 90;

    const DRIVER_MYSQL = 20;

    const CLEAR_QUERY_ONLY = 90;

    protected $_table;
    protected $_commandType;
    protected $_command;
    protected $_distinct;
    protected $_columns;
    protected $_values;
    protected $_multiValues;
    protected $_columnValues;
    protected $_join;
    protected $_where = array();
    protected $_groupBy;
    protected $_having;
    protected $_orderBy;
    protected $_limit;
    protected $_sql;
    protected $_ignore;
    protected $_insertId;
    protected $_affectedRows;
    protected $_result;

    protected $_model;
    protected $_groupStack = 0;
    protected $_dbConfigKeyOrDb;
    protected $_fields;

    protected $_callbackFunction;
    protected $_callbackArgs;
    protected $_mapCallbackFunction;
    protected $_mapCallbackArgs;
    
    protected $_args;

    public function __construct($table, $dbConfigKeyOrDb) {
        $this->table( $table );
        $this->setKey( $dbConfigKeyOrDb );
    }

    public function setKey( $dbConfigKeyOrDb ) {
        $this->_dbConfigKeyOrDb = $dbConfigKeyOrDb;
    }
    
    public function setData($_args = array()){
        $this->_args = $_args;
        return $this;
    }
    
    public function getData(){
        return $this->_args;
    }

    public function execute() {

        if($this->_commandType != QB::COMMAND_TYPE_SELECT) :

          // var_dump($this->_dbConfigKeyOrDb);

          $this->get(); // generate query from QueryBuilder
          if(!trim($this->_sql)) throw new CoreError("QB::execute(): Unspecified SQL Query.");

          $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ? 
              $this->_dbConfigKeyOrDb :
              $this->getInstance($this->_dbConfigKeyOrDb);

          if(!$db instanceof IDatabase)
              throw new CoreError("QB::execute(): Unable to get an instance of database connection.");

          $result = $db->query($this->_sql);
          if (trim($error = $db->getError()))
               throw new CoreError("QB::execute(): " . $error . " . SQL: " . $this->_sql . "");

          $this->_result = $result;
          $this->_insertId = $db->getInsertId();
          $this->_affectedRows = $db->getAffectedRows();
          
          return $this;


        endif;

    }

    public function executeQuery($asObject = false) {

        if($this->_commandType == QB::COMMAND_TYPE_SELECT) :
        
            $this->get(); // generate query from QueryBuilder
            if(empty($this->_sql)) throw new Exception("QB::executeQuery(): Unspecified SQL Query.");
            
            $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ? 
                $this->_dbConfigKeyOrDb :
                $this->getInstance($this->_dbConfigKeyOrDb);

            if(!$db instanceof IDatabase)
                throw new Exception("QB::executeQuery(): Unable to get an instance of database connection.");

            $result = $db->query($this->_sql, $asObject);
            
            if (trim($error = $db->getError()) != false)
                throw new Exception("QB::executeQuery(): " . $error . " <br> SQL: <code>" . $this->_sql . "</code>");
            return $result;

        endif;

    }

    public function map( $modelOrClassName = null ) {

        // if Command Type is COMMAND_TYPE_SELECT

        try {
            if( !is_object($modelOrClassName) && class_exists($modelOrClassName) )
                $model = new $modelOrClassName;
            else $model = $modelOrClassName;
        } catch(Exception $e) {
            throw new Exception("Unable to find " . $model . " class.");
        }

        if($model !== null) {
            $this->_model = $model;
            $this->_fields = QB::fields($model);
        }

        if(empty($this->_model)) 
            throw new Exception("Model::map(): Unspecified Model to map.");

        $this->get(); // generate query from QueryBuilder

        if(empty($this->_sql)) throw new Exception("Model::map(): Unspecified SQL Query.");

        $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ? 
            $this->_dbConfigKeyOrDb :
            $this->getInstance($this->_dbConfigKeyOrDb);

        if(!$db instanceof IDatabase)
            throw new Exception("QB::executeQuery(): Unable to get an instance of database connection.");

        //var_dump($this->_sql);

        $result = $db->query($this->_sql, false);
        if (trim($error = $db->getError()) != false)
            throw new Exception("QB::map(): " . $error. " <br> SQL: <code>" . $this->_sql . "</code>");


        $collection = array();
        $modelClassName = get_class($this->_model);
        
        if($result === null) return $collection;

        foreach($result as $row) { //var_dump($row);
            $o = new $modelClassName();       
            
            // Mapping query results to field's model
            foreach($this->_fields as $f) { //var_dump($f);
                $o->$f = (isset($row[$f])) ? $row[$f] : null;
            }

            // Callback
            if(trim($this->_callbackFunction) != false) {
                $function = $this->_callbackFunction;
                $args = $this->_callbackArgs;
                $o->$function($row, $args);
            }

            // Add object to collection
            $collection[] = $o;
        } 

        //var_dump($collection);
        return $collection;
    }

    public function insertId() {
        return $this->_insertId;
    }
    
    public function result() {
        return $this->_result;
    }

    public function getFields() {
        return $this->_fields;
    }

    public function callback( $functionName, $args = array() ) {
        $this->_callbackFunction = $functionName;
        $this->_callbackArgs = $args;
        return $this;
    }

    // Clear

    public function clear( $clearType = QB::CLEAR_QUERY_ONLY) {
        
        $this->_commandType = null;
        $this->_command = null;
        $this->_columns = null;
        $this->_values = null;
        $this->_multiValues = null;
        $this->_columnValues = null;
        $this->_join = null;
        $this->_where = array();
        $this->_groupBy = null;
        $this->_having = null;
        $this->_orderBy = null;
        $this->_limit = null;
        $this->_sql = null;
        $this->_ignore = null;
        $this->_insertId = null;
    
        $this->_table = null;
        $this->_model = null;
        $this->_groupStack = 0;
        $this->_dbConfigKeyOrDb = null;
        $this->_fields = null;
    
        $this->_callbackFunction = null;
        $this->_callbackArgs = null;

        return $this;
    }
    
    // Static Helper functions of QueryBuilder

    public static function raw( $string ) {
        return new QBRaw($string);
    }

    public static function instance( $dbConfigKeyOrDb, $table = null ) {
      if($dbConfigKeyOrDb instanceof IDatabase) {
        $qb = new QueryBuilderMysql( $table, $dbConfigKeyOrDb );
      } else {
        $qb = null;
        $dbConfig = self::getConfig($dbConfigKeyOrDb); // var_dump($dbConfig); die();
        if($dbConfig) {
          $driver = property_exists($dbConfig, 'driver') ? $dbConfig->driver : 'mysqli';
          switch ($driver) {
            case 'mysql':
            case 'mysqli':
            default:
              $qb = new QueryBuilderMysql( $table, 
                $dbConfig->config );
            break;
          }
        }
      }
      return $qb;
    }

    public static function bt( $column ) {
        $rColumn = preg_replace_callback(
            '/(.+?)\.(.+) as (.+$)/i',
            function ($matches) { //var_dump($matches);
                return sprintf('`%s`.`%s` AS `%s`', $matches[1], $matches[2], $matches[3]);
            },
            $column
        );
        if ($rColumn != $column) return $rColumn;
        $rColumn = preg_replace_callback(
            '/(.+?) as (.+)/i',
            function ($matches) { //var_dump($matches);
                return sprintf('`%s` AS `%s`', $matches[1], $matches[2]);
            },
            $column
        );
        if ($rColumn != $column) return $rColumn;
        $rColumn = preg_replace_callback(
            '/(.+?)\.(.+)/i',
            function ($matches) { //var_dump($matches);
                return sprintf('`%s`.`%s`', $matches[1], $matches[2]);
            },
            $column
        );
        if ($rColumn != $column) return $rColumn;
        $rColumn = preg_replace_callback(
            '/(.+?) (.+)/i',
            function ($matches) { //var_dump($matches);
                return sprintf('`%s` `%s`', $matches[1], $matches[2]);
            },
            $column
        );
        if ($rColumn != $column) return $rColumn;
        return sprintf('`%s`', $column);
    }

    public static function qt( $value ) {
        if($value instanceof QBRaw) return $value->raw;
        return sprintf('\'%s\'', $value);
    }

    public static function connector( $connectorType = QB::OP_AND ) {
        switch($connectorType) {
            case QB::OP_AND :
                return "AND";
            case QB::OP_OR :
                return "OR";
        }
    }

    public static function esc( $value ) {
        return addslashes($value);
    }

    protected static function fields( $model ) {
        return array_keys(get_object_vars($model)); //var_dump($attrs);
    }

    public function getInsertId() {
      return $this->_insertId;
    }

    public function getAffectedRows() {
      return $this->_affectedRows;
    }

}
