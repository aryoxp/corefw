<?php

class CoreView {
  
  private static $instance;

  private $styles = array();
  private $scripts = array();
  private $plugins = array();

  private function __construct($controller) {
    $this->controller = $controller;
  }

  public static function instance($controller) {
    if(self::$instance == null)
      self::$instance = new CoreView($controller);
    return self::$instance;
  }

  public function json( $data ) {
    header('content-type:application/json');
    echo json_encode($data);
    exit;
  }

  public function view( $view, $data = array(), $return = false) {

    $viewPath = CORE_APP_PATH . DS . "view" . DS . $view;

    if(is_array($data)) extract($data);
    if($return) ob_start();
    if(file_exists($viewPath) and is_readable($viewPath))
      include $viewPath;
    else echo 'View: ' . $view . ' not found.';
    if($return) return ob_get_clean();

  }

  public function location( $path = NULL, $secure = false ) {

    if(preg_match("/http(s?)/i", $path)) return $path;
    $core = Core::instance();
    $scheme = ($secure) ? 
      $core->getUri(CoreUri::SCHEME) . "s" :
      $core->getUri(CoreUri::SCHEME);
    $port = ($core->getUri(CoreUri::PORT) == 80) 
      ? ''
      : ":" . $core->getUri(CoreUri::PORT) ;

    $location = 
      $scheme . "://" 
      . $core->getUri(CoreUri::HOST) . DS
      . $core->getUri(CoreUri::SCRIPT) . DS 
      . $path;

    return $location; 

  }

  public function assets( $path = NULL, $secure = false ) {
    if(preg_match("/http(s?)/i", $path)) return $path;

    $core = Core::instance();

    $scheme = ($secure) ? 
      $core->getUri(CoreUri::SCHEME) . "s" :
      $core->getUri(CoreUri::SCHEME);

    $port = ($core->getUri(CoreUri::PORT) == 80) 
      ? ''
      : ":" . $core->getUri(CoreUri::PORT) ;

    $location = 
      $scheme . "://" 
      . $core->getUri(CoreUri::HOST) . DS
      . str_replace('index.php', '', $core->getUri(CoreUri::SCRIPT))
      . CORE_APP . DS . "assets" . DS
      . $path;

    return $location;
  }

  public function addStyle($path) {
    if(preg_match('/^http/i', $path)) {
      $this->styles[] = $path;
      return;
    }
    if(file_exists(
      CORE_BASE_PATH . DS . CORE_APP . DS . "assets" . DS 
      . $path)) {
      if(!in_array($this->assets($path), $this->styles))
        $this->styles[] = $this->assets($path);
    } else echo '<!-- Invalid: ' . $scriptPath . '-->';
  }
  public function addScript($opath) {
    if(preg_match('/^http/i', $opath)) {
      $this->scripts[] = $opath;
      return;
    }
    $path = preg_replace('/(\?.*)/i', '', $opath);
    $scriptPath = CORE_BASE_PATH . DS . CORE_APP . DS . "assets" . DS . $path;
    // var_dump("$scriptPath: " . file_exists($scriptPath));
    if(file_exists($scriptPath)) {
        if(!in_array($this->assets($opath), $this->scripts))
          $this->scripts[] = $this->assets($opath);
    } else echo '<!-- Invalid: ' . $scriptPath . '-->';
      
  }
  public function addPlugin($key) {
    $pluginDefs = CORE_BASE_PATH . DS . CORE_APP . DS 
      . "config" . DS . "plugin.json";
    if(file_exists($pluginDefs) and is_readable($pluginDefs)) {
      $plugins = json_decode(file_get_contents($pluginDefs));
      foreach($plugins->plugins as $p) {
        if($p->key == $key) {
          if(isset($p->scripts)) {
            foreach($p->scripts as $s) $this->addScript($s);
          }
          if(isset($p->styles)) {
            foreach($p->styles as $s) $this->addStyle($s);
          }
          break;
        }
      }
    }
  }

}