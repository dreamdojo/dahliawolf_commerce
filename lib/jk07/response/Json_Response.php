<?php

class Json_Response extends Abstract_Response
{
    public $success = false;
    public $data = false;
    public $messages = null;
    public $debug = null;


    public function __construct(Response_Command $command)
    {
    	$this->content_type = 'application/json; charset=UTF-8';
    	$this->command = $command;
    }

    
	public function render()
    {
        $this->success = $this->execute();
        $this->addTimers();

        Jk_Loader::loadClass( 'json#json_format' );

        //// any print/echos will be appeded to the trace variable
        $bcontent = trim(ob_get_clean());
        ob_end_clean();
        if($bcontent != '') $this->trace = $bcontent;


        $custom_ct = $this->setContentType( $this->data->content_type );
        if ( $custom_ct ) unset( $this->data->content_type );

        
        $content = json_format::toString( @json_encode( $this ) );

        //custom encoding return
        if($custom_ct) return $content;

        ////////
        if ( self::checkClientGzEncode() )
        {
            //client does not support gzip so send plain text;
            $this->content_type = 'text/plain; charset=UTF-8';
        }
        else
        {
            $content = gzencode($content, 9, true);
            $this->addEncodingHeaders();
        }

        //self::debug($this->content_type);
        //self::debug($content);



        return $content;
    }


    private function checkClientGzEncode()
    {
        if( !empty( $_SERVER["HTTP_ACCEPT_ENCODING"]) && strpos("gzip",$_SERVER["HTTP_ACCEPT_ENCODING"]) === NULL ) return false;
        return true;
    }
    
    private function addEncodingHeaders()
    {
        header("Content-Encoding: gzip");
    }
    
    private function addTimers()
    {
    	$inittime = EXECUTION_INIT;
    	 
    	$this->addMessage("JkX-db-queries", 	Jk_Db::getTotalQueries() );
    	$this->addMessage("JkX-db-time", 		sprintf("%01.6f secs", Jk_Db::getTotalExecution()) );
    	$this->addMessage("JkX-render-time",	Jk_Base::getTotaltime( $inittime, false) );
    	
    	$this->debug = self::getMessages();
    }


} //END OF CLASS


?>