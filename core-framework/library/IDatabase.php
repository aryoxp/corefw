<?php defined('CORE') or die('Can\'t access directly!');

interface IDatabase {
  
  // connection templates
  public function connect();
  public function disconnect(); 
	
	// informational templates
	public function getVersion();
	public function getInsertId();
	public function getError();

  // query template
  public function query( $query );
	public function getVar( $query );
	public function getRow( $query );

	// transaction templates
  public function begin();
  public function commit();
  public function rollback();
	
	// functional templates
	public function escape( $data );
	
}
