<?php

class QueryBuilderMysql extends QB { //implements IQueryBuilder {

  public function __construct($table = null, $dbConfigKeyOrDb = null) {
    parent::__construct($table, $dbConfigKeyOrDb);
  }

  public function select($columns) {
    if ($this->_table === null) {
      throw new Exception("Table name is unspecified");
    }

    if (!empty($columns)) {
      if (is_array($columns[0])) {
        $columns[0] = array_map(array('QB', 'bt'), $columns[0]);
      } else {
        $columns = array_map(array('QB', 'bt'), $columns);
      }
    }

    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    $this->_columns .= empty($columns) ? " * " :
    (is_array($columns[0]) ? implode(", ", $columns[0]) : implode(", ", $columns));
    $this->_commandType = QB::COMMAND_TYPE_SELECT;

    return $this;
  }

  public function selectModel($modelOrClassName) {

    try {
      if (!is_object($modelOrClassName) && class_exists($modelOrClassName)) {
        $model = new $modelOrClassName;
      } else {
        $model = $modelOrClassName;
      }

    } catch (Exception $e) {
      throw new Exception("Unable to find " . $model . " class.");
    }

    if (!is_object($model) || !($model instanceof CoreModel)) {
      throw new Exception("Argument is not an object model");
    }

    $this->_model  = $model;
    $this->_fields = QB::fields($model);
    $attrs         = array_map(array('QB', 'bt'), $this->_fields);

    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    $this->_columns .= implode(", ", $attrs) . " ";
    $this->_commandType = QB::COMMAND_TYPE_SELECT;

    return $this;
  }

  public function where($column, $opValue, $value = null, $connector = QB::OP_AND) {
    $operator = ($value === null) ? "=" : $opValue;
    $value    = ($value === null) ? $opValue : $value;
    $conn     = QB::connector($connector);

    $value = ($value instanceof QBRaw) ? $value->raw : "'$value'";

    if (empty($this->_where[$this->_groupStack])) {
      $this->_where[$this->_groupStack] = '';
      $this->_where[$this->_groupStack] .= QB::bt($column) . " " . $operator . " " . $value . " ";
    } else {
      $this->_where[$this->_groupStack] .= $conn . " " . QB::bt($column) . " " . $operator . " " . $value . " ";
    }

    return $this;
  }

  public function orWhere($column, $opValue, $value = null) {
    $this->where($column, $opValue, $value, QB::OP_OR);
    return $this;
  }

  public function whereIn($column, $values = array()) {
    $values = array_map(array('QB', 'qt'), $values);
    $value  = "(" . implode(", ", $values) . ")";
    $this->where($column, "IN", QB::raw($value));
    return $this;
  }

  public function whereNotIn($column, $values = array()) {
    $values = array_map(array('QB', 'qt'), $values);
    $value  = "(" . implode(", ", $values) . ")";
    $this->where($column, "NOT IN", QB::raw($value));
    return $this;
  }

  public function whereNull($column) {
    $this->where($column, "IS", QB::raw('NULL'));
    return $this;
  }

  public function whereNotNull($column) {
    $this->where($column, "IS NOT", QB::raw('NULL'));
    return $this;
  }

  public function whereBetween($column, $min, $max) {
    $this->where($column, "BETWEEN", QB::raw("'" . $min . "' AND '" . $max . "'"));
    return $this;
  }

  public function whereNotBetween($column, $min, $max) {
    $this->where($column, "NOT BETWEEN", QB::raw("'" . $min . "' AND '" . $max . "'"));
    return $this;
  }

  public function whereColumn($columnLeft, $opColumnRight, $columnRight = null) {
    $operator    = ($columnRight === null) ? "=" : $opColumnRight;
    $columnRight = ($columnRight === null) ? $opColumnRight : $columnRight;
    $this->where($columnLeft, $operator, QB::raw(QB::bt($columnRight)));
    return $this;
  }

