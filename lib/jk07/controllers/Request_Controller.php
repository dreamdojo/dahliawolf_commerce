<?php

//Jk_Loader::loadClass("interfaces#IExecutable");

class Request_Controller extends Jk_Root
{
	protected $default_command;
	protected $command;
	protected $request_string;
	
	public function __construct() 
	{
		
	} 
	
	
    //usually we want to print some errors.. do it in this controller
	public function getDefault()
	{
		$controller = 'Default';
		$controller = Jk_Functions::camelize( $controller ) . '_Controller';
		
		//if(Jk_Config::APP_PREFFIX != '' ) Jk_Functions::camelize( Jk_Config::APP_PREFFIX ) ."_" . $controller;
        Jk_Loader::loadController($controller);
		
		if ( class_exists( $controller ) ) $obj = new $controller();
		
		Jk_Base::debug("INFO: loading DEFAULT $controller controller");
		
		$obj->addMessage('warning', 'no Controller Here pal!, Im taking over');
		$obj->addMessage('info', 'from Default_Controller loadDefault()');
		
		$command = new Response_Command();
		$command->setController($obj);
		$command->setAction( "index");
            	
		return $command;
	}


    private function addErrorHeaders()
    {
        header("JkX-notfound: " . Jk_Request::getRequesturi() );
    }
	
	
	public function getCommand()
	{
        if( strtolower( Jk_Request::getRequestParts(0) ) == 'lib' )
        {
            //var_dump(Jk_Request::getRequestParts(0));
            self::addErrorHeaders();
            return null;
        }

		$uri    = trim( strtolower( Jk_Request::getRequesturi() ), '/'); // get rid of trailing /
		
        $dotpos = strripos($uri, '.');
        $this->request_string  = $dotpos > 0 ? substr($uri,0, $dotpos ) : $uri; // get rid of .
        
        //// if request is json reroute request to Facade adapter /////
        if( Jk_Request::isJson())
        {
        	$this->command = $this->facadeAdapter();
        }else {        	
        	$this->command = $this->controllerAdapter();
        }
        
		if( !$this->command ) $this->command = self::getDefault();
        
        return $this->command;
        
	}
	
	
	protected function controllerAdapter()
	{
		$parts		= explode('/', $this->request_string);
		
		$controller	= count($parts) > 1 ? $parts[0] : "Index";
		$action		= strtolower( count($parts) > 1 ? $parts[1] : $parts[0] );
		
		$controller = Jk_Functions::camelize( $controller ) . '_Controller';
		
		//var_dump($controller);
		//var_dump($action);
		
	
		if(Jk_Config::APP_PREFFIX != '' ) Jk_Functions::camelize( Jk_Config::APP_PREFFIX ) ."_" . $controller;
        
        Jk_Loader::loadController($controller);
        
        
        if ( class_exists( $controller ) )
        {   
            $obj = new $controller();
            $command = new Response_Command();
            $command->setController($obj);
            $command->setAction( $action);

            /*
            if(  !is_callable( array($obj, $command->getAction() )) || !method_exists($obj, $command->getAction()) )
            {            	
                Jk_Base::debug("ERROR: action '$action' not found on $controller controller, default to index");
                $command->setAction($this, 'index');
            }
            */
            
        } else
        {
            Jk_Base::debug("ERROR: controller $controller not found");
        }

        return $command;

	}
	
	protected function facadeAdapter()
	{
		$parts	= explode('/', $this->request_string);
		
		$model	= count($parts) > 1 ? $parts[0] : $this->request_string;
		$action	= strtolower( count($parts) > 1 ? strtolower($parts[1]) : strtolower( Jk_Request::getVar('q') ) );
		
		$facade =   Jk_Functions::camelize( $model ) . '_Facade';
        
		if(Jk_Config::APP_PREFFIX != '' ) Jk_Functions::camelize( Jk_Config::APP_PREFFIX ) ."_" . $facade;
        
        Jk_Loader::loadModel($model);
        
        if ( class_exists( $facade ) )
        {
            $obj = new $facade();
            $command = new Response_Command();
            $command->setController( $obj);
            $command->setAction($action);

            /*
            if(  !is_callable( array($obj, $command->getAction() )) || !method_exists($obj, $command->getAction()) )
            {
                $command->setAction($this, 'index');
                Jk_Base::debug("ERROR: action '$action' not found on $model facade");
            }
            */
            
        } else
        {
            Jk_Base::debug("ERROR: facade $facade not found");
        }
        
        return $command;
        
	}
	
 
	
}//End of class
?>