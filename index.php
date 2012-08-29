<?php
	// Include generic configuration
	require_once('config.php');
	
	// Include URL Routing class
	require_once(PATH_ROOT . '/Classes/Lite_Rest_Router.php');
	
	// Include the PluginLoader
	require_once(PATH_ROOT . '/Classes/PluginLoader.php');
	
	// Include Smarty
	require_once(SMARTY_DIR . 'Smarty.class.php');
	
	// Include the custom mie detection because the Fileinfo extension doesn't work based on extension.
	require_once(PATH_ROOT . '/Classes/MimeDetector.php');
	
	// Create the PluginLoader object
	$oPluginLoader = new PluginLoader();
	
	// Create the URL router object. 
	$oRouter = new Lite_Rest_Router();
	
	// Create the MimeDetector
	$oMimeDetector = new MimeDetector();
	
	// Smarty configuration
	$oSmarty = new Smarty();
	$oSmarty->setTemplateDir(TEMPLATE_PATH . "/HTML/");
	$oSmarty->setCompileDir(TEMPLATE_PATH . "/HTML_Compiled/");
	$oSmarty->setConfigDir(TEMPLATE_PATH . "/Config/");
	$oSmarty->setCacheDir(TEMPLATE_PATH . "/Cache/");
	
	// Handle the index route.
	$oRouter->get("/", function() use ($oPluginLoader, $oSmarty, $oMimeDetector)
	{
		// List the data directory
		$aFiles = scandir(PATH_ROOT . "/DataFiles");
		
		// Remove . and ..
		unset($aFiles[0]);
		unset($aFiles[1]);
		
		$aFileToParserPlugin = array();
		
		foreach($aFiles as $sFile)
		{
			// Get plugin information per file.
			$sFullFilePath = PATH_ROOT . "/DataFiles/" . $sFile;
			$aFileToParserPlugin[$sFile] = $oPluginLoader->parsersForMime($oMimeDetector->mime($sFullFilePath));
		}
		
		$oSmarty->assign('aFileToParserPlugin', $aFileToParserPlugin);
		$oSmarty->display('index.html');
	});
	
	// Handle an individual file
	$oRouter->get("/:filename/", function($sFilename) use ($oPluginLoader, $oSmarty, $oMimeDetector)
	{
		$sFullFilePath = PATH_ROOT . "/DataFiles/" . $sFilename;
		$oSmarty->assign('error', '');
		
		if(is_file($sFullFilePath))
		{
			$sMime = $oMimeDetector->mime($sFullFilePath);
			$oPlugin = $oPluginLoader->firstAvailableParser($sMime);
			$oPlugin->setFile($sFullFilePath);
			$oSmarty->assign('parsedData', $oPlugin->parsedData());
		}
		else
		{
			$oSmarty->assign('error', 'The file: ' . $sFullFilePath . ' does not exist.');
		}
		
		// Note: A file will be parsed with the first available parser if you don't specifically click on a parser.
		$oSmarty->display('parsed_file.html');
	});