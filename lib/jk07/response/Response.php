<?php
	
    class Response extends Jk_Base
    {        
        private $command;
        private $response_adapter;
        private $content;
        
        
        public function __construct(Response_Command $command = null)
        {
            if($command) self::setCommand($command);
        }
        
        
        public function setCommand(Response_Command $command)
        {                        
            $this->command = $command;
            $this->response_adapter = self::getResponseAdapter();
        }
		
    	public function getResponseAdapter()
		{
			if( $this->command->isJson() )
			{
				return new Json_Response($this->command);
			}
	
			return new Html_Response($this->command);
		}
        
		
        public function render()
        {
            
            $content = $this->response_adapter->render();
            
            self::setContentType();

            //self::debug( "Response: render()" . $content );
            
            return $content;
        }
        
        
        private function setContentType()
        {
        	header("Content-type: " . $this->response_adapter->getContentType() );
        	//header("Content-Length: " . strlen($this->content) );
        }
        
        
    }//END OF CLASS
    
?>