<?php
	
	// The absolute path to index.php
	define("PATH_ROOT", __DIR__);
	
	// The absolute url to index.php
	define("WEB_ROOT", ((isset($_SERVER['HTTPS'])) ? "https://" : "http://") . $_SERVER["HTTP_HOST"] . dirname($_SERVER['SCRIPT_NAME']));
	
	// Template root URL and URL
	define("TEMPLATE_ROOT_PATH", 	PATH_ROOT . "/Templates");
	define("TEMPLATE_ROOT_URL", 	WEB_ROOT . "/Templates");
	
	// Template to use, path and url. Used for convenience.
	define("TEMPLATE_NAME", 		"Default");
	define("TEMPLATE_PATH", 		TEMPLATE_ROOT_PATH . "/" . TEMPLATE_NAME);
	define("TEMPLATE_URL", 			TEMPLATE_ROOT_URL  . "/" . TEMPLATE_NAME);
	
	// Plugin specifics
	define("PLUGIN_PATH", 			PATH_ROOT . "/Plugins");
	
	// Smarty
	define("SMARTY_DIR", 			PATH_ROOT . "/Smarty/");