<?php

class CoreView {

  private static $instance;

  private $styles  = array();
  private $scripts = array();

  private function __construct($controller) {
    $this->controller = $controller;
  }

  public static function instance($controller) {
    if (self::$instance == null) {
      self::$instance = new CoreView($controller);
    }

    return self::$instance;
  }

  public function language($path) {
    CoreLanguage::instance()->load($path);
  }

  public function l($key = '', ...$params) {
    if($key == '' || trim($key) == '') return "-";
    return CoreLanguage::instance()->get($key, ...$params);
  }

  public function json($data) {
    header('content-type:application/json');
    echo json_encode($data);
    exit;
  }

  public function view($view, $data = array(), $return = false) {

    $viewPath = CORE_APP_PATH . DS . "view" . DS . $view;

    if (is_array($data)) {
      extract($data);
    }

    if ($return) {
      ob_start();
    }

    if (file_exists($viewPath) and is_readable($viewPath)) {
      include $viewPath;
    } else {
      echo 'View: ' . $view . ' not found.';
    }

    if ($return) {
      return ob_get_clean();
    }

  }

  public function location($path = NULL, $secure = false) {

    if (preg_match("/http(s?)/i", $path)) {
      return $path;
    }

    $core   = Core::instance();
    $scheme = ($secure) ?
    $core->getUri(CoreUri::SCHEME) . "s" :
    $core->getUri(CoreUri::SCHEME);

    $location =
    $scheme . "://"
    . $core->getUri(CoreUri::HOST) . DS
    . $core->getUri(CoreUri::SCRIPT) . DS
      . $path;

    return $location;

  }

  public function assets($path = NULL, $secure = false) {
    if (preg_match("/http(s?)/i", $path)) {
      return $path;
    }

    $core = Core::instance();

    $scheme = ($secure) ?
    $core->getUri(CoreUri::SCHEME) . "s" :
    $core->getUri(CoreUri::SCHEME);

    $location =
    $scheme . "://"
    . $core->getUri(CoreUri::HOST) . DS
    . str_replace('index.php', '', $core->getUri(CoreUri::SCRIPT))
      . CORE_APP . DS . "assets" . DS
      . $path;

    return $location;
  }

  public function addStyle($path, $opt = null) {
    if (preg_match('/^http/i', $path)) {
      $this->styles[] = $path . ($opt ? $opt : '');
      return;
    }
    $stylePath = CORE_BASE_PATH . DS . CORE_APP . DS . "assets" . DS . $path;
    if (file_exists($stylePath)) {
      if (!in_array($this->assets($path), $this->styles)) {
        $this->styles[] = $this->assets($path) . ($opt ? $opt : '');
      }

    } else {
      echo '<!-- Invalid: ' . $stylePath . '-->';
    }

  }
  public function addScript($opath, $opt = null) {
    if (preg_match('/^http/i', $opath)) {
      $this->scripts[] = $opath . ($opt ? $opt : '');
      return;
    }
    $path       = preg_replace('/(\?.*)/i', '', $opath);
    $scriptPath = CORE_BASE_PATH . DS . CORE_APP . DS . "assets" . DS . $path;
    // var_dump("$scriptPath: " . file_exists($scriptPath));
    if (file_exists($scriptPath)) {
      if (!in_array($this->assets($opath), $this->scripts)) {
        $this->scripts[] = $this->assets($opath) . ($opt ? $opt : '');
      }

    } else {
      echo '<!-- Invalid: ' . $scriptPath . '-->';
    }

  }
  public function addPlugin($key, $opt = null) {
    $pluginDefs = CORE_BASE_PATH . DS . CORE_APP . DS
      . "config" . DS . "plugin.json";
    if (file_exists($pluginDefs) and is_readable($pluginDefs)) {
      $plugins = json_decode(file_get_contents($pluginDefs));
      foreach ($plugins->plugins as $p) {
        if ($p->key == $key) {
          if (isset($p->scripts)) {
            foreach ($p->scripts as $s) {
              $this->addScript($s, $opt);
            }

          }
          if (isset($p->styles)) {
            foreach ($p->styles as $s) {
              $this->addStyle($s, $opt);
            }

          }
          break;
        }
      }
    }
  }

}