  public function whereGroup($qb, $connector = QB::OP_AND) {
    if (!empty($this->_where[$this->_groupStack])) {
      $this->_where[$this->_groupStack] .= QB::connector($connector) . " ";
    } else {
      $this->_where[$this->_groupStack] = null;
    }

    $this->_where[$this->_groupStack] .= "( ";
    $this->_groupStack++;
    $qb($this);
    $this->_groupStack--;
    $this->_where[$this->_groupStack] .= $this->_where[$this->_groupStack + 1];
    unset($this->_where[$this->_groupStack + 1]);
    $this->_where[$this->_groupStack] .= " ) ";
    return $this;
  }

  public function whereExists($qb, $connector = QB::OP_AND) {
    if (!empty($this->_where[$this->_groupStack])) {
      $this->_where[$this->_groupStack] .= QB::connector($connector) . " ";
    } else {
      $this->_where[$this->_groupStack] = null;
    }

    $this->_where[$this->_groupStack] .= "EXISTS ( ";
    $this->_groupStack++;
    $qb($this);
    $this->_groupStack--;
    $this->_where[$this->_groupStack] .= $this->_where[$this->_groupStack + 1];
    $this->_where[$this->_groupStack] .= " ) ";
    return $this;
  }

  public function groupBy($columns = array()) {
    $columns = array_map(array('QB', 'bt'), $columns);
    $this->_groupBy .= "( ";
    $this->_groupBy .= implode(", ", $columns);
    $this->_groupBy .= " ) ";
    return $this;
  }

  public function having($column, $opValue, $value, $connector = QB::OP_AND) {
    $this->_command     = "SELECT";
    $this->_commandType = QB::COMMAND_TYPE_SELECT;
    if (!empty($this->_having)) {
      $this->_having .= QB::connector(QB::OP_AND) . " ";
    }

    $this->_having .= QB::bt($column) . " " . $opValue . " " . QB::qt($value);
    return $this;
  }

  public function orHaving($column, $opValue, $value) {
    $this->having($column, $opValue, $value, QB::OP_OR);
    return $this;
  }

  // Insert

  public function insert($columnValues, $ignore = false) {
    if ($this->_table === null) {
      throw new Exception("Table name is unspecified");
    }

    $this->_ignore = $ignore;
    foreach ($columnValues as $c => $v) {
      $this->_columns[] = $c;
      $this->_values[]  = $v;
    }
    $this->_commandType = QB::COMMAND_TYPE_INSERT;
    return $this;
  }

  public function insertIgnore($columnValues) {
    $this->insert($columnValues, true);
    return $this;
  }

  public function insertUpdate($columnValues, $updateColumnValues) {
    if ($this->_table === null) {
      throw new Exception("Table name is unspecified");
    }

    foreach ($columnValues as $c => $v) {
      $this->_columns[] = $c;
      $this->_values[]  = $v;
    }

    $ucv = array();
    if (is_a($updateColumnValues, 'QBRaw')) {
      $ucv[] = $updateColumnValues->raw;
    } else {
      if (!empty($this->_uColumnValues)) {
        $this->_uColumnValues .= ", ";
      }
      foreach ($updateColumnValues as $c => $v) {
        $ucv[] = QB::bt($c) . " = " . QB::qt($v) . "";
      }
    }
    $this->_uColumnValues .= implode(", ", $ucv);

    $this->_commandType = QB::COMMAND_TYPE_INSERT_UPDATE;
    return $this;
  }

  public function insertModel($models, $columns = array()) {

    if (is_array($models) and count($models) == 0) {
      throw new CoreError("Error: Empty models.");
    }
    if (!empty($columns)) {
      foreach ($columns as $c) {
        $this->_columns[] = $c;
      }
    } else if (empty($columns)) {
      if (is_array($models)) {
        $columns = QB::fields($models[0]);
      } else {
        $columns = QB::fields($models);
      }

      foreach ($columns as $c) {
        $this->_columns[] = $c;
      }
    }

    $this->_multiValues = array();
    if (is_array($models)) {
      foreach ($models as $model) {
        $values = array();
        foreach ($columns as $c) {
          $values[$c] = $model->$c;
        }

        $this->_multiValues[] = $values;
      }
    } else {
      $values = array();
      foreach ($columns as $c) {
        $values[$c] = $models->$c;
      }

      $this->_multiValues[] = $values;
    }

    $this->_commandType = QB::COMMAND_TYPE_INSERT_MULTIVALUES;
    return $this;

  }

