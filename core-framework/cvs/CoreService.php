<?php

class CoreService {

  protected function getInstance($key) {
    return self::instance($key);
  }

  private static function instance($configKey) {

    $dbConfig = self::getConfig($configKey);
  
    switch($dbConfig->driver) {
      case 'mysqli': 
        if(!isset($connectionCache[$dbConfig->driver]))
          $connectionCache[$dbConfig->driver] = 
            new CoreDatabaseMysqli($dbConfig->config);
        return $connectionCache[$dbConfig->driver];
    }

  }

  protected static function getConfig($configKey) {
    $appDbConfig = CORE_APP_PATH . DS . "config" . DS . "db.json";

    if(file_exists($appDbConfig))
    {
      $configs = json_decode(file_get_contents($appDbConfig));
      foreach($configs->db as $c) {
        if($c->key == $configKey) return $c;
      }
    }
    return null;
  }

}