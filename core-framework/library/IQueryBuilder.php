<?php

interface IQueryBuilder {

    public function table( $table );

    // Select

    public function select( ...$columns );
    public function selectModel( $model );
    public function distinct();
    
    // Insert

    public function insert( $columnValues, $ignore );
    public function insertIgnore( $columnValues );
    public function insertModel( $models, ...$fields );
    public function ignore();

    // Delete

    public function delete();
    public function deleteModel( $models );

    // Update 

    public function update( $columnValues );
    public function updateModel( $model );

    // Ordering

    public function orderBy( $column, $order );

    // Criteria

    public function where( $column, $opvalue, $value );
    public function orWhere( $column, $opvalue, $value );
    public function whereIn( $column, $values );
    public function whereNotIn( $column, $values );
    public function whereNull( $column );
    public function whereNotNull( $column );
    public function whereBetween( $column, $min, $max );
    public function whereNotBetween( $column, $min, $max );
    public function whereColumn( $columnLeft, $opColumnRight, $columnRight );
    public function whereGroup( $qb );
    public function whereExists( $qb );
    public function groupBy( ...$columns );
    public function having( $column, $opValue, $value );
    public function orHaving( $column, $opValue, $value );


    // Getter

    public function get();

    // Aggregates

    public function max( $column );
    public function count( $column );

    // Raw

    public function queryRaw( $sql, ...$paramValues );
    public function selectRaw( $sql, ...$paramValues );
    public function whereRaw( $sql, ...$paramValues );
    public function orWhereRaw( $sql, ...$paramValues );
    public function havingRaw( $sql, ...$paramValues );
    public function orHavingRaw( $sql, ...$paramValues );

    // Join

    public function join ( $table, $leftColumn, $operator, $rightColumn);
    public function leftJoin ( $table, $leftColumn, $operator, $rightColumn);
    public function crossJoin ( $table ); 

    // Pagination

    public function limit( $offset, $limit );
    public function page( $page, $perPage );

}