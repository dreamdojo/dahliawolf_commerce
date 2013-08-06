<?php

Jk_Loader::loadInterface("IExecutable");

class Jk_Access_Controller extends Jk_Root implements IExecutable
{
	
	protected $command;
	protected $login_command;
	protected $logout_command;
	
	public function  __construct()
	{

	}
	
	public function getLoginCommand()
	{
        $request_controller = new Request_Controller();
        $this->login_command = $request_controller->getDefault();
        $this->login_command->setAction('login');


        return $this->login_command;
	}

	public function getLogoutCommand()
	{
        $request_controller = new Request_Controller();
        $this->login_command = $request_controller->getDefault();
        $this->login_command->setAction('logout');

        return $this->logout_command;
	}
	
	public function execute(&$request_command)
	{
        if(!$request_command) return false;
        
        $user               = Jk_Session::getUser();
        $user_access_level  = $user->getAccessLevel();
        $user_is_authorized = $user->isAuthorized();

        //Jk_Base::debug("user access level: $user_access_level ");
        Jk_Base::debug("controller alevel: ". $request_command->getAccessLevel(). " -- user: $user_access_level"  );


		// check if page requires authentication //// if user acl is 11 and command needs 10 then login with higher acl is required
		if ( $user_access_level-1 > $request_command->getAccessLevel()  )
		{
			//// not authorized - return login command ////
            $request_command = $this->getLoginCommand();
            return true;
		}
		
		return false;
	}
}
?>