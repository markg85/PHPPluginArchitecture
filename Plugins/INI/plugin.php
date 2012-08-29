<?php
	
	// Include the PluginInterface since we need to inherit from that
	require_once(PATH_ROOT . "/Classes/PluginInterface.php");
	
	
	class INI implements PluginInterface
	{
		private $sFile = "";
		
		public function __construct()
		{
		
		}
		
		public function mimeTypes()
		{
			return array("application/textedit", "zz-application/zz-winassoc-ini");
		}
		
		public function setFile($sFilePath)
		{
			$this->sFile = $sFilePath;
		}
		
		public function file()
		{
			return $this->sFile;
		}
		
		public function parsedData()
		{
			return parse_ini_file($this->sFile, true);
		}
	}