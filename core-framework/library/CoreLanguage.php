<?php

class CoreLanguage {
  private static $instance;
  private static $_CORE_LANG;

  function __construct()
  {
    CoreLanguage::$_CORE_LANG = array();
  }

  public static function instance() {
    if(CoreLanguage::$instance == null) CoreLanguage::$instance = new CoreLanguage();
    return CoreLanguage::$instance;
  }

  public function load($path) {
    $langPath = CORE_APP_PATH . DS . "assets" . DS . "languages" . DS . $path . ".json";
    if(file_exists($langPath)) {
      $langJson = file_get_contents($langPath);
      CoreLanguage::$_CORE_LANG = array_merge(CoreLanguage::$_CORE_LANG, (array) json_decode($langJson));
    }
  }

  public function get($key = '') {
    return (isset(CoreLanguage::$_CORE_LANG[$key])) ? CoreLanguage::$_CORE_LANG[$key] : '-';
  }



}