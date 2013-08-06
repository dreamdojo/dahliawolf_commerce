<?php

Jk_Loader::loadClass("interfaces#IExecutable");


class SSL_Controller extends Jk_Root implements IExecutable
{
	private $request_command;
	
	public function __construct()
	{
		
	}
	
	public function execute(&$request_command)
	{
		$this->request_command = $request_command;
		
		return self::SSLControl();
	}
	
    protected function SSLControl()
    {
        if( !$this->request_command ) return false;
        
        $qs         = Jk_Request::getQueryString() == '' ? '' : '?' . Jk_Request::getQueryString();
        $redirect   = false;
        
        if(Jk_Request::isSSL() == false && $this->request_command->requireSSL() == true)
        {
            $redirect = Jk_Request::getSSLHost() . Jk_Request::getRequesturi() . $qs;
            
        }else if(Jk_Request::isSSL() == true && $this->request_command->requireSSL() == false)
        {
            $redirect = Jk_Request::getHttpHost() . Jk_Request::getRequesturi() . $qs;
        }
        
        if($redirect)
        {
            Router::redirect( $redirect );
            return true;
        }
        
        return false;
    }

	
}