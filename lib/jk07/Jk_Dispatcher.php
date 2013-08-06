<?php
  
class Jk_Dispatcher extends Jk_Base
{
	public static $instance = null;
    
	private $request_command;
    private $response_command;
    
    private $request_controller;
    private $access_controller;
    private $SSL_controller;
    
    private $response;
    
    
    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    

	public function __construct()
	{        
	}


	public function setAccessController($controller)
	{
		$this->access_controller = $controller;
	}
	
	
	public function setSSLController($controller)
	{
		$this->SSL_controller = $controller;
	}
	
	
	public function setRequestController($controller)
	{
		$this->request_controller = $controller;		
	}
 
    
    private function enforceSSLControl()
	{
        // SSL control //        
		if($this->SSL_controller == null) $this->SSL_controller = new SSL_Controller();
		
		$this->SSL_controller->execute($this->request_command);
	}
    
	private function enforceAccessControl()	
	{
		if($this->access_controller == null) $this->access_controller = new Jk_Access_Controller();
		
		$this->access_controller->execute($this->request_command);
	}
    
	
	private function getRequestCommand()
	{
		if($this->request_controller == null) $this->request_controller = new Request_Controller();
		
		$this->request_command = $this->request_controller->getCommand();
		
		
	}
    
	//render controller->views
	public function render()
	{
        self::getRequestCommand();
        
        //ssl control request will redirect 
		self::enforceSSLControl();
		self::enforceAccessControl();

		$this->response_command = $this->request_command;

		if( !$this->response_command )
        {
            $this->response_command = $this->request_controller->getDefault();
        }

        $this->response	= new Response();
		$this->response->setCommand($this->response_command);

        // everything excecute response
		$content = $this->response->render();

        self::setAppHeaders();

        if($content) echo $content;

    }
	
	
	private function setAppHeaders()
	{
		$inittime = EXECUTION_INIT;
		 
		header("JkX-db-queries: " . Jk_Db::getTotalQueries() );
		header("JkX-db-time: ". sprintf("%01.8f secs", Jk_Db::getTotalExecution()) );
		header("JkX-render-time: " . Jk_Base::getTotaltime( $inittime, false) );
		 
	}
	
    
}// END CLASS


?>