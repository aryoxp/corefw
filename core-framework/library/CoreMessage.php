<?php

class CoreMessage {

  static private $instance;

  const DEBUG = 0;
  const INFO = 1;
  const SUCCESS = 2;
  const WARNING = 3;
  const ERROR = 4;
  const DATABASE = 50;

  private $messages = array();
  
	private $database = array();
  private $info = array();
  private $error = array();
  private $warning = array();
  private $success = array();
  private $debug = array(); 
	
	private function __construct( $message, $type ) {
    switch($type) {
      case CoreMessage::INFO:
        $this->info[] = $message; break;
      case CoreMessage::ERROR:
        $this->error[] = $message; break;
      case CoreMessage::DEBUG:
        $this->debug[] = $message; break;
      case CoreMessage::SUCCESS:
        $this->success[] = $message; break;
      case CoreMessage::WARNING:
        $this->warning[] = $message; break;
      case CoreMessage::DATABASE:
        $this->database[] = $message; break;
    }
	}
	
	static public function instance( $message = NULL, $type = CoreMessage::INFO ) {
		if( !self::$instance )
			self::$instance = new CoreMessage( $message, $type );
		return self::$instance;
	}
		
	public function get( $kind = array() ) {

    if(!is_array($kind)) $kind = [$kind];

    if(empty($kind)) {
      $this->messages[] = $this->debug;
      $this->messages[] = $this->info;
      $this->messages[] = $this->success;
      $this->messages[] = $this->warning;
      $this->messages[] = $this->error;
      $this->messages[] = $this->database;
      return $this->messages;
    }

    foreach($kind as $k) {
      switch($kind) {
        case CoreMessage::DEBUG:
          $this->messages[] = $this->debug; break;
        case CoreMessage::INFO:
          $this->messages[] = $this->info; break;
        case CoreMessage::SUCCESS:
          $this->messages[] = $this->success; break;
        case CoreMessage::WARNING:
          $this->messages[] = $this->warning; break;
        case CoreMessage::ERROR:
          $this->messages[] = $this->error; break;
        case CoreMessage::DATABASE:
          $this->messages[] = $this->database; break;
      }
    }

    return $this->messages;
		
	}

}