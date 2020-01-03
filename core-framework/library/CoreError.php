<?php

class CoreError extends Exception {
	
	public function show() {
    @header('content-type:application/json');

    $error = new stdClass;
    $error->status = false;
    $error->error = $this->message;

    echo json_encode($error);
  }

  public static function instance( $message ) {
    return new CoreError($message);
  }
	
}
