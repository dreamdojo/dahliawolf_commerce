<?php
/**
 * User: JDorado
 * Date: 4/26/11
 */

class Error_Handler extends Jk_Base
{
    /** @var Jk_Logger $log */
    private static $log;
    private static $isregistered = false;
    private static $log_notices = false;

    protected static function initLog()
    {
        if( self::$log  == null)
        {
            self::$log = new Jk_Logger( APP_PATH . 'logs/php_error.txt', Jk_Logger::INFO);
        }
    }


    public static function setNoticesLogging(Boolean $log=null)
    {
        self::$log_notices = (boolean) $log;
    }

    public static function registerShutdownHandler()
    {
        self::$isregistered = true;
        register_shutdown_function( array("Error_Handler", 'callShutdownHandler') );
    }

    public static function registerErrorHandler()
    {
        self::$isregistered = true;
        set_error_handler( array("Error_Handler", "callPhpError") );
    }

    protected static function trace($elevel = false, $errstr= false, $errfile = false, $errline=false)
    {
        if(self::$isregistered == false) return;
        self::initLog();

        $e          = error_get_last();
        $message    = ($elevel ? "$elevel :": "")  . ($errstr ? $errstr : $e['message']);

        $file = $errfile ? $errfile : str_replace(APP_PATH, '', $e['file']);
        $line = $errline ? $errline : $e['line'];
        
        self::$log->LogInfo( Jk_Base::getCallee(3) . "=> \t\t$message file: $file line: $line"  );

    }


    public static function callShutdownHandler()
    {
        if ( error_get_last() )
        {
            self::trace("ON_SHUTDOWN");

            $classes = get_declared_classes();
            if(count($classes) > 30) $short_classes = array_slice($classes, count($classes)-(30+1), 30);

            self::$log->LogDebug( sprintf("INCLUDE PATHS: %s", var_export( explode(":", get_include_path()), true) ) );
            self::$log->LogDebug( $short_classes ? sprintf("LAST 30 LOADED CLASSES \n %s", var_export($short_classes, true)) : sprintf("LOADED CLASSES: %s", var_export($classes, true)) );

            $included_files =  get_required_files();

            self::$log->LogDebug( sprintf("INCLUDED FILES: %s", var_export($included_files, true)) );

            self::$log->LogDebug( Jk_Base::getDebugStack() );
        }
    }

    public static  function user($m, $el = E_WARNING)
    {
        if( is_object($m) || is_array($m))
        {
            self::callPhpError($el, var_export($m, true));
            return;
        }

        self::callPhpError($el, var_export($m, true));
    }


    public static function callPhpError($errno, $errstr, $errfile = null, $errline=null)
    {
        if($errno == 0) return;

        if(!defined('E_STRICT'))            define('E_STRICT', 2048);
        if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

        $elevel = "Unknown error ($errno)";


        switch($errno)
        {
            case E_ERROR:               $elevel = "Error";                  break;
            case E_WARNING:             $elevel = "Warning";                break;
            case E_PARSE:               $elevel = "Parse Error";            break;
            case E_NOTICE:              $elevel = "Notice";
                                        if(self::$log_notices) return null; break;
            case E_CORE_ERROR:          $elevel = "Core Error";             break;
            case E_CORE_WARNING:        $elevel = "Core Warning";           break;
            case E_COMPILE_ERROR:       $elevel = "Compile Error";          break;
            case E_COMPILE_WARNING:     $elevel = "Compile Warning";        break;
            case E_USER_ERROR:          $elevel = "User Error";             break;
            case E_USER_WARNING:        $elevel = "User Warning";           break;
            case E_USER_NOTICE:         $elevel = "User Notice";            break;
            case E_STRICT:              $elevel = "Strict Notice";          break;
            case E_RECOVERABLE_ERROR:   $elevel = "Recoverable Error";      break;
            default:                    $elevel = "Unknown error ($errno)"; break;
        }

        self::trace("$elevel:{$errno}", $errstr, $errfile, $errline);

    }


}

?>