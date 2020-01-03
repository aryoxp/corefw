<?php

class CoreResult {
  
  public $result; 
  public $status; 
  public $error;

  public function __construct($result, $status = true, $error = null)
  {
    $this->result = $result; 
    $this->status = $status; 
    $this->error = $error;
  }

	public function show() {
    @header('content-type:application/json');

    $result = new stdClass;
    $result->status = true;
    $result->error = null;
    $error->result = $result;

    echo json_encode($error);
  }

  public static function instance( 
    $result, $status = true, $error = null ) {
    return new CoreResult($result, $status = true, $error = null);
  }
	
}
