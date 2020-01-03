<?php

$GLOBALS['microtime_start'] = microtime(true);

if (!CORE_APP) {
  die("Unable to find CORE_APP directory definition,
  Core Framework will now exit.");
}

session_set_cookie_params(array('Secure' => false, 'SameSite' => 'Lax'));
session_start();

// defining system and application structure paths
define('DS', "/");
define('CORE', dirname(__FILE__) . DS);
define('CORE_LIBRARY', CORE . DS . 'library' . DS);
define('CORE_CVS', CORE . DS . 'cvs' . DS);
define('CORE_CONTROLLER', CORE_APP . DS . 'controller' . DS);
define('CORE_SERVICE', CORE_APP . DS . 'service' . DS);
define('CORE_MODEL', CORE_APP . DS . 'model' . DS);
define('CORE_VIEW', CORE_APP . DS . 'view' . DS);
define('CORE_APPLIBRARY', CORE_APP . DS . 'library' . DS);
define('CORE_BASE_PATH', getcwd());
define('CORE_APP_PATH', getcwd() . DS . CORE_APP);

// instantiate the autoloader object
require_once CORE_LIBRARY . 'Core.php';
$core = Core::instance();

// try to instantiate the controller and execute it's selected method
try {
  //var_dump($core->getUri(CoreUri::METHOD));
  $controller = $core->getUri(CoreUri::CONTROLLER);
  $method = $core->getUri(CoreUri::METHOD);
  $args = $core->getUri(CoreUri::ARGS);

  //var_dump($controller, $method, $args); exit;

  if (str_replace("Controller", '', $controller) == false) {
    $controller = ucfirst($core->getConfig('default-controller') . "Controller");
  }

  // try to instantiate the controller
  if (file_exists(CORE_CONTROLLER . $controller . ".php")) {
    $P = new $controller();
  } else {
    throw CoreError::instance("Invalid controller: "
      . str_replace("Controller", "", $controller)
      . ".");
  }

  if (empty($method)) {
    $method = $core->getConfig('default-method');
  }

  if (method_exists($controller, $method)) {
    // execute method
    call_user_func_array(array($P, $method), $args);

  } else {
    throw CoreError::instance("Method: $method not found in controller: "
      . str_replace("Controller", "", $controller)
      . ".");
  }

} catch (Exception $e) {
  $e->show();
  exit;
}

// do cleanup here ...
exit;
