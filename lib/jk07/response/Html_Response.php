<?php

class Html_Response extends Abstract_Response
{
	public $title = 'cartext';
    public $meta_tags = '';

    private $APP_VIEWS = APP_VIEWS;

    private $contents = '';
	private	$controller;

    private $template;
    private $view;
    
	public $data;
	public $messages;
	public $post_uri;

    public $host = '';


    function __construct( Response_Command $command )
    {
        $this->host         = Jk_Request::getHost();
        $this->command		= $command;

        $this->content_type = 'text/html';

        $this->post_uri 	= Jk_Request::getHost() . Jk_Request::getRequesturi();
    }

    protected function execute()
    {
    	$ok = $this->command->execute();
        $this->controller	= $this->command->getController();

    	$this->data = $this->command->getData();
    	$this->messages = $this->command->getMessages();

        return $ok;
    }

    //render view
    public function render()
    {
        $this->execute();

        ////make local vars////
        $this->addVars();

        $this->view         = $this->controller->getView();

        $rawcontent = $this->controller->getRawContent();

        if( $rawcontent != null )
        {
            $this->contents = $rawcontent;

            ob_clean();

            $this->setContentType( $this->view->getContentType() );
            if( @get_resource_type($this->contents) )
            {
                self::renderResource($rawcontent);
                $rawcontent = true;
            }

            return $rawcontent === true ? null : $this->contents;
        }


        $this->template     = $this->controller->getTemplate();

        $bcontent = ob_get_clean();
        if(strlen($bcontent) > 0)
        {
            @ob_end_clean();
            $bcontent = trim($bcontent);
        }

        if( $this->template )
        {
            $this->contents     = $this->load( $this->view->getTemplate() );


            /// sync view css and js modules queued in controller first
            $this->syncCSSModules();
            $this->syncJSModules();


            $main_content       = $this->load( $this->view->getFile() );

                
            $this->setContentVar('dynamic_content',     $main_content);


            $this->setContentVar('page_title',          $this->getViewTitle() );
            $this->setContentVar('page_tracker',        $this->trackView() );
            $this->setContentVar('js_includes',			$this->getJSincludes() );
            $this->setContentVar('css_includes',        $this->getCSSincludes() );
            $this->setContentVar('error_stack',         $this->printMessages() );

        }else
        {
            $main_content   = $this->load( $this->view->getFile() );
            $this->contents = $main_content;
        }

        //injected on <!--vars--> into content stream
        $this->injectVars();


        $this->addTimers();

        // print anything outputed in buffer
        $this->contents .= "\n<!-- doc trace -->\n".$bcontent;

        // print any messages to the trace
        if($this->messages) $this->contents .= "\n<!-- message stack -->\n" . $this->printMessages();

        return $this->contents;
    }

    protected function getViewTitle($html=true)
    {
        return $this->view->getTitle();
    }

    protected function syncCSSModules()
    {
        $modules = $this->view->getCSSModules();

        if( count( $modules) > 0)
        {
            foreach($modules as $module) $this->getCSSModule($module);
        }
    }

    protected function syncJSModules()
    {
        $modules = $this->view->getJSModules();

        if( count( $modules) > 0)
        {
            foreach($modules as $module) $this->getJSModule($module, false);
        }
    }

    public function setContentVar($var = 'cvar', $content = '')
    {
        $this->contents = str_replace("<!--$var-->",  $content, $this->contents);
    }

    public function addContentVar( $var, $content)
    {
        if(!$var|| !$content) return;
        self::addData('inject', array($var => $content));

        //var_dump(self::getData());
    }

    protected function injectVars()
    {
        self::mergeData(self::getData(),$this->data, true);
        //var_dump($this->data);

        if($this->data->inject)
        {

            
            foreach($this->data->inject as $var => $val)
            {
                //var_dump( $var );
                //var_dump( $val );

                if(is_numeric($var))
                {
                    $e = "ERROR: var value is numeric need to pass as array('var' => 'val')";
                    Jk_Base::debug($e);
                    $this->addMessage('error',$e);
                    continue;
                }



                $this->setContentVar("$var", $val );
            }
        }
    }


    protected function addVars()
    {
       if($this->data->variables)
        {
            foreach($this->data->variables as $var => $val)
            {
                if(is_numeric($var))
                {
                    $e = "ERROR: var value is numeric need to pass as array('var' => 'val')";
                    Jk_Base::debug($e);
                    $this->addMessage('error',$e);
                    continue;
                }

                $this->$var = $val;
            }
        }
    }


	private function addTimers()
    {
    	$inittime = EXECUTION_INIT;
    	$timers = "\n";    	 
    	$timers .= "<!-- JkX-db-queries: ". 	Jk_Db::getTotalQueries() ."-->\n";
    	$timers .="<!-- JkX-db-time: ". 		sprintf("%01.8f secs", Jk_Db::getTotalExecution()) . "-->\n";
    	$timers .="<!-- JkX-render-time: ".		Jk_Base::getTotaltime( $inittime, false) ."-->\n";
    	
    	$this->contents .= $timers;
    }

    
    private function printMessages()
    {
        $return = '';
        $break = "";
        $print = false;
        
        $errors = $this->getMessages();
        
        if( !$errors ) return;
        
        foreach($errors as $error)
        {
            $print = true;
            break;
        }

        if($print)
        {
            foreach($errors as $key => $error)
            {
                if(is_array($error))
                {
                    $return .= "<div><strong>{$key}s</strong><br>\n";
                    foreach($error as $chunk) {
                        $return .= "<pre style='text-indent: 20px'>{$break}{$chunk}";
                        $break = "</pre><br>\n";
                    }
                    $return .= "</div>";
                }else
                {
                    $return .= "{$break}{$error}";
                    $break = "<br>\n";
                }

            }
            
            $return = "\n<div id=\"message_stack\">\n" . $return . "\n</div>\n";
        }
        
        return $return;
    }

    
    public function setCSS($a)
    {
        if($this->view) $this->view->setCSS($a);
		
    }


