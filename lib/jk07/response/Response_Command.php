<?php


class Response_Command extends Jk_Base
{
    protected $_controller;
    protected $_action;


    public function isJson()
    {
        return Jk_Request::isJson();
    }

    public function execute()
    {
        // process main controller
        $action = $this->getAction();
        $obj 	= $this->getController();
        self::addDataObject($obj);

        $main = $obj->$action();
        /*
        self::addData('command', $action);
        self::addData('command', get_class($obj) );
        self::addData('command', "main: " . ($main ? "true": "false") );
        */

        $routes = self::processRoutes(true);

        //self::addData('command', "routes: " . ($routes ? "true": "false") );

        //self::debug( "Response_Command: addAction()" . ($main ? 'true' : 'false') );

        return $main;
    }

    protected function processRoutes()
    {
        $ok = false;
        $routes = self::getRoutes();
        if( $routes == null) return $ok;

        // process any routes from controller
        foreach( $routes as $route)
        {
            $this->_action      = $route->getAction();
            $this->_controller  = $route->getController();

            $action = $this->getAction();
            $obj 	= $this->getController();
            self::addDataObject($obj);

            $ok = $obj->$action();

            // process second level routes ///
            if( self::getRoutes() != null)
            {
                foreach(self::getRoutes() as $route)
                {
                    $this->_action      = $route->getAction();
                    $this->_controller  = $route->getController();

                    $action = $this->getAction();
                    $obj 	= $this->getController();
                    self::addDataObject($obj);

                    $ok = $obj->$action();
                };
            }
        }

        return $ok;

    }


    public function getController()
    {
        return $this->_controller;
    }

    public function getRoutes()
    {
        $routes = (array) $this->_controller->getRoutes();
        if(count($routes)>0)
        {
            return $routes;
        }

        return null;
    }

    public function getAction()
    {
        return strtolower($this->_action) . "Action";
    }

    public function requireSSL()
    {
        return $this->_controller->requireSSL();
    }

    public function getAccessLevel()
    {
        return $this->_controller->getAccessLevel();
    }

    public function setController(IController $controller)
    {
        $this->_controller = $controller;
    }

    public function setAction( $action)
    {
        $this->_action = $action;
    }

}

?>