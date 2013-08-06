<?php
/**
 * User: JDorado
 * Date: 11/12/11
 */
 
class Response_Route
{

    private $controller;
    private $action;

    public function __construct($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }


    public function getController()
    {
        return $this->controller;
    }


    public function getAction()
    {
        return $this->action;
    }

}

?>