<?php 

class Router
{	
	public static function redirect($_url)
	{

		if(strlen($_url) > 1 && (strrpos($_url, "http")> -1)){
			
			header("Location: " . $_url);
		}
		
		else{
			header("Location: error_no_route" );
		}
	}

}