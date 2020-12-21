<?php

class CoreController {

  protected $ui;
  protected $core;

  public function __construct()
  {
    $this->ui = CoreView::instance($this);
    $this->core = Core::instance();
  }

  public function redirect( $destination = NULL ) {
		
    $destination = ( empty( $destination ) ) ? $this->location() : $destination;

    if ( !preg_match("/^http(s)\:\/\//i", $destination) )
        $destination = $this->location() . $destination;
    header( 'location: ' . trim( $destination ) );
    exit;
    
  }

  public function location( $path = NULL, $secure = false ) {
    return $this->ui->location($path, $secure);
  }

  public function file( $path ) {
      return CORE_BASE_PATH . DS . CORE_APP . DS . "assets" . DS . $path;
  }

  // get request variables and sanitizes them
  public function post($var, $defaultValue = null) {
    $var = isset($_POST[$var]) ? $_POST[$var] : $defaultValue;
    return $var;
  }
  public function get($var, $defaultValue = null) {
    $var = isset($_GET[$var]) ? $_GET[$var] : $defaultValue;
    return $var;
  }
  public function request($var, $defaultValue = null) {
    $var = isset($_REQUEST[$var]) ? $_REQUEST[$var] : $defaultValue;
    return $var;
  }

}