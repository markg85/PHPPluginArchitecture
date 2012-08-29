<?php

	/**
		Lite_Rest_Router
		
		This class is meant to be used when you want to have restfull urls very fast with as little overhead as possible.
		Right now there is no APC support, but in time that should be added since this is a typical case where APC can
		come in very handy!
		
		Version:		0.5
		Author:			markg85 <markg85@gmail.com>
		License:		It's all yours! (under BSD)
		Requirements:	PHP 5.3+
		
		Examples
		Using this class is quite easy. Below are some examples of how one could use this class.
		$oExample = new Lite_Rest_Router();
		
		This would match any url with:
		URL/articles/some_long_title.whatever
		$oExample->get("/articles/:title/", function($articles, $title)
		{
			echo $one . ' --- ' . $two . "<br />";
		});
		
		Anything that's written between / and / that doesn't start with a ":" will only exactly match the url.
		So this: $oExample->get("/articles/blog/", function($articles, $blog){}); will only match URL/articles/blog/
		
		Anything that starts with a ":" will match anything that is written between / and /.
		So this: $oExample->get("/articles/:other/", function($articles, $other){}); will match anything as long as it starts with /articles/
		/articles/some_long_page_link
		/articles/whatever
		
		If you don't end the pattern wits a slash like so: $oExample->get("/articles/:other", function($articles, $other){});
		then it will match ALL of the url stuff after /articles/. Inclusing other slashes.
		/articles/anything/behind/this/
		
		You obviously can so a whole lot more:
		$oExample->get("/:everything", function($everything){}); just matches everything
		$oExample->get("/articles/:other/:comment/", function($articles, $other, $comment){});
		
		Changelog
		0.5 - Fixed a bug where $oExample->get("/article/:two", function($one, $two){}); would match /article as well.
		0.4 - Add ability to use $oExample->get("/", function($everything){}); as index. So not providing anything in your url will take the / route.
			- Return false instead of just return. Looks better.
		0.3 - Fixed matching so that /:one/:two/ actually only matches those two and not everything after :one.
			- Redone the mapRoute function to allow for much more flexibility.
			- Doing /:something/ will match everything between the / and /. Anything else will be an exact case sensitive match.
			  So, /one/ will onle match /one (with and without trailing slash). This allows for more flexible routing since
			  you can specify a function for every route you want.
		0.2 - Greatly improved the used regular expression for matching url parts
			- Fixed a lot of possible errors
			- Trim of the leading slash since that makes the regular expressions more friendly and logic
			- Note, the number of ":" that you pass in the get, post, put or delete function must match the number of arguments for the closure!
			- Double forward slashes will be seen as 1 forward slash
			- Added urldecoding (urldecode())
			- Greatly simplified the checking for GET, POST, PUT and DELETE
		0.1	- Initial release
		
		Possible optimizations
		- Use caching for URL Routing storage.
		- Create the $aMatches at class construction time rather then in the mapRoute function
		- Split the mapRoute in 2 stages. Stage 1 matches exact paths. If a dynamic path is detected enter stage 2.
		- Perhaps using callbacks when a certain route is matched works better. The current lambda version works just fine,
		  but is going through all cases always. Doesn't matter much for a few but probably becomes slow when you have a few hundred possible matches.
	*/
	class Lite_Rest_Router
	{
		private $aCallbackData			= array();
		private $sPathInfo				= '';
		private $sExpression			= '([a-zA-Z0-9_\-. ]*)';
		
		public function __construct()
		{
			// First get the full URI. This includes the current subfolder name..
			$sFullUri 		= (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';
			$sScriptName 	= (isset($_SERVER['PATH_INFO'])) ? $_SERVER['SCRIPT_NAME'] : dirname($_SERVER['SCRIPT_NAME']);
			
			// Now remove the subfolder to get a clean URL from the current folder also remove and leading slashes.
			$this->sPathInfo = urldecode(substr($sFullUri, strlen($sScriptName)));
			
			// Clean the path of double slashes
			$this->sPathInfo = $this->stripDoubleSlashes($this->sPathInfo);
		}
		
		public function setExpression($sExpression)
		{
			$this->sExpression = $sExpression;
		}
		
		private function stripDoubleSlashes($sInput)
		{
			return preg_replace(array('/\/{1,}/'), array('/'), $sInput);
		}
		
		private function mapRoute($eType, $sPattern, $oCallback)
		{
			// Check if we are allowed to call the method with out current REQUEST_METHOD from PHP
			if($_SERVER['REQUEST_METHOD'] != $eType)
			{
				trigger_error('The current request method is: '.$_SERVER['REQUEST_METHOD'].' while the function call was for '.$eType.'.', E_USER_ERROR);
			}
			
			// Is the callback function callable?
			if(!is_callable($oCallback))
			{
				trigger_error('The supplied callback is not callable.', E_USER_ERROR);
			}
			
			// A path can't exist in just 1 or less character(s) so exit if it does.
			if(strlen($this->sPathInfo) <= 1 && $this->sPathInfo != "/")
			{
				return false;
			}
			
			// If there is no route known we make and store it.
			$sMethodUniqueName = $eType . $sPattern;
			if(!isset($this->aCallbackData[$sMethodUniqueName]))
			{
				// Match all url parts
				preg_match_all("/" . $this->sExpression . "/", $this->sPathInfo, $aMatches);
				
				if(is_array($aMatches[0]))
				{
					// We have no need for the other data in the $aMatches array so we can simply re-assign it with the data we want.
					$aMatches = array_values(array_filter($aMatches[0]));
				}
				
				$aPattern = array_values(array_filter(explode("/", $sPattern)));
				
				$lastPattern = end($aPattern);
				
				if(count($aPattern) > 0 && empty($aMatches))
				{
					return false;
				}
				elseif(count($aPattern) < count($aMatches))
				{
					// We are now in a condition where we could possible return the function because there are to little patterns for the given url to ever match
					// However.. The last pattern "could" be one that matches "the rest of the URL" this the function should continue
					
					if(substr($sPattern, -1) != "/" && $lastPattern[0] != ":")
					{
						return false;
					}
					elseif(substr($sPattern, -1) == "/")
					{
						return false;
					}
				}
				
				foreach($aPattern as $iKey => $sSinglePattern)
				{
					// Starting with ":" means anything will match.
					if(isset($aMatches[$iKey]))
					{
						if($sSinglePattern[0] == ":" && substr($sPattern, -1) != "/" && $sSinglePattern == end($aPattern))
						{
							// Put all remaining matches in one long string and abort the loop
							$aTempMatches = $aMatches;
							for($int = 0; $int < $iKey; $int++)
							{
								array_shift($aTempMatches);
							}
							$aMatches[$iKey] = implode("/", $aTempMatches);
							break;
						}
						
						if(empty($aMatches[$iKey]))
						{
							return false;
						}
					}
					elseif(isset($aMatches[$iKey]) && $sSinglePattern != $aMatches[$iKey])
					{
						return false;
					}
					else
					{
						return false;
					}
				}
				
				// set the function and arguments
				$this->aCallbackData[$sMethodUniqueName]['function'] = $oCallback;
				$this->aCallbackData[$sMethodUniqueName]['arguments'] = $aMatches;
			}
			
			// Once the route has been created return it
			return call_user_func_array($this->aCallbackData[$sMethodUniqueName]['function'], $this->aCallbackData[$sMethodUniqueName]['arguments']);
		}
		
		public function get($pattern, $callback)
		{
			return $this->mapRoute('GET', $pattern, $callback);
		}
		
		public function post($pattern, $callback)
		{
			return $this->mapRoute('POST', $pattern, $callback);
		}
		
		public function put($pattern, $callback)
		{
			return $this->mapRoute('PUT', $pattern, $callback);
		}
		
		public function delete($pattern, $callback)
		{
			return $this->mapRoute('DELETE', $pattern, $callback);
		}
	}