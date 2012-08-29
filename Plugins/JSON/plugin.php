<?php
	
	// Include the PluginInterface since we need to inherit from that
	require_once(PATH_ROOT . "/Classes/PluginInterface.php");
	
	
	class JSON implements PluginInterface
	{
		private $sFile = "";
		
		public function __construct()
		{
		
		}
		
		public function mimeTypes()
		{
			return array("application/json");
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
			return json_decode(file_get_contents($this->sFile), true);
		}
	}