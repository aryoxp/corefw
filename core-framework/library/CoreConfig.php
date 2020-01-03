<?php
defined('CORE') or die("This file can't be accessed directly!");

class CoreConfig {

  static private $instance;
  private $coreConfig;

  private function __construct() {

    $coreConfig = file_get_contents(CORE . "config" . DS . "core.json");
    $this->coreConfig = json_decode($coreConfig);

    $appConfigFile = CORE_APP . "config" . DS . "core.json";
    if (file_exists($appConfigFile) and is_readable($appConfigFile)) {
      $appConfig = file_get_contents();
      foreach ($appConfig as $key => $value) {
        $this->coreConfig->$key = $value;
      }
    }

    // set the error reporting environment setup
    switch ($this->coreConfig->environment) {
      case 'DEV':error_reporting(E_ALL);
        break;
      case 'PRO':error_reporting(0);
        break;
      default:
        exit('The application environment is not set correctly. Please check your environment configuration on config.php file.');
    }
    date_default_timezone_set($this->get('default-timezone'));
  }

  public function get($key) {
    return $this->coreConfig->config->$key;
  }

  public static function instance() {
    if (!self::$instance)
      self::$instance = new CoreConfig();
    return self::$instance;
  }

  /**
   * Get Base URL
   *
   * @author  Aris S Ripandi
   * @since  Version 0.3.1
   *
   * @access public
   * @return void
   */
  public function base_url() {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://'; // protocol
    $base_url .= preg_replace('/:(80|443)$/', '', $_SERVER['HTTP_HOST']); // host[:port]
    $base_url .= str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // path
    if (substr($base_url, -1) == '/')
      $base_url = substr($base_url, 0, -1);
    $base_url = $base_url . '/';
    return $base_url;
  }

} // End Library_config