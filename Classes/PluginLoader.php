<?php
	
	/**
	  The PluginLoader class loads all the plugins from PLUGIN_PATH (defined in config.php and included in index.php).
	  It will make an instance of every plugin and store those. This is not "lazy loading".
	  It will also maintain an internal list of loaded plugins based on mime type. A mime type can have multiple plugins.
	 */
	
	class PluginLoader
	{
		// All mime types from all plugins will be registered in here.
		private $aRegisteredMimeTypes = array();
		
		// The actual plugin objects will be stored in here.
		private $aRegisteredPlugins = array();
		
		public function __construct()
		{
			if(defined("PLUGIN_PATH") && is_dir(PLUGIN_PATH))
			{
				$aFiles = scandir(PLUGIN_PATH);
				
				// Remove . and .. from the array list. This saves other checks later on and those two are always the first 2 elements.
				unset($aFiles[0]);
				unset($aFiles[1]);
				
				// Iterate over all folders and attempt to load the plugins.
				foreach($aFiles as $sPluginFolderName)
				{
					$sComposedDir = PLUGIN_PATH . "/" . $sPluginFolderName;
					$sComposedPluginFile = $sComposedDir . "/plugin.php";
					if(is_dir($sComposedDir) && is_file($sComposedPluginFile))
					{
						require_once($sComposedPluginFile);
						
						$interfaces = class_implements($sPluginFolderName);
						if(isset($interfaces["PluginInterface"]))
						{
							// The class implements the PluginInterface thus can be used.
							$this->aRegisteredPlugins[$sPluginFolderName] = new $sPluginFolderName();
							$this->pluginChecks($sPluginFolderName);
						}
						else
						{
							trigger_error("The plugin: " . $sPluginFolderName . " is not implementing PluginInterface.", E_USER_ERROR);
						}
					}
					elseif(!is_dir($sComposedDir))
					{
						trigger_error("The item: " . $sComposedDir . ", does not belong in the plugin folder. Please move it elsewhere.", E_USER_NOTICE);
					}
					else
					{
						trigger_error("Cannot find: " . $sComposedPluginFile . ", please verify that you installed the plugin correctly.", E_USER_NOTICE);
					}
				}
			}
			else
			{
				trigger_error("The PluginLoader cannot open " . PLUGIN_PATH, E_USER_ERROR);
			}
		}
		
		/**
		  This checks the plugin for some information that should be known in the PLuginLoader.
		  For now this only fetches the mime list from the plugins, but this can do - obviously - a lot more.
		 */
		private function pluginChecks($sPluginName)
		{
			$oPlugin = $this->aRegisteredPlugins[$sPluginName];
			
			// Get the mime types this plugin registers
			$aPluginMimeTypes = $oPlugin->mimeTypes();
			if(is_array($aPluginMimeTypes) && count($aPluginMimeTypes > 0))
			{
				foreach($aPluginMimeTypes as $sMimeType)
				{
					if(!is_string($sMimeType))
					{
						trigger_error("The mimeType function in plugin: " . $sPluginName . " returns an array with at least one non string value. All array values must be strings!", E_USER_ERROR);
					}
					
					if(!isset($this->aRegisteredMimeTypes[$sMimeType]))
					{
						$this->aRegisteredMimeTypes[$sMimeType] = array();
					}
					array_push($this->aRegisteredMimeTypes[$sMimeType], $sPluginName);
				}
			}
			else
			{
				trigger_error("The plugin: " . $sPluginName . " does not have any mime typed registerd! It must register at least one.", E_USER_ERROR);
			}
		}
		
		/**
		  Fetches the available parsers from the internal class arrays based on the given mime string.
		 */
		public function parsersForMime($sMime)
		{
			$aPlugins = array();
			$aPlugins['metadata'] = array('mime' => $sMime, 'usable_plugins' => 0);
			if(isset($this->aRegisteredMimeTypes[$sMime]))
			{
				$aPlugins['metadata']['usable_plugins'] = count($this->aRegisteredMimeTypes[$sMime]);
				foreach($this->aRegisteredMimeTypes[$sMime] as $sPlugin)
				{
					$aPlugins[$sPlugin] = $this->aRegisteredPlugins[$sPlugin];
				}
			}
			return $aPlugins;
		}
		
		/**
		  This function just grabs the first plugin and returns that object.
		 */
		 public function firstAvailableParser($sMime)
		 {
			if(isset($this->aRegisteredMimeTypes[$sMime]))
			{
				$sPlugin = current($this->aRegisteredMimeTypes[$sMime]);
				return $this->aRegisteredPlugins[$sPlugin];
			}
		 }
		
	}