  public function ignore() {
    $this->_ignore = true;
    return $this;
  }

  // Delete

  public function delete() {
    $this->_commandType = QB::COMMAND_TYPE_DELETE;
    return $this;
  }

  public function deleteModel($models, $keys = array()) {

    $this->_multiValues = array();

    if (is_array($models) && count($models)) {
      $model          = $models[0];
      $this->_columns = $model->getKey();
      foreach ($models as $model) { //var_dump($model);
        $keyValue = array();
        foreach ($this->_columns as $kc) {
          $keyValue[$kc] = $model->$kc;
        }

        $this->_multiValues[] = $keyValue; //var_dump($keyValue);
      }
    } else {
      $this->_columns = $models->getKey();
      $keyValue       = array();
      foreach ($this->_columns as $kc) {
        $keyValue[$kc] = $models->$kc;
      }

      $this->_multiValues[] = $keyValue; //var_dump($keyValue);
    }

    $this->_commandType = QB::COMMAND_TYPE_DELETE_MULTIVALUES;
    return $this;
  }

  // Update

  public function update($columnValues) {
    $cv = array();
    if (is_a($columnValues, 'QBRaw')) {
      $cv[] = $columnValues->raw;
    } else {
      if (!empty($this->_columnValues)) {
        $this->_columnValues .= ", ";
      }

      foreach ($columnValues as $c => $v) {
        if($v === null) $cv[] = QB::bt($c) . " = NULL";
        else $cv[] = QB::bt($c) . " = " . QB::qt($v) . "";
      }
    }
    $this->_columnValues .= implode(", ", $cv);
    $this->_commandType  = QB::COMMAND_TYPE_UPDATE;
    return $this;

  }

  public function updateModel($model, $fields = array()) {

    $columns = QB::fields($model);
    $keys    = $model->getKey();
    $columns = array_diff($columns, $keys);
    if (!empty($fields)) {
      $columns = array_intersect($columns, $fields);
    }

    $columnValues = array();
    foreach ($columns as $c) {
      $columnValues[$c] = $model->$c;
    }

    $this->update($columnValues);
    foreach ($keys as $key) {
      $this->where($key, $model->$key);
    }

    $this->_commandType = QB::COMMAND_TYPE_UPDATE;
    return $this;

  }

  // Ordering

  public function orderBy($column, $order = QB::ORDER_ASC) {
    if (!empty($this->_orderBy)) {
      $this->_orderBy .= ", ";
    }

    $o = "";
    switch ($order) {
    case QB::ORDER_ASC:$o = "ASC";
      break;
    case QB::ORDER_DESC:$o = "DESC";
      break;
    case QB::ORDER_RAND:$o = "RAND()";
      break;
    }
    if($order == QB::ORDER_RAND) $this->_orderBy .= $o . " ";
    else $this->_orderBy .= $column . " " . $o . " ";
    return $this;
  }

  // Select

  public function distinct() {
    $this->_distinct = true;
    return $this;
  }

  public function addSelect($columns = array()) {
    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    $columns = array_map(array('QB', 'bt'), $columns);
    $this->_columns .= implode(", ", $columns);
    $this->_commandType = QB::COMMAND_TYPE_SELECT;
    return $this;
  }

  // Aggregates

  public function max($column) {
    $this->_commandType = QB::COMMAND_TYPE_SELECT;
    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    $this->_columns .= "MAX( " . QB::bt($column) . " ) AS `max`";
    return $this;
  }

  public function count($column = "*") {
    $this->_commandType = QB::COMMAND_TYPE_SELECT;
    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    $this->_columns .= "COUNT( " . QB::bt($column) . " ) AS `count`";
    return $this;
  }

  // Raw

  public function queryRaw($sql, $paramValues = array()) {
    $this->_commandType = QB::COMMAND_TYPE_RAW;
    $sql                = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_sql .= $sql;
    return $this;
  }

