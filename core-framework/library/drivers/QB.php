<?php

class QB extends CoreService {

  const OP_AND        = 0;
  const OP_OR         = 1;
  const INSERT_IGNORE = 2;

  const ORDER_ASC  = 10;
  const ORDER_DESC = 11;
  const ORDER_RAND = 12;

  const COMMAND_TYPE_SELECT                    = 30;
  const COMMAND_TYPE_INSERT                    = 31;
  const COMMAND_TYPE_INSERT_UPDATE             = 32;
  const COMMAND_TYPE_UPDATE                    = 33;
  const COMMAND_TYPE_DELETE                    = 34;
  const COMMAND_TYPE_INSERT_MULTIVALUES        = 35;
  const COMMAND_TYPE_DELETE_MULTIVALUES        = 36;
  const COMMAND_TYPE_INSERT_UPDATE_MULTIVALUES = 37;
  const COMMAND_TYPE_RAW                       = 90;

  const DRIVER_MYSQL = 20;

  const CLEAR_DEFAULT          = 80;
  const CLEAR_CONNECTION_CACHE = 81;

  protected $_table;
  protected $_commandType;
  protected $_command;
  protected $_distinct;
  protected $_columns;
  protected $_updateColumns;
  protected $_values;
  protected $_multiValues;
  protected $_columnValues;
  protected $_uColumnValues;
  protected $_join;
  protected $_joinAliases = array();
  protected $_where       = array();
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
    $this->table($table);
    $this->setKey($dbConfigKeyOrDb);
  }

  public function setKey($dbConfigKeyOrDb) {
    $this->_dbConfigKeyOrDb = $dbConfigKeyOrDb;
  }

  public function setData($_args = array()) {
    $this->_args = $_args;
    return $this;
  }

  public function getData() {
    return $this->_args;
  }

  public function table($table = null) {
    if ($table instanceof QBRaw) {
      $this->_table = $table->raw;
    } elseif ($table !== null) {
      $this->_table = QB::bt($table);
    }

    return $this;
  }

  // Getter

  public function get() { // get SQL query string

    if (
      $this->_table === null
      and $this->_commandType !== QB::COMMAND_TYPE_RAW
    ) {
      throw new Exception("Table name is unspecified");
    }

    switch ($this->_commandType) {
    case QB::COMMAND_TYPE_SELECT:
      $this->_command = "SELECT";
      $this->_command .=
      ($this->_distinct) ? " DISTINCT" : "";
      $this->_sql = $this->_command . " " . $this->_columns . " "
      . "FROM " . $this->_table . " ";
      break;
    case QB::COMMAND_TYPE_INSERT:
      $this->_table    = preg_replace('/\ .+$/i', '', $this->_table);
      $this->_command  = "INSERT " . ($this->_ignore ? "IGNORE " : "");
      $this->_scolumns = array_map(array('QB', 'bt'), $this->_columns);
      $this->_svalues  = array_map(array('QB', 'qt'), $this->_values);
      $this->_sql      = $this->_command . "INTO " . $this->_table . " "
      . "( " . implode(", ", $this->_scolumns) . " ) VALUES ( " . implode(", ", $this->_svalues) . " ) ";
      break;
    case QB::COMMAND_TYPE_INSERT_UPDATE:
      $this->_table    = preg_replace('/\ .+$/i', '', $this->_table);
      $this->_command  = "INSERT " . ($this->_ignore ? "IGNORE " : "");
      $this->_scolumns = array_map(array('QB', 'bt'), $this->_columns);
      $this->_svalues  = array_map(array('QB', 'qt'), $this->_values);
      $this->_sql      = $this->_command . "INTO " . $this->_table . " "
      . "( " . implode(", ", $this->_scolumns) . " ) VALUES ( " . implode(", ", $this->_svalues) . " ) "
      . "ON DUPLICATE KEY UPDATE " . $this->_uColumnValues;
      break;
    case QB::COMMAND_TYPE_INSERT_MULTIVALUES:
      $this->_table    = preg_replace('/\ .+$/i', '', $this->_table);
      $this->_command  = "INSERT " . ($this->_ignore ? "IGNORE " : "");
      $this->_scolumns = array_map(array('QB', 'bt'), $this->_columns);
      $this->_sql      = $this->_command . " INTO " . $this->_table . " "
      . "( " . implode(", ", $this->_scolumns) . " ) VALUES ";
      $mvs = array();
      foreach ($this->_multiValues as $mv) {
        $mvs[] = array_map(array('QB', 'qt'), $mv);
      }
      $vs = array();
      foreach ($mvs as $mv) {
        $vs[] = "( " . implode(", ", $mv) . " )";
      }
      // implode columns
      $this->_sql .= implode(", ", $vs); // implode rows
      break;
    case QB::COMMAND_TYPE_INSERT_UPDATE_MULTIVALUES:
      $this->_table    = preg_replace('/\ .+$/i', '', $this->_table);
      $this->_command  = "INSERT " . ($this->_ignore ? "IGNORE " : "");
      $this->_scolumns = array_map(array('QB', 'bt'), $this->_columns);
      $this->_sql      = $this->_command . " INTO " . $this->_table . " "
      . "( " . implode(", ", $this->_scolumns) . " ) VALUES ";
      $mvs = array();
      foreach ($this->_multiValues as $mv) {
        $mvs[] = array_map(array('QB', 'qt'), $mv);
      }
      $vs = array();
      foreach ($mvs as $mv) {
        $vs[] = "( " . implode(", ", $mv) . " )";
      }
      // implode columns
      $this->_sql .= implode(", ", $vs); // implode rows
      $this->_sql .= " ON DUPLICATE KEY UPDATE " . $this->_updateColumns;
      break;
    case QB::COMMAND_TYPE_DELETE:
      // $this->_table   = preg_replace('/\ .+$/i', '', $this->_table);
      $alias = array();
      preg_match('/^(.+)\ (.+)/i', $this->_table, $table);
      if (isset($table[2])) {
        $alias[] = $table[2];
      }

      foreach ($this->_joinAliases as $a) {
        $alias[] = QB::bt($a);
      }
      if (!empty($this->_join)) {
        preg_match('/^(.+)\ (.+)/i', $this->_join, $table);
      }
      $this->_command = "DELETE";
      if (count($alias) >= 1) {
        $this->_command .= " " . implode(",", $alias) . " ";
      }

      $this->_sql = $this->_command . " FROM " . $this->_table . " ";
      break;
    case QB::COMMAND_TYPE_DELETE_MULTIVALUES:
      // $this->_table   = preg_replace('/\ .+$/i', '', $this->_table);
      $this->_command  = "DELETE";
      $this->_scolumns = array_map(array('QB', 'bt'), $this->_columns);
      $this->_sql      = $this->_command . " FROM " . $this->_table . " "
      . "WHERE (" . implode(", ", $this->_scolumns) . ") IN (";
      $mvs = array();
      foreach ($this->_multiValues as $mv) {
        $mvs[] = array_map(array('QB', 'qt'), $mv);
      }
      $vs = array();
      foreach ($mvs as $mv) {
        $vs[] = "( " . implode(", ", $mv) . " )";
      }
      // implode columns
      $this->_sql .= implode(", ", $vs); // implode rows
      $this->_sql .= ")";
      break;
    case QB::COMMAND_TYPE_UPDATE:
      $this->_command = "UPDATE";
      $this->_sql     = $this->_command . " " . $this->_table . " SET "
      . $this->_columnValues . " ";
      break;
    }
    if (!empty($this->_join)) {
      $this->_sql .= $this->_join;
    }

    if (!empty($this->_where)) {
      $this->_sql .= "WHERE " . $this->_where[0];
    }

    if (!empty($this->_groupBy)) {
      $this->_sql .= "GROUP BY " . $this->_groupBy;
    }

    if (!empty($this->_having)) {
      $this->_sql .= "HAVING " . $this->_having;
    }

    if (!empty($this->_orderBy)) {
      $this->_sql .= "ORDER BY " . $this->_orderBy;
    }

    if (!empty($this->_limit)) {
      $this->_sql .= "LIMIT " . $this->_limit;
    }

    return trim($this->_sql);
  }

  public function begin() {
    $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
    $this->_dbConfigKeyOrDb :
    $this->getInstance($this->_dbConfigKeyOrDb);

    $db->begin();
    return $this;
  }

  public function commit() {
    $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
    $this->_dbConfigKeyOrDb :
    $this->getInstance($this->_dbConfigKeyOrDb);

    $db->commit();
    return $this;
  }

  public function rollback() {
    $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
    $this->_dbConfigKeyOrDb :
    $this->getInstance($this->_dbConfigKeyOrDb);

    $db->commit();
    return $this;
  }

  public function execute() {

    if ($this->_commandType != QB::COMMAND_TYPE_SELECT):

      // var_dump($this->_dbConfigKeyOrDb);

      $this->get(); // generate query from QueryBuilder
      if (!trim($this->_sql)) {
        throw new CoreError("QB::execute(): Unspecified SQL Query.");
      }

      $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
      $this->_dbConfigKeyOrDb :
      $this->getInstance($this->_dbConfigKeyOrDb);

      if (!$db instanceof IDatabase) {
        throw new CoreError("QB::execute(): Unable to get an instance of database connection.");
      }

      $result = $db->query($this->_sql);
      if (trim($error = $db->getError())) {
        throw new CoreError("QB::execute(): " . $error . " . SQL: " . $this->_sql . "");
      }

      $this->_result       = $result;
      $this->_insertId     = $db->getInsertId();
      $this->_affectedRows = $db->getAffectedRows();

    endif;

    return $this;
  }

  public function executeQuery($asObject = false) {

    if ($this->_commandType == QB::COMMAND_TYPE_SELECT):

      $this->get(); // generate query from QueryBuilder
      
      if (empty($this->_sql)) {
        throw new Exception("QB::executeQuery(): Unspecified SQL Query.");
      }

      $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
      $this->_dbConfigKeyOrDb :
      $this->getInstance($this->_dbConfigKeyOrDb);

      if (!$db instanceof IDatabase) {
        throw new Exception("QB::executeQuery(): Unable to get an instance of database connection.");
      }

      $result = $db->query($this->_sql, $asObject);

      if (trim($error = $db->getError()) != false) {
        throw new Exception("QB::executeQuery(): " . $error . " <br> SQL: <code>" . $this->_sql . "</code>");
      }

      return $result;

    endif;
  }

  public function map($modelOrClassName = null) {

    // if Command Type is COMMAND_TYPE_SELECT
    try {
      if (!is_object($modelOrClassName) && class_exists($modelOrClassName)) {
        $model = new $modelOrClassName;
      } else {
        $model = $modelOrClassName;
      }
    } catch (Exception $e) {
      throw new Exception("Unable to find " . $model . " class.");
    }

    if ($model !== null) {
      $this->_model  = $model;
      $this->_fields = QB::fields($model);
    }

    if (empty($this->_model)) {
      throw new Exception("Model::map(): Unspecified Model to map.");
    }

    $this->get(); // generate query from QueryBuilder

    if (empty($this->_sql)) {
      throw new Exception("Model::map(): Unspecified SQL Query.");
    }

    $db = ($this->_dbConfigKeyOrDb instanceof IDatabase) ?
    $this->_dbConfigKeyOrDb :
    $this->getInstance($this->_dbConfigKeyOrDb);

    if (!$db instanceof IDatabase) {
      throw new Exception("QB::executeQuery(): Unable to get an instance of database connection.");
    }

    $result = $db->query($this->_sql, false);
    if (trim($error = $db->getError()) != false) {
      throw new Exception("QB::map(): " . $error . " <br> SQL: <code>" . $this->_sql . "</code>");
    }

    $collection     = array();
    $modelClassName = get_class($this->_model);

    if ($result === null) {
      return $collection;
    }

    foreach ($result as $row) { //var_dump($row);
      $o = new $modelClassName();

      // Mapping query results to field's model
      foreach ($this->_fields as $f) { //var_dump($f);
        $o->$f = (isset($row[$f])) ? $row[$f] : null;
      }

      // Callback
      if (trim($this->_callbackFunction) != false) {
        $function = $this->_callbackFunction;
        $args     = $this->_callbackArgs;
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

  public function callback($functionName, $args = array()) {
    $this->_callbackFunction = $functionName;
    $this->_callbackArgs     = $args;
    return $this;
  }

  // Clear

  public function clear($clearType = QB::CLEAR_DEFAULT) {

    $this->_commandType   = null;
    $this->_command       = null;
    $this->_columns       = null;
    $this->_updateColumns = null;
    $this->_values        = null;
    $this->_multiValues   = null;
    $this->_columnValues  = null;
    $this->_uColumnValues = null;
    $this->_join          = null;
    $this->_joinAliases   = array();
    $this->_where         = array();
    $this->_groupBy       = null;
    $this->_having        = null;
    $this->_orderBy       = null;
    $this->_limit         = null;
    $this->_sql           = null;
    $this->_ignore        = null;
    $this->_insertId      = null;

    $this->_table      = null;
    $this->_model      = null;
    $this->_groupStack = 0;
    $this->_fields     = null;

    $this->_callbackFunction = null;
    $this->_callbackArgs     = null;

    if ($clearType == QB::CLEAR_CONNECTION_CACHE) {
      $this->_dbConfigKeyOrDb = null;
    }

    return $this;
  }

  // Static Helper functions of QueryBuilder

  public static function raw($string) {
    return new QBRaw($string);
  }

  public static function instance($dbConfigKeyOrDb, $table = null) {
    if ($dbConfigKeyOrDb instanceof IDatabase) {
      $qb = new QueryBuilderMysql($table, $dbConfigKeyOrDb);
    } else {
      $qb       = null;
      $dbConfig = self::getConfig($dbConfigKeyOrDb); // var_dump($dbConfig); die();
      if ($dbConfig) {
        $driver = property_exists($dbConfig, 'driver') ? $dbConfig->driver : 'mysqli';
        switch ($driver) {
        case 'mysql':
        case 'mysqli':
        default:
          $qb = new QueryBuilderMysql(
            $table,
            $dbConfig->config
          );
          break;
        }
      }
    }
    return $qb;
  }

  public static function bt($column) {
    
    if($column instanceof QBRaw) return $column->raw;
    
    $rColumn = preg_replace_callback(
      '/(.+?)\.(.+) as (.+$)/i',
      function ($matches) { //var_dump($matches);
        return sprintf('`%s`.`%s` AS `%s`', $matches[1], $matches[2], $matches[3]);
      },
      $column
    );
    if ($rColumn != $column) {
      return $rColumn;
    }

    $rColumn = preg_replace_callback(
      '/(.+?) as (.+)/i',
      function ($matches) { //var_dump($matches);
        return sprintf('`%s` AS `%s`', $matches[1], $matches[2]);
      },
      $column
    );
    if ($rColumn != $column) {
      return $rColumn;
    }

    $rColumn = preg_replace_callback(
      '/(.+?)\.(.+)/i',
      function ($matches) { //var_dump($matches);
        return sprintf('`%s`.`%s`', $matches[1], $matches[2]);
      },
      $column
    );
    if ($rColumn != $column) {
      return $rColumn;
    }

    $rColumn = preg_replace_callback(
      '/(.+?) (.+)/i',
      function ($matches) { //var_dump($matches);
        return sprintf('`%s` `%s`', $matches[1], $matches[2]);
      },
      $column
    );
    if ($rColumn != $column) {
      return $rColumn;
    }

    return sprintf('`%s`', $column);
  }

  public static function qt($value) {
    if ($value instanceof QBRaw) {
      return $value->raw;
    }
    if ($value === null) {
      return 'NULL';
    }

    return sprintf('\'%s\'', $value);
  }

  public static function connector($connectorType = QB::OP_AND) {
    switch ($connectorType) {
    case QB::OP_AND:
      return "AND";
    case QB::OP_OR:
      return "OR";
    }
  }

  public static function esc($value) {
    return addslashes($value);
  }

  protected static function fields($model) {
    return array_keys(get_object_vars($model)); //var_dump($attrs);
  }

  public function getInsertId() {
    return $this->_insertId;
  }

  public function getAffectedRows() {
    return $this->_affectedRows;
  }
}
