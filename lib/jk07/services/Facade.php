<?php
    
    //Jk_Loader::loadClass("interfaces#IFacade");

    abstract class Facade extends Jk_Base
    {
        protected $routes_stack = null;

        protected $access_level = 10;
        protected $require_ssl = false;
        protected $dto;

        public function getMessages()
        {
            if($this->dto)self::syncMessages($this->dto);
            return parent::getMessages();
        }

        public function getData()
        {
            if($this->dto)self::syncData($this->dto);
            return parent::getData();
        }

        public function getAccessLevel()
        {
            return $this->access_level;
        }


        public function requireSSL()
        {
            return $this->require_ssl;
        }


        public function methodsAction()
        {
            $actions = array();
            $methods = get_class_methods($this);

            foreach($methods as $method)
            {
                if( stripos($method, 'action'))
                {
                    $actions[] = strtolower(str_ireplace('action', '', $method));
                }
            }

            self::addData('methods', $actions);
            return true;
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
                $facade =   Jk_Functions::camelize( $controller ) . '_Facade';
                if(Jk_Config::APP_PREFFIX != '' ) Jk_Functions::camelize( Jk_Config::APP_PREFFIX ) ."_" . $facade;
                Jk_Loader::loadModel($controller);
            }

            $name = get_class($facade);

            if($name && ( stripos($name, 'facade') !== false ) )
            {
                $this->routes_stack->$name = new Response_Route($facade, $action);
                return true;
            }

            return false;

        }

        protected function checkLogin()
        {
            JK_User::restoreUserFromSession();
            $user = Jk_Session::getUser();

            //user must have acl 1
            if($user && $user->isAuthorized() && self::checkAccess() )
            {
                return true;
            }

            return false;
        }


        protected function checkAccess()
        {
            JK_User::restoreUserFromSession();
            $user = Jk_Session::getUser();

            $ucl  = $user->getAccessLevel()-1;

            $acl = self::getAccessLevel();

            self::debug("this acl: $acl -- user acl $ucl -- is user auth?" . ($user->isAuthorized() ? "true": "false"));

            //user must have acl 1
            if( $user && ( $acl > $ucl  ) )
            {
                return true;
            }

            return false;
        }



        protected function restoreSession()
        {

            if( Jk_Request::getVar('PHPSESSID', true) )
            {
                Jk_Session::restore(Jk_Request::getVar('PHPSESSID', true));
                return true;
            }

            if(Jk_Session::restoreFromCookie()) return true;


            return false;
        }


        protected function loginResponse()
        {
            $this->addMessage('info', "please login and try again!!");
            return false;
        }


        public function logoutAction()
        {
            Jk_Session::destroy();
            $this->addMessage('info', "you are now logged out!!");
        }


        public function loginAction()
        {
            return self::loginResponse();
        }

        private function initRoutesStack()
        {
            if( $this->routes_stack == null) $this->routes_stack  = new stdClass();
        }




    }//END OF CLASS
    
	
?>