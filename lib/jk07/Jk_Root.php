<?php

## base class for all classes
ini_set('zend.ze1_compatibility_mode', 'off');

class FunctionCommand
{
    public $function;
    public $object;
    public $args;

    public function execute()
    {
        $prefix  = substr( $this->function, 0, 3 );
        $propery = strtolower( $this->function{3} ) . substr( $this->function, 4 );

        $obj = get_class($this->object);


        $controllers = array(
            'controller',
            'facade'
        );


        $name = strtolower(get_class($this->object));
        $name = substr($name, strrpos( $name, '_')+1);


        Jk_Base::debug( "controller_name $name");
        
        $is_controller = in_array($name, $controllers);
        $default_function = 'indexAction';

        //if( stripos( get_class($this->object), 'controller') > -1)
        if( $is_controller )
        {
            //try indexAction
            $function = strtolower($this->function);
            $function = str_replace('action', 'Action', $function);
            $function = strtolower( $function{0} ) . substr($function, 1 );
            
            Jk_Base::debug( "trying Auto call indexAction [$obj::$function()] " . ( method_exists($this->object, $function) ? 'true': 'false'));
            //if(  is_callable( array($this->object, $function )) && method_exists($this->object, $function) )
            if(  method_exists($this->object, $function) )
            {
                Jk_Base::debug( "Auto call [$obj::$function()] in indexAction format \n" . Jk_Base::getCallee(4) );
                return call_user_func_array( array($this->object, $function), $this->args);
            }

            //try index-Action
            $function = str_replace(' ', '', ucwords( str_replace('-', ' ', strtolower($this->function)) ));
            $function = str_replace('action', 'Action', $function);
            $function = strtolower( $function{0} ) . substr($function, 1 );

            Jk_Base::debug( "trying Auto call  index-Action  [$obj::$function()]" . ( method_exists($this->object, $function) ? 'true': 'false') );

            //if(  is_callable( array($this->object, $function )) && method_exists($this->object, $function) )
            if(  method_exists($this->object, $function) )
            {
                Jk_Base::debug( "Auto call [$obj::$function()] \n in index-Action format" . Jk_Base::getCallee(4) );
                return call_user_func_array( array($this->object, $function), $this->args);
            }

            //if(  is_callable( array($this->object, $default_function )) && method_exists($this->object, $default_function) )
            if(  method_exists($this->object, $default_function) )
            {
                Jk_Base::debug( "Auto call default function [$obj::$function()] \n in " . ( method_exists($this->object, $function) ? 'true': 'false') . Jk_Base::getCallee(4) );
                return call_user_func_array( array($this->object, $default_function), $this->args);
            }
        }

        if ( class_exists( 'Jk_Base' ) )
        {
            Jk_Base::debug( "Warning function not found: $this->function in callee $obj\n" . Jk_Base::getDebugStack() );
        }
        /*
        else
        {
            trigger_error( 'Base class not loaded' , E_WARNING);
            trigger_error( "Warning function not found: $this->function", E_WARNING );
        }
        */
    }
}

class Jk_Root
{

    public function __toString()
    {
        return get_class( "class object $this" );
    }


    public function __call( $function, $args )
    {
        $command = new FunctionCommand();
        $command->function= $function;
        $command->args = $args;
        $command->object = $this;
        $command->execute();
        return;
    }


    public static function __callStatic( $name, $args )
    {
        $command = new FunctionCommand();
        $command->object = self;
        $command->function= $name;
        $command->object = $args;
        return $command->execute();
    }


    public function getClassName()
    {
        return get_class($this);
    }


}// END OF CLASS


?>