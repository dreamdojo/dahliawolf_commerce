<?php


abstract class Jk_Base extends Jk_Root
{
    private static $app_log = null;
    private static $app_file =  "logs/main_log.txt";

    protected $message_stack = null;
    protected $data_stack = null;
    protected $sync_objects = array();


    private function initStacks()
    {
        if( $this->data_stack == null ) self::flushData();
        if( $this->message_stack == null) self::flushMessages();
    }

    //// data ////
    protected function flushData()
    {
        $this->data_stack  = new stdClass();
    }


    public function syncData($oobj)
    {
        $this->initStacks();
        if( $oobj != null) self::mergeData($oobj->getData(), $this->data_stack, true);
    }

    public function getData()
    {
        $this->initStacks();

        if($this->sync_objects)
        {
            foreach($this->sync_objects as $obj) self::syncData($obj);
        }
        
        return $this->data_stack;
    }

    public function addData($name, $details)
    {
        $this->initStacks();
        self::addVarData($this->data_stack, $name, $details);
    }


    public function rmData($name)
    {
        if($this->data_stack->$name) unset($this->data_stack->$name);
    }

    //// messages /////
    public function getMessages()
    {
        $this->initStacks();

        if($this->sync_objects)
        {
            foreach($this->sync_objects as $obj) self::syncMessages($obj);
        }
        
        return $this->message_stack;
    }

    protected  function flushMessages()
    {
        $this->message_stack = new stdClass();
    }


    public function addMessage($name, $details)
    {
        $this->initStacks();
        self::addVarData($this->message_stack, $name, $details);
    }


    public function syncMessages($oobj)
    {
        $this->initStacks();
        if( $oobj != null) self::mergeData($oobj->getMessages(), $this->message_stack, true);
    }


    private function addVarData(&$localvar, $name, $details)
    {
        if( @$localvar->$name)
        {
            if( is_array($localvar->$name) && is_array($details) )
            {
                $localvar->$name = array_merge($localvar->$name, $details);
            }
            else
            {
                is_array($localvar->$name) ? array_push($localvar->$name, $details) : $localvar->$name = array($localvar->$name, $details);
            }
        }
        else
        {
            $localvar->$name = $details;
        }
    }

    public function addDataObject($object)
    {
        if(method_exists($object, 'getMessages')) $this->sync_objects[] = $object;
    }

    ////////////////////////////////
    public static function mergeData( $oobj, &$dobj, $add = false, $linear=false)
    {
        if( $oobj == null )
        {
            Jk_Base::debug("ERROR DONT SEND NULL OBJECTS TO MERGE \n" . Jk_Base::getDebugStack());
            return;
        }

        if( $dobj == null)
        {
            Jk_Base::debug("ERROR DEST OBJECT IS NULL DATA WILL NOT MERGE \n" . Jk_Base::getDebugStack());
            return;
        }

        $isarray = is_array($dobj);

        if($isarray) $dobj = (object) $dobj;

        $dvars = @get_object_vars( $dobj );

        foreach($oobj as $var => $val)
        {
            if($add)
            {
                if(@is_array($dobj->$var) && @is_array($val))
                {
                    $dobj->$var = array_merge($dobj->$var, $val);
                }
                elseif( $dobj->$var )
                {
                    if( @is_array($dobj->$var) )
                    {
                        array_push($dobj->$var, $val);
                    }else
                    {
                        // IF its linear and var already exists the old var its overriden NOT pushed into dymanic array
                        $linear==false ? $dobj->$var = array($dobj->$var, $val) : $dobj->$var = $val;
                    }
                }else{
                    $dobj->$var = $val;
                }
            }else
            {
                if(array_key_exists($var, $dvars))
                {
                    $dobj->$var = $val;
                }
            }
        }

        if($isarray) $dobj = (array) $dobj;
    }

    public static function getTime()
    {
        list($usec, $sec) = explode(' ',microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function getTotaltime(&$start, $float = false)
    {
        $end = self::getTime();
        $time = $end - $start;

        if($float) return $time;

        $formatted = sprintf("%01.6f secs", $time);

        return $formatted;
    }


    public static function getDebugStack()
    {
        $retstring = '';
        $backtrace = debug_backtrace();
        for($ii = 1; $ii < count($backtrace); ++$ii)
        {
            $retstring[] = @basename(isset($backtrace[$ii]['file'])?$backtrace[$ii]['file'] : "") . ' - ' .
                            @$backtrace[$ii]['function'] .
                             ' (' . @(isset($backtrace[$ii]['line']) ? $backtrace[$ii]['line'] : "") . ')';
        }
        return "function stack \r\n".  implode(" \r\n", $retstring);
    }


    public static function getCallee($depth=2)
    {
        $retstring = '';
        $backtrace = debug_backtrace();

        $file = ($backtrace[$depth-1] && isset($backtrace[$depth-1]['file']) ? $backtrace[$depth-1]['file'] : "FILE ");
        $function = @(isset($backtrace[$depth]['function']) ? $backtrace[$depth]['function'] : "");
        return sprintf("%s -> $function()", basename($file));
    }

    public static function getCalleeObject($depth=2)
    {
        $backtrace 	= debug_backtrace();
        $object 	= $backtrace[$depth]['object'];
        if (is_object($object))
        {
            return get_class($object);
        }
        return null;
    }


    public static function debug($m, $verbose = false, $file = null )
    {
        if($file == null) $file = Jk_Base::$app_file;

        //// GLOBAL DEBUGGIN ON MAIN LOG ////
        if (Jk_Config::GLOBAL_DEBUG == false) return;

        if(Jk_Base::$app_log == null)
        {
            Jk_Base::$app_log = new Jk_Logger(APP_PATH . $file, Jk_Logger::DEBUG);
        }

        if(is_array($m) || is_object($m)) $m =  var_export($m, true);

        Jk_Base::$app_log->LogDebug( Jk_Base::getCallee() . " => $m" );

        if($verbose)
        {
            self::debug(self::getDebugStack(), false);
        }
    }


    protected function resetMainLog()
    {
        Jk_Base::$app_log = new Jk_Logger(APP_PATH . Jk_Base::$app_file, Jk_Logger::DEBUG);
    }


    protected function setMainLog($file, $unbuffer=false)
    {
        $file_path = APP_PATH . "logs/" . dirname($file) . "/";

        if( !file_exists( $file_path ) ) mkdir( $file_path, 0777, true);

        $logger = new Jk_Logger( $file_path . basename($file), Jk_Logger::DEBUG);
        if($unbuffer) $logger->unBuffer();
        Jk_Base::$app_log = $logger;
    }



}//END OF CLASS


?>