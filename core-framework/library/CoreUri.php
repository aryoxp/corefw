<?php 
defined('CORE') or die('Can\'t access directly!');

class CoreUri {

  private $uri;
  private $script;
  private $scheme;
  private $host;
  private $port;
  private $query;

	private $controller;
	private $method;
  private $args;
  
  const CONTROLLER = 1;
  const METHOD = 2;
  const ARGS = 3;
  
  const SCHEME = 11;
  const HOST = 12;
  const PORT = 13;
  const SCRIPT = 14;

  const QUERY_STRING = 21;
	
	private static $instance;

  private function __construct(){

    $this->scheme = $_SERVER['REQUEST_SCHEME'];
    $this->host = $_SERVER['HTTP_HOST'];
    $this->port = $_SERVER['SERVER_PORT'];
    $this->uri = $_SERVER['REQUEST_URI'];
    $this->script = $_SERVER['SCRIPT_NAME'];
    $this->query = $_SERVER['QUERY_STRING'];

    $uri = $queryString = explode("?", $this->uri);

    preg_match('/index\.php(.*)?/i', $uri[0], $matches);
    $pathInfo = 
      (is_array($matches) and count($matches) > 1) 
      ? trim($matches[1], "/")
      : ""; // var_dump($matches);

    $pathParts = explode("/", $pathInfo); // var_dump($pathParts);

    $this->controller = ucfirst(array_shift($pathParts)) . 'Controller';
    $this->method = array_shift($pathParts); // var_dump($this->method);
    $this->args = $pathParts; // var_dump($this->args);

  }

  public static function instance() {
    if(CoreUri::$instance == null)
      CoreUri::$instance = new CoreUri();
    return CoreUri::$instance;
  }

  public function getScheme() {
    return $this->scheme;
  }

  public function getHost() {
    return $this->host;
  }

  public function getPort() {
    return $this->port;
  }

  public function getScript() {
    return trim($this->script, "/");
  }

  public function getQueryString() {
    return $this->query;
  }

  public function getController() {
    return $this->controller;
  }

  public function getMethod() {
    return $this->method;
  }

  public function getArgs() {
    return $this->args;
  }

} 
