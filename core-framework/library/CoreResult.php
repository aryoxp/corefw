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
    $result->status = $this->status;
    $result->error = $this->error;
    $result->result = $this->result;

    echo json_encode($result);
  }

  public static function instance( 
    $result, $status = true, $error = null ) {
    return new CoreResult($result, $status = true, $error = null);
  }
	
}
