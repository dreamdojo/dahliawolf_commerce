<?php


class Jk_Request
{
    
	private static $RAW_HOST;
    private static $SSL_HOST;
    private static $BASIC_HOST;
    private static $HOST;
	private static $BASE_URI;
    
    private static $instance = null;
	private static $protocols = array ('80' => 'http://', '443'=> 'https://');
    

		
	public function __construct()
	{
		self::init();
	}
    
    public static function isJson()
    {
        return isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) || strpos(  $_SERVER['REQUEST_URI'], '.json') > 1;
    }
    
    
    public static function getInstance()
    {
        if (null === self::$instance)
		{
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public static function getHttpHost()
    {
        return  self::$BASIC_HOST;
    }
    
    public static function getSSLHost()
    {
        return self::$SSL_HOST;
    }
    
    public static function getRawHost()
    {
        return self::$RAW_HOST;
    }

    public static function getHost()
    {
        return self::$HOST;
    }
	
	public static function fetchAll($_method, $sanitize = true, $_print = false)
    {
		$_methods['POST'] = $_POST;
		$_methods['GET']  = $_GET;
		$_methods['ALL']  = $_REQUEST;
		
		$_vars = is_array($_methods[$_method]) ? $_methods[$_method] : $_methods['ALL'];
				
		foreach ($_vars as $_k => $_v)
		{
			if($_print)      echo "$_k = $_v <br>";
			if($sanitize)    $_vars[$_k] = stripslashes(Jk_Functions::sanitize( @urldecode ($_v) ));
		}
		
		return (object) $_vars;
	}

    
    private static function init()
    {
        self::isSSL();
        self::setBaseuri();
    }
    
    
	public static function getRequesturi()
	{
        $qs = explode('?',  substr( $_SERVER['REQUEST_URI'], 1) );
        $uri = trim( urldecode( $qs[0] ) );
        
		$return = self::$BASE_URI == '/' ?  $uri : str_replace(self::$BASE_URI, "", $uri);
				
		return $return;
	}


    public static function getRequestParts($index = -1)
    {
        $parts = explode('/', self::getRequesturi() );

        return (int) $index > -1 ? $parts[$index] : $parts;
    }
    
	public static function getQueryString()
	{
        $return = false;
        $uri = explode('?',  $_SERVER['REQUEST_URI']);
          
        if(@$uri[1]) $return = trim( urldecode( $uri[1] ) );
 
		return $return;
	}
	
	
	public static function setBaseuri()
	{
		self::$BASE_URI = Jk_Config::BASE_URI ? Jk_Config::BASE_URI : '';
        
        self::setHost(self::$BASE_URI);
        
    	return self::$BASE_URI;
	}
    
    
    private static function setHost($_uri = '/')
	{
		$_srv = $_SERVER['SERVER_NAME'] . $_uri;
		$_p = self::getProtocol();
		
        self::$RAW_HOST     = trim($_srv, '/');
        self::$HOST         = $_p . $_srv;
        self::$SSL_HOST     = 'https://' . $_srv;
        self::$BASIC_HOST   = 'http://' . $_srv;

	}
	
    
	public static function isSSL()
	{
		return $_SERVER['SERVER_PORT'] == '443' ? true : false;
	}
	
    
	public static function getProtocol()
	{
		$_p = $_SERVER['SERVER_PORT'];
		$_r = '';
		
		foreach(self::$protocols as $_key => $_val)
		{
			if($_key == $_p){
				$_r =  $_val;
			}
		}
		
		return $_r;
	}

    public static function setVar($v = null, $_val, $_post = true)
    {
        if(!$v) return false;

        if($_post)
		{
	    	if( $_val != '' && !isset($_POST[$v]) )
		 	{
		 		$_POST[$v] = $_val;
		 	}
		}else
        {
            $_REQUEST[$v] = $_val;
            $_GET[$v] = $_val;
		}

        return true;
    }
    
	public static function getVar($v = 'a', $_post = false, $sanitize = true)
	{
		$_val = false;

		if($_post)
		{
	    	if(isset($_POST[$v]) && $_POST[$v] != '')
		 	{
		 		$_val = $_POST[$v];
		 	}
			
		}else
        {
			if (isset($_REQUEST[$v]) && $_REQUEST[$v] != '')
			{
				$_val = $_REQUEST[$v];
			}
		}
		
		if($sanitize)
		{
			$_val = Jk_Functions::sanitize( urldecode($_val));
		}
        
	 	
	 	return $_val;
		
	}
	
	
	public static function kill($v = 'a')
	{
		$kill = false;
        $globals_arr  = array(&$_GET, &$_POST, &$_REQUEST);
		
        unset($_GET["$v"]);
        unset($_POST["$v"]);
        unset($_REQUEST["$v"]);
	 	
	 	return 	true;
	}
		
	
} //END CLASS //

?>