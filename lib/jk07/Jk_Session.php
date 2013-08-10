<?php

class Jk_Session extends Jk_Base
{

	private static $instance = null;	
	private static $session_started 	= false; 		 
    private static $user = false;

 	/**
     * Singleton instance
     *
     * @return Jk_Session;
     */
     
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public function __construct()
	{
	   self::start();
	}
    
    
    private static function init($_force = false)
    {
		session_start();
		self::$session_started = true;
 		self::set('start_time', time());
    }
    
    
    #### static  functions ####
   	public static function start()
	{
		if(self::$session_started) return;
 		
        self::init();
		return true;
    }
    
    public static function restore($id)
    {
		session_write_close();
    	session_id($id);

    	self::init(true);
    }
    
    
    public static function restoreFromCookie()
    {
    	if( $_COOKIE['PHPSESSID'] )
        {
            session_id( $_COOKIE['PHPSESSID'] );
            self::init();

            return true;
        }

        return false;
    }

    
    public static function destroy()
    {
		$_SESSION[] = array();
		session_destroy();
	}

    public static function getId()
    {
        return session_id();
    }


    public static function set($_key, $_val)
    {
        self::start();
        $_SESSION[$_key] = $_val;
    }

    public static function get($_key)
    {
        self::start();
        if( isset($_SESSION[$_key]) ) return $_SESSION[$_key];
        return false;
    }

    public static function kill($key)
    {
        self::start();
        if( isset($_SESSION[$key]) ) unset( $_SESSION[$key] );
    }

    #### user helper functions ####
    /**
     * @return Cartext_User
     */
    public static function getUser()
	{
        return self::getLocalUser();
	}
    
    
    public static function setUser(Jk_User $jk_user)
    {
        self::setLocalUser($jk_user);
    }

    
    private static function getLocalUser()
    {
        if(!self::$user)
        {
            self::$user = JK_User::restoreUserFromSession();
        }
        
        return self::$user;
    }
    
    private static function setLocalUser(Jk_User $jk_user)
    {
        self::$user = $jk_user;
    }

   
    
} // END OF CLASS

?>