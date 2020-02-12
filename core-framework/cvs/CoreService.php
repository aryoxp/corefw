<?php

class CoreService {

  protected function getInstance($key) {
    return self::instance($key);
  }

  private static function instance($configKey) {
    $dbConfig = self::getConfig($configKey);
    switch ($dbConfig->driver) {
    case 'mysqli':
      if (!isset(CoreDB::$connectionCache[$dbConfig->driver])) {
        CoreDB::$connectionCache[$dbConfig->driver] =
        new CoreDatabaseMysqli($dbConfig->config);
      }
      return CoreDB::$connectionCache[$dbConfig->driver];
    }
  }

  protected static function getConfig($configKey) {
    $appDbConfig = CORE_APP_PATH . DS . "config" . DS . "db.json";

    if (file_exists($appDbConfig)) {
      $configs = json_decode(file_get_contents($appDbConfig));
      foreach ($configs->db as $c) {
        if ($c->key == $configKey) {
          // var_dump($c);
          return $c;
        }
      }
    }
    return null;
  }

}