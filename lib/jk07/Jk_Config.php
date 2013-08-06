<?php //

class Jk_Config
{
	
    // Db connection
    public $host            = '10.48.113.8';
    public $db             	= 'dahliawolf_v1_2013';
    public $user			= 'offlineadmin';
    public $pass           	= '9w8^^^qFtwCD7N^N^';
    public $env				= 'dev';
    
    // server web root
    const BASE_URI          = '/';

    //google analytings acc
    const TRACKING_ACC      = "12346";
    
    
    //set default user model
    const USER_MODEL        = "Jk_User";
    
    
    // response controller db or file //
    const RESPONSE_SOURCE   = 'file';
    const RESPONSE_ORDER    = 'file,db';
    
    //email settings
    const SEND_FROM_EMAIL   = 'no-reply@dahliawolf.com';
    const SEND_FROM_NAME    = 'Admin';
    const SEND_FROM_SITE    = 'dahliawolf';
    
    const SMTP              = false;
    const SMTP_PORT         = 25;
    const SMTP_HOST         = "localhost";
    const SMTP_USER         = "";      // SMTP server username
    const SMTP_PASS         = "";      // SMTP server password


    const ERROR_NO_INDEX    = '<span style="font: bold 24px sans-serif; color:red">app has no index interface</span>';
    
    
    const APP_PREFFIX       = ''; // used to map models
    
    const GLOBAL_DEBUG		= true;

    const AUTO_CREATE_TABLES  = false;
    
    
    public static $instance = null;
 	public function __construct()
    {
    		$domain = intval(strpos( Jk_Request::getRawHost(), 'local'));
    		
    		if( $domain < 0 )
    		{
    			//use default settings (dev)
    			//Jk_Base::debug('dev');
    			
    		}else
            {
    			/*
    			`// live server
    			//Jk_Base::debug('prod');
    			$this->env 		= 'dev';
    			$this->host 	= '10.48.113.8';
    			$this->db 		= 'dev_dahliawolf';
    			$this->user 	= 'dev_dahliawolf';
    			$this->pass 	= 'password';
    			*/
    		}
    		
    }
    
    
    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

	
} // END CLASS //


?>