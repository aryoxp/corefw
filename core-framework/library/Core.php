<?php
/**
 * A core object that provides access to libraries of the framework.
 * A core is a singleton object that should be accessed from its
 * static instance method.
 */
class Core {

  private static $instance;

  private $autoloader;
  private $config;
  private $uri;
  private $message;

  const LIBCONFIG = "config";
  const LIBURI = "uri";
  const LIBMESSAGE = "message";

  private function __construct() {
    // load the autoloader class file manually...
    require_once CORE_LIBRARY . 'CoreAutoloader.php';

    // instantiate core-class objects
    $this->autoloader = new CoreAutoloader();
    $this->config = CoreConfig::instance();
    $this->uri = CoreUri::instance();
    $this->message = CoreMessage::instance();
  }

  /**
   * A method to get an instance of a requested 
   * core framework library
   */
  public static function instance($lib = null) {
    if (Core::$instance == null) Core::$instance = new Core();
    switch($lib) {
      case Core::LIBCONFIG:
        return (Core::$instance)->config;
      case Core::LIBURI:
        return (Core::$instance)->uri;
      case Core::LIBMESSAGE:
        return (Core::$instance)->message;
      default:
        return (Core::$instance);
    }
    return Core::$instance;
  }

  public function __get($var) {
    return $this->$var;
  }

  public function getUri($type = null) {
    switch ($type) {
      case CoreUri::CONTROLLER:
        return $this->uri->getController();
      case CoreUri::METHOD:
        return $this->uri->getMethod();
      case CoreUri::ARGS:
        return $this->uri->getArgs();
      case CoreUri::SCHEME:
        return $this->uri->getScheme();
      case CoreUri::HOST:
        return $this->uri->getHost();
      case CoreUri::PORT:
        return $this->uri->getPort();
      case CoreUri::SCRIPT:
        return $this->uri->getScript();
      case CoreUri::QUERY_STRING:
        return $this->uri->getQueryString();
    }
    return $this->uri;
  }

  // Config handling

  public function getConfig($key) {
    return $this->config->get($key);
  }

  public function loadConfig($configFile) {
    $this->config->load($configFile);
    return $this;
  }

  // Message handling
  public function setMessage($message, $type) {

  }

}