    public function setJS($a)
    {
        if($this->view) $this->view->setJS($a);
    }

    
    public function getJSModule($module = '', $load_css = true, $addtime= true, $head_module=false)
    {
        if($module == '' || !$module) return;

        $tab = "     ";

        $return = "\n";
        //// GET JS MODULE FILES ////	/*

        $_web_lib = Jk_Functions::fixPathSlashes(WEB_LIB);
        $_module = "js/$module";

        // if module is single file..
        if( strpos($_module, '.js') )
        {
            $_hsrc = Jk_Request::getHttpHost() . "lib/$_module"  . ($addtime ? "?" . filemtime($_web_lib . "$_module") : '');
            $return .= "$tab<script type=\"text/javascript\" src=\"$_hsrc\"></script> \n";
        }else
        {
            $_fldr = $_web_lib. "$_module/";
            $_indx = Jk_Functions::readFolder($_fldr, array('js'), array('htaccess'), array('local-urchin', 'swfo'));

            foreach ($_indx as $_file)
            {
                $_hsrc = Jk_Request::getHttpHost() . "lib/$_module/$_file?"  . filemtime($_web_lib . "$_module/$_file");
                $return .= "<script type=\"text/javascript\" src=\"$_hsrc\"></script> \n";
            }
        }


        if($head_module)
        {
            $return .= "$tab<!--head_js_modules-->\n";
            $this->setContentVar('head_js_modules', $return );
        }else
        {
            $return .= "\n<!--js_modules-->\n";
            $this->setContentVar('js_modules', $return );
        }


        if($load_css) $this->getCSSModule($module);

        return $return;
    }


    public function getCSSModule($module = '')
    {
        if($module == '' || !$module) return;

        $tab = "     ";
        $return = "";
        //// GET CSS MODULE FILES ////	/*

        $_web_lib = Jk_Functions::fixPathSlashes(WEB_LIB);
        $_module = "css/$module/css";
        $_fldr = $_web_lib . "$_module";
        $_indx= Jk_Functions::readFolder($_fldr, array('css', 'php'), array('htaccess'));

        foreach ($_indx as $_file)
		{
		    $_hsrc = Jk_Request::getHost() . "lib/$_module/$_file?" . filemtime($_web_lib . "$_module/$_file");
		    $return .= "$tab<link type=\"text/css\" rel=\"stylesheet\" href=\"$_hsrc\"  /> \n";
        }

        $return .= "\n$tab<!--css_modules-->\n";
        
        $this->setContentVar('css_modules', $return );
        
        return $return;
    }


    private function getCSSincludes()
    {
        $includes = $this->view->getCSS();
        $content ='<!--css_includes-->';
        

        if( count( $includes) > 0)
        {
            $content = '';

            foreach($includes as $include)
            {
                $fname      = basename($include);
                $content   .= str_replace( '</style>', "\n", str_replace('<style>', "\n/*$fname*/\n", $this->load($include) ));
            }

            $content  = "\n<style>\n". $content . "\n</style>\n<!--css_includes-->";
        }

        return $content;
    }
    
    
    private function getJSincludes()
    {
        $includes   = $this->view->getJS();
        $content    ='<!--js_includes-->';


        if( count( $includes) > 0)
        {
            $content = '';

            foreach($includes as $include)
            {
                $fname      = basename($include);
                $content   .= "\n<!--/*$fname start*/-->\n" . str_replace( '</script>', "\n", str_replace('<script>', "", $this->load($include) )) . "\n<!--/*$fname end*/-->\n";
            }

            $content  = "\n<script>\n". $content . "\n</script> \n<!--js_includes-->";
        }

                
        return $content;
    }
    
        
	
	public function makeMetaKeywords()
	{
		return implode(', ', $this->view->getTags());
	}

    
    private function load( $file)
	{
		$file = $this->APP_VIEWS . $file;
        
		if( file_exists($file) && is_dir($file) == false )
		{
            ob_start();
			include_once($file);
            $content = ob_get_clean();

		}else
		{
            Jk_Base::debug("INFO: error loading view file $file");
            $file = basename($file);
			$content =  "<!-- The file $file was not found --> \n";
		}
        
        return $content;
	}
	
	
	public function trackView()
	{
        $tracker = '';
        
        if(@$this->link->track_page)
        {
			$tracking_acc = Jk_Config::getInstance()->tracking_acc;
			 
			$tracker =  "\n\t
            <script type=\"text/javascript\">
				var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
				document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
			</script>
			
			<script type=\"text/javascript\">
				try {
				var pageTracker = _gat._getTracker(\"$tracking_acc\");
				pageTracker._trackPageview();
				} catch(err) {}
			</script>
			";
        }
        
        return $tracker;
	}	

	
} // END CLASS


?>