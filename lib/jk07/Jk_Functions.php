<?php //
//
class Jk_Functions
{
	const DEBUGGING = true;
	
	public static $instance = null;
	
	protected static $log = false;
    
    
    public function __construct()
    {
        if (false === self::$log) 
		{	
            self::$log = new Jk_Logger(APP_PATH . '/logs/functions.txt', Jk_Logger::DEBUG);
        }
    }

    public static function getRandomString($length=5)
    {
        return substr( str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
    }


    public static function timeAgo($timestamp, $granularity = 2, $format = 'Y-m-d H:i:s')
    {
        $timestamp = intval($timestamp) < 10000 ? strtotime($timestamp) : $timestamp;

        $difference = time() - $timestamp;

        if ($difference < 0) return '0 seconds ago';

        elseif ($difference < 864000)
        {
            $periods = array('week' => 604800, 'day' => 86400, 'hr' => 3600, 'min' => 60, 'sec' => 1);
            $output = '';
            foreach ($periods as $key => $value) {
                if ($difference >= $value) {
                    $time = round($difference / $value);
                    $difference %= $value;
                    $output .= ($output ? ' ' : '') . $time . ' ';
                    $output .= ( ($time > 1) ? "{$key}s" : $key);
                    $granularity--;
                }
                if ($granularity == 0) break;
            }
            return ($output ? $output : '0 seconds') . ' ago';
        }
        else return date($format, $timestamp);
    }

	
    
    public static function getInstance()
    {
        if (null === self::$instance) 
		{	
            self::$instance = new self();   
        }

        return self::$instance;
    }
    
    
    public static function mysqlDate($_date)
    {
		/*$date = date_parse($_date);
		return $date['year'] .'-' . $date['month'] .'-'. $date['day'];*/
		if(strlen($_date) < 8){
			return '';
		}
		
		$date = date( "Y-m-d H:i:s", strtotime( str_replace('-', '/', $_date)));
		return $date;
		//
		//date()
    }
    
    
    public static function htmlDate($_date)
	{
		/*$date = date_parse($_date);
		return $date['month'] .'-' . $date['day'] .'-'. $date['year'];*/
		if(strlen($_date) < 8){
			return '';
		}
		
		$date = date( "m-d-Y", strtotime( str_replace('-', '/', $_date)));
		return $date; 
	}
    
    
    public static function getFileExtension($_file)
    {
        return substr($_file, strrpos($_file, ".") + 1);
    }
    
    
    
	public static function buildOpts($_arr, $deftxt = "- select one -", $setopt = false, $defopt = true, $return = false, $rep_num_keys = true)
	{

        if($rep_num_keys)
        {
            foreach($_arr as $k => $opt)
            {
                if( is_int($k) == true )
                {
                    unset($_arr[$k]);
                    //$_arr [ strtolower(str_replace(' ', '-', $opt) )] = $opt;
                    $_arr [ $opt ] = $opt;
                }
            }
        }

        $string = '';
        	   
		if($defopt && $setopt == false)
		{
            $text = "<option value=\"\"  selected>$deftxt</option>\n";
            
            if($return) { $string .= $text; }
	    	else { print $text; } 
	    }
	    
		$index = 0;
        
	    foreach ($_arr as $_key => $_opt)
	    {
            if( ($setopt !='' ) &&  ($setopt == $_key || count($_arr) == 1) )
            {
                $selected = 'selected';    
            } 
            else
            {
                $selected = '';
            }
            
            $text = "\t\t\t\t\t\t\t<option title=\"$index\" value=\"$_key\" $selected>$_opt</option>\n ";
            
            if($return) { $string .= $text; }
	    	else { print $text; }
                 
	        $index++;
	    }
        
        return $string;
	}
	
	public static function buildNumOpts($n = 0, $s = 0, $int = 1, $def = "- Select One -", $defopt = true, $return = false)
	{
        $string = '';

		if($defopt)
		{
	    	$text =  "<option value=\"default\"  selected>$def</option>\n";
            if($return) { $string .= $text; }
	    	else { print $text; }
	    }
	    
	    
	    for ($i = 0; $i < $n; $i++)
	    {
	        $text =  "\t\t\t\t\t\t\t<option value=\"$s\">$s</option>\n ";
            
            if($return) { $string .= $text; }
	    	else { print $text; }
            
	        $s+=$int;
	    }
        
        return $string;
	}
    
    public static function camelize($str='') 
    {
        return str_replace(' ', '_', ucwords(str_replace('-', ' ', $str)));
    }
    
    /**
    * Convert SalaDeImprensa to sala_de_imprensa
    * @param	$str		string
    * @return	string
    */
    
    public static function uncamelize($str='')
    {
    	return preg_replace('@^_+|_+$@', '', strtolower(preg_replace("/([A-Z])/", "_$1", $str)));
    }
    
    /**
    * Convert the first char to lower case
    * @param	$str		string
    * @return	string
    */
    
    public static function lcfirst($str="") 
    {
        if ($str=='') return '';
        $str{0} = strtolower($str{0});
        return $str;
    }

	
	
	static function getConstrainedDims($width, $height, $maxw, $maxh)
	{
		
		$dims = new stdClass();
		//is width larger?
	    if ( Jk_Functions::getRatio($width, $height) > 1 )
	    {
	    		$ratio = Jk_Functions::getRatio($height, $width);
				$dims->width 	= $maxw;
				$dims->height 	= $maxw*$ratio;
				
				//Jk_Functions::trace("CRUNCHING LANDSCAPE IMAGE: $height/$width : RATIO =  $ratio");
	    }else
		{
				$ratio = Jk_Functions::getRatio($width, $height);
				$dims->width 	= $maxh*$ratio;
				$dims->height 	= $maxh;
				
				//Jk_Functions::trace("CRUNCHING PORTRAIR IMAGE: $width/$height : RATIO =  $ratio");
		}
		
		return $dims;
			
    }


	public static function fixPathSlashes($path)
    {
        return( str_replace("//", '/', str_replace("\\", '/', $path )) );
    }
	
	//// reads folder and returns array (indexed items, index count)
	static function readFolder($_f, $_inc = array("jpg", "jpeg", "png", "gif"), $_exc = array ('php', 'htaccess'), $_fexc = array())
	{
	    $_folder = opendir(self::fixPathSlashes("$_f") );
	    $_inctypes = $_inc;
	    $_exctypes = $_exc;
	    $_excfiles = array('.', '..');
	    $_index = array();
	    //
	    $_excfiles  = array_merge($_excfiles, $_fexc);
	    
	    while ($_file = readdir($_folder))
	    {
	        $_type = substr(strtolower($_file), strrpos($_file, ".") + 1);
	        $_name = substr(strtolower($_file), 0, strrpos($_file, "."));
	        
	        
	        if(!in_array($_name, $_excfiles) )
			{
				
		        if ( !in_array($_type, $_exctypes) && in_array($_type, $_inctypes) )
		        {
		            array_push($_index, $_file);
		        }
	        }
	    }
	    
	    closedir($_folder);
	    ///////

        sort($_index);
	    
	    return ($_index);
	}
    
    
    	//// reads folder and returns array (indexed items, index count)
	static function readFolderAll($_f, $_fexc = array() )
	{
	    $_folder = opendir("$_f");
	    $_excfiles = array('.', '..');
	    $_index = array();
	    //
	    $_excfiles  = array_merge($_excfiles, $_fexc);
	    
	    while ($_file = readdir($_folder))
	    {
	        $_name = substr(strtolower($_file), 0, strrpos($_file, "."));	        
	        
	        if( !in_array($_name, $_excfiles) && $_file != '.')
			{
		        array_push($_index, $_file);
	        }
	    }
	    
	    closedir($_folder);
	    ///////
	    
	    return ($_index);
	}
    
	
	static function readFolders($_f, $_ea = '')
	{
	    $_types = array("admin", "administrator", "members", "styles", "banners", "flash", ".htaccess");
        
		foreach ($_ea as $_a)
	    {
	        array_push($_types, "$_a");
	    }
	
	    $_index = false;
        
        if( $_folder = opendir("$_f") )
        {
            $_index = array();
            
    	    while ($_file = readdir($_folder))
    	    {
    	        if ( is_dir($_file) && $_file != '.' && !in_array($_file, $_types))
    	        {
    	            array_push($_index, $_file);
    	        }
    	    }
            
    	    closedir($_folder);
	    }
        
	    return $_index;
	}


	static function getRandomItem( $a = array() )
	{
	    return ( $a[rand(0, count($a) - 1)] );
	}

	
    static function getRatio($m1, $m2)
	{
	    return round( $m1 / $m2, 3 );
	
	}
	
    
    
	static function arr2str($a = array(), $glue = '#')
	{       
        return implode($glue, $a);
	}
	
	
	/**
	 * Strip any unsafe tags/chars/attributes from input values.
	 *
	 * @param  string  Value to be cleaned
	 * @param  boolean Strip \r\n ?
	 * @return string
	 */
	static function sanitize($value, $strip_crlf = true, $strip_tags = false)
	{
		//self::getInstance();
        ///*
       
        /*
        '@<[\/\!]*?[^<>]*?>@si';
        '@&(?!(#[0-9]+|[a-z]+);)@si'
        '@<style[^>]*?>.*?</style>@si',
        */
        
	    // Some of what we have in the $search array may not be needed, but let's be safe.
	    $search = array('@<script[^>]*?>.*?</script>@si', '@<applet[^>]*?>.*?</applet>@si', '@<object[^>]*?>.*?</object>@si', '@<iframe[^>]*?>.*?</iframe>@si', '@<form[^>]*?>.*?</form>@si');
	
	    if ($strip_crlf)
	    {
	        $search[] = '@([\r\n])[\s]+@';
	    }
	    
	    //self::$log->LogInfo("FUNCTIONS:sanitize => BEFORE: $value");
	    $value = preg_replace($search, '', $value);
	    
        
        if($strip_tags) $value = strip_tags($value);
        
        
	    

        //$value = trim( addslashes($value) );
	    
	    //self::$log->LogInfo("FUNCTIONS:sanitize => AFTER: $value");
	    
	   	return ($value);
	}

	/**
	 * Trim and strip slashes from data
	 *
	 * @param  string  Data to be cleaned
	 * @return string
	 */
	static function clean($data)
	{
	    return trim(stripslashes($data));
	}
	
	/**
	 * Tests for a valid email address
	 *
	 * @param  string  Email address
	 * @return boolean
	 */
	static 	function is_valid_email($email)
	{
		$valid = false;
		
	    if (preg_match('#^[a-z0-9.!\#$%&\'*+-/=?^_`{|}~]+@([0-9.]+|([^\s\'"<>]+\.+[a-z]{2,6}))$#si', $email))
	    {
	         if( !self::is_email_injection($email) )  $valid = true;
	    }
	    
	    return $valid;
	}
	
	/**
	 * Tests input data from the contact form for email injection - very basic
	 *
	 * @param  string  Data to check
	 * @return boolean
	 */
	static function is_email_injection($data)
	{
	    if (preg_match('#(To:|Bcc:|Cc:|Content-type:|Mime-version:|Content-Transfer-Encoding:|\\r\\n)#i', urldecode($data)))
	    {
	        return true;
	    }
	    return false;
	}

	
	/**
	 * Checks input values from the contact form for a set number of links.
	 * Can be useful to catch someone trying to spam you.
	 *
	 * @param  string  Value to check
	 * @return boolean
	 */
	static function is_spam($value, $numlinks)
	{
	    preg_match_all('#(<a href|\[url|http:\/\/)#i', $value, $matches, PREG_PATTERN_ORDER);
	
	    if (count($matches[0]) > SPAM_NUM_LINKS)
	    {
	        return true;
	    }
	    return false;
	}
	

	/**
	*Get the users ip address
	*
	*@return string
	*/
	static function get_ip()
	{
	    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	    {
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    } else
	        if (isset($_SERVER['HTTP_CLIENT_IP']))
	        {
	            $ip = $_SERVER['HTTP_CLIENT_IP'];
	        } else
	            if (isset($_SERVER['HTTP_FROM']))
	            {
	                $ip = $_SERVER['HTTP_FROM'];
	            } else
	            {
	                $ip = $_SERVER['REMOTE_ADDR'];
	            }
	            return $ip;
	}
	
	
	private static function trace($_m)
	{
		Jk_Functions::getInstance();
		
		if (self::DEBUGGING)
		{
			self::$log->LogInfo(get_class(self) . " => $_m");
		}
	}
	
} // END OF FUNCTIONS CLASS //

// ?>