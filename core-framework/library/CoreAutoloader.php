<?php

class CoreAutoloader {
	public function __construct() {
    spl_autoload_register(array($this, 'libraryLoader'));
    spl_autoload_register(array($this, 'driverLoader'));
    spl_autoload_register(array($this, 'cvsLoader'));
		spl_autoload_register(array($this, 'appLibraryLoader'));
		spl_autoload_register(array($this, 'controllerLoader'));
    spl_autoload_register(array($this, 'serviceLoader'));
    spl_autoload_register(array($this, 'modelLoader'));
	}

	private function libraryLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_LIBRARY . $className . '.php';
		
  }

  private function driverLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_LIBRARY . DS . "drivers" . DS . $className . '.php';
		
  }
  
  private function cvsLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_CVS . $className . '.php';
		
	}

	private function appLibraryLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_APPLIBRARY . $className . '.php';
		
	}

	private function controllerLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_CONTROLLER . $className .'.php';
		
	}

	private function serviceLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_SERVICE . $className .'.php';
		
  }
  
  private function modelLoader( $className ) {
		
		// convert the given class name to it's path
		// $className = trim( str_replace("_", "/", $className), "/" );
		@include_once CORE_MODEL . $className .'.php';
		
	}
	
	public static function register($loader) {
		spl_autoload_register($loader);	
	}
}

?>