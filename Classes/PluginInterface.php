<?php
	
	/**
	  This interface describes the absolute minimal functions that a plugin should implement.
	 */
	
	interface PluginInterface
	{
		// Must return an array with the mime types the plugin can handle
		public function mimeTypes();
		
		// Set file to parse
		public function setFile($sFilePath);
		
		// Returns the file path that was set using setFile
		public function file();
		
		// Returns parsed data as an array
		public function parsedData();
	}