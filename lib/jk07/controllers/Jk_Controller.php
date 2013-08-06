<?php


abstract class Jk_Controller extends Jk_Base
{
    protected $routes_stack = null;
    
	protected $model;
	protected $require_ssl = false;
	protected $access_level = 10;
	
	protected $view;
    protected $menu_methods = array( array('label'=> 'index', 'target' => 'index') );

    protected $raw_content = null;
	
    public function __construct()
    {
    }


    //// overwrite this on action controllers
    

    
    protected function addMenuMethod( $label = 'index', $tmethod = 'index', $options = null )
    {
        if(!$this->menu_methods) $this->menu_methods = array();
        
        //conv first to object ##i like objs so converts all to objs // all pushed after are objs by def
        if( count($this->menu_methods) < 2 ) foreach($this->menu_methods as &$m) $m = (object) $m;

        array_push( $this->menu_methods, (object) array('label' => $label,'target' => $tmethod, 'options' => $options) );
    }


    protected function injectMenus($active = 'index', $nav_id='')
    {
        unset($this->getData()->inject->main_menu);
        $menu = Site_Utils::getControllerNav( self::getName(), $active, $nav_id);

        $this->addData('inject', array('main_menu' => $menu));
    }


    public function getName()
    {
        return strtolower( substr( self::getClassName(), 0, strpos(self::getClassName(), '_')) );
    }


    public function getMenuMethods()
    {
        return $this->menu_methods;
    }
    

	public function getView()
	{
		return $this->view;
    }


    public function getRawContent()
    {
        return $this->raw_content;
    }


    protected function setRawContent($content)
    {
        $this->raw_content = $content;
    }
	
	public function getTemplate()
	{	
		return $this->view->getTemplate();
	}
	
	//// data sync ////
	public function getMessages()
	{
		if($this->model)
		{
			if( is_array($this->model) )
			{
				foreach ($this->model as $model) self::syncMessages($model);
			}else
			{
				self::syncData($this->model);
			}
		}
		return parent::getMessages();
	}


	public function getData()
	{
		if($this->model)
		{
			if( is_array($this->model) )
			{
				foreach ($this->model as $model) self::syncData($model);
			}else
			{
				self::syncData($this->model);
			}
		}

		return parent::getData();
	}


    public function addOutputVar($name, $val)
    {
        if($name == null) return;
        $this->addData('variables', array( "$name" => $val ) );
    }


    public function injectOutputVar($name, $val)
    {
        if($name == null) return;
        $this->addData('inject', array( "$name" => $val ) );
    }

	public function requireSSL()
	{
		return $this->require_ssl;
	}


	public function getAccessLevel()
	{
        Jk_Base::debug($this->access_level);
		return $this->access_level;
    }



    //// routes /////
    public function getRoutes()
    {
        $this->initRoutesStack();

        return $this->routes_stack;
    }

    public function addRoute($controller, $action)
    {
        $this->initRoutesStack();

        
        if(is_object($controller) == false)
        {
            $cclass = Jk_Functions::camelize( $controller ) . '_Controller';
            Jk_Loader::loadController("$controller");

            $controller = new $cclass();
        }

        $name = get_class($controller);

        if($name && ( stripos($name, 'controller') !== false ) )
        {
            $this->routes_stack->$name = new Response_Route($controller, $action);
            return true;
        }

        return false;

    }

    protected function checkLogin( $send_login_response = true )
    {
        //JK_User::restoreUserFromSession();
        $user = Jk_Session::getUser();

        $ucl  = $user->getAccessLevel()-1;

        $acl = self::getAccessLevel();

        self::debug("this acl: $acl -- user acl $ucl -- is user auth?" . ($user->isAuthorized() ? "true": "false"));

        //user must have acl 1
        if($user && $user->isAuthorized() && ( $acl > $ucl  ) )
        {
            return true;
        }

        if($send_login_response) self::sendLoginResponse();

        return false;
    }


    protected function sendLoginResponse()
    {
        self::addRoute("default", "login");
    }


    public function logoutAction()
    {
        Jk_Session::destroy();
        return true;
    }



    private function initRoutesStack()
    {
        if( $this->routes_stack == null) $this->routes_stack  = new stdClass();
    }

	
}
?>