  public function selectRaw($sql, $paramValues = array()) {
    $this->_commandType = QB::COMMAND_TYPE_SELECT;
    if (!empty($this->_columns)) {
      $this->_columns .= ", ";
    }

    // "SELECT ?, ?", ['a', 'b']
    // "SELECT a, b"
    $sql = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_columns .= $sql;
    return $this;
  }

  public function whereRaw($sql, $paramValues = array()) {
    $connector = QB::OP_AND;
    if (!empty($this->_where[$this->_groupStack])) {
      $this->_where[$this->_groupStack] .= QB::connector($connector) . " ";
    } else {
      $this->_where[$this->_groupStack] = null;
    }

    $sql = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_where[$this->_groupStack] .= $sql;
    return $this;
  }

  public function orWhereRaw($sql, $paramValues = array()) {
    if (!empty($this->_where[$this->_groupStack])) {
      $this->_where[$this->_groupStack] .= QB::connector(QB::OP_OR) . " ";
    } else {
      $this->_where[$this->_groupStack] = null;
    }

    $sql = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_where[$this->_groupStack] .= $sql;
    return $this;
  }

  public function havingRaw($sql, $paramValues = array()) {
    if (!empty($this->_having)) {
      $this->_having .= QB::connector(QB::OP_AND) . " ";
    }

    $sql = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_having .= $sql;
    return $this;
  }

  public function orHavingRaw($sql, $paramValues = array()) {
    if (!empty($this->_having)) {
      $this->_having .= QB::connector(QB::OP_OR) . " ";
    }

    $sql = preg_replace_callback('/\?/',
      function ($match) use (&$paramValues) {
        return array_shift($paramValues); // wrap in quotes and sanitize
      }, $sql);
    $this->_having .= $sql;
    return $this;
  }

  // Join

  public function join($table, $leftColumnOrKeyPairs, $rightColumn = null, $operator = "=") {
    preg_match('/^(.+)\ (.+)$/', $table, $match);
    $this->_joinAliases[] = $match[2];
    if (is_array($leftColumnOrKeyPairs)) {
      $pairs = array();
      foreach ($leftColumnOrKeyPairs as $l => $r) {
        $pairs[] = QB::bt($l) . " " . $operator . " " . QB::bt($r);
      }
      $this->_join .= "JOIN " . QB::bt($match[1])
      . " ON " . implode(" AND ", $pairs);
      return $this;
    }

    $this->_join .= "JOIN " . QB::bt($match[1])
    . " ON " . QB::bt($leftColumnOrKeyPairs) . " " . $operator . " " . QB::bt($rightColumn) . " ";
    return $this;
  }

  public function leftJoin($table, $leftColumnOrKeyPairs, $rightColumn = null, $operator = "=") {
    preg_match('/^(.+)\ (.+)$/', $table, $match);
    $this->_joinAliases[] = $match[2];
    if (is_array($leftColumnOrKeyPairs)) {
      $pairs = array();
      foreach ($leftColumnOrKeyPairs as $l => $r) {
        $pairs[] = QB::bt($l) . " " . $operator . " " . QB::bt($r);
      }
      $this->_join .= "LEFT JOIN " . QB::bt($match[1] 
      . ($match[2] ? " " . $match[2] : ''))
      . " ON " . implode(" AND ", $pairs) . " ";
      return $this;
    }

    $this->_join .= "LEFT JOIN " . QB::bt($match[1]
    . ($match[2] ? " " . $match[2] : ''))
    . " ON " . QB::bt($leftColumnOrKeyPairs) . " " . $operator . " " . QB::bt($rightColumn) . " ";
    return $this;
  }

  public function crossJoin($table) {
    preg_match('/^(.+)\ (.+)$/', $table, $match);
    $this->_joinAliases[] = $match[2];
    $this->_join .= "CROSS JOIN " . QB::bt($match[1]);
    return $this;
  }

  // Pagination

  public function limit($offsetOrLimit, $limit = null) {
    $this->_limit = ($limit === null) ? $offsetOrLimit : $offsetOrLimit . ", " . $limit;
    return $this;
  }

  public function page($page, $perPage = 25) {
    $this->_limit = (($page - 1) * $perPage) . ", " . $perPage;
    return $this;
  }

}
