<?php


Jk_Loader::loadModel('base');

abstract class Jk_User extends Simple_DAO
{   

    // access props
    protected $access_level     = 10;
    protected $authorized       = false;

    protected $group_hash;
    protected $group_type;
    protected $group_name       = 'users';
    protected $group_id;

    protected $user_type        = 'generic';
    protected $home_page        = false;
    protected $user_hash        = null;

    protected $secret;
    protected $pass;
    protected $random;
    protected $active;


    //user table
    public $first_name 	    = null;
    public $last_name	    = null;
    public $email		    = null;
    public $login           = null;




    public function __construct($record_hash = false)
    {
        parent::__construct($record_hash);
    }


    public function authorize()
    {
        return $this->doAauth();
    }


    public function sendPass()
    {
        return $this->retrievePass();
    }


    public function getAccessLevel()
    {
        return (int) $this->access_level;
    }


    public function getHomePage()
    {
        return $this->getUserHomePage($this->user_hash);
    }


    public function isAdmin()
    {
        return $this->access_level  < 2;
    }


    public function getGroupName()
    {
        return $this->group_name;
    }

    public function getGroup()
    {
        return $this->group_hash;
    }

    public function isAuthorized()
    {
        return $this->authorized;
    }


    public function getHash()
    {
        return $this->user_hash;
    }


    public function getSessionVars()
    {
        $return = new stdClass();

        //this is already sent to the session by authorize of reset
        $return->user_hash          = $this->user_hash;

        $return->user_first_name    = $this->first_name;
        $return->user_last_name     = $this->last_name;
        $return->user_email         = $this->email;
        $return->user_type          = $this->user_type;
        $return->user_group_hash    = $this->group_hash;

        return $return;
    }


    private function doAauth()
    {
        $request  = Jk_Request::getInstance();
        $login    = Jk_Request::getVar('email' , true);
        $secret   = Jk_Request::getVar('pass' , true);

        if($login != '' && $secret != '')
        {
            $q = "SELECT `random` FROM `user`  WHERE `login` = '$login' AND `user_status` = 'enabled';";
            $user = $this->db->fetchSingle($q);
        }

        if ( $user )
        {
            $rand = $user->random;
            $hash = sha1($rand . $secret);


            $q =   "SELECT u.user_hash, u.first_name, u.last_name, u.email, u.type
                    FROM `user` u
                    WHERE u.login = '$login'
                    AND u.secret = '$hash'
                    ; ";


            $user =  false;
            $user = $this->db->fetchSingle($q);


            if ( $user )
            {
                unset($this->active_login);
                unset($this->active_secret);

                $this->authorized = true;

                $this->user_hash	= $user->user_hash;
                $this->first_name 	= $user->first_name;
                $this->last_name	= $user->last_name;
                $this->email		= $user->email;
                $this->user_type    = $user->type;


                Jk_Session::set("user_hash", $this->user_hash);

                $this->setProps();
                //$this->setAccessLevel();
                $this->toSession();

                $this->addMessage('info', "user successfully logged in: $this->user_hash");
                $this->addData('token', Jk_Session::getId());

                return true;
            }
            else
            {
                $this->addMessage('login_error', 'your email/password combination is incorrect, please try again');
                return false;
            }
        }
        else
        {
            $this->addMessage('login_error', 'your email/password combination is incorrect, please try again');
            return false;
        }

    }

    protected function setProps()
    {
        $props = $this->get( $this->user_hash );

        if ( $props )
        {
            $this->group_name = $this->group_type;
            $this->user_props_loaded = true;

            $this->setAccessLevel();

            return true;
        }
        else
        {
            return false;
        }
    }


    protected function resetUser($hash)
    {
        $user = $this->db->fetchSingle( "SELECT u.user_hash, u.first_name, u.last_name, u.email, u.type
                            FROM `user` u
                            WHERE u.user_hash = '$hash'
                            ; ");
        if ($user)
        {
            $this->authorized   = true;

            $this->user_hash	= $user->user_hash;
            $this->first_name 	= $user->first_name;
            $this->last_name	= $user->last_name;
            $this->email		= $user->email;
            $this->user_type    = $user->type;

            Jk_Session::set("user_hash", $this->user_hash);

            $this->setProps();
            //$this->setAccessLevel();

            self::debug('resetUser RESETING USER');

            return true;
        }
        else
        {
            $this->authorized = false;
            return false;
        }
    }



    protected function retrievePass()
    {
        $request = Jk_Request::getInstance();
        $email = Jk_Request::getVar('email', true);

        $user = $this->db->fetchSingle("SELECT u.user_hash, u.random, u.first_name, u.login, u.pass
                                FROM `user` u
                                WHERE u.login = '$email'
                                ;");
        if ( $user )
        {
            // send password /

            $sent = $this->sendPasswordRetrieveEmail($user, true);

            if ( $sent )
            {
                $this->addMessage('pass_sent', 'please check email.  your password has been sent');

            }else
            {
                $this->addMessage('error_sending', 'server busy! please try again or contact customer support');
            }

            return true;
        }
        else
        {
            //no pass send empty error//
            $this->addMessage('invalid_email', 'the email address provided does not exist. please try again');
            return false;
        }

    }


    protected function sendPasswordRetrieveEmail($user, $copy_admin = false)
    {
        $sent = false;

        $auser = Jk_User::getAppUser();
        $auser->setHash($user->user_hash);
        $auser->get();

        $interface = $auser->getInterface();

        $from_email = "noreply@$interface->domain";
        $from_name  = "$interface->name";


        if( $user )
        {
            $email 	= $user->login;
            $cs = new Crypt($user->random);
            $user->password = $cs->decrypt($user->pass);

            $tpl = Site_Templates::getPasswordRetrieveTemplate($user, $interface);

            $sent = Jk_Mail::sendMail($email, $tpl->body, $tpl->subject, $from_email, $from_name);

            if($copy_admin) Jk_Mail::sendAdminEmail($tpl->body, $tpl->subject, $from_email, $from_name);
        }

        return $sent;
    }




    public function setAccessLevel()
    {
        $props = $this->db->fetchSingle("SELECT ug.*
                                        FROM `user` u, `user_groups` ug
                                        WHERE u.user_hash = '$this->user_hash'
                                        AND ug.group_hash = u.group_hash
                                    ;");

        if ( $props )
        {
            $this->access_level = $props->access_level;
            $this->group_name   = $props->group_type;

            self::debug("resetUser access_level: $this->access_level");

            Jk_Session::set('access_level', $this->access_level);
        }
    }


    private function getUserHomePage($user_hash)
    {
        if($this->home_link) return $this->home_link;


        $db_page = $this->db->fetchSingle("SELECT p.*
                                        FROM `user_props` up, jk_link p
                                        WHERE up.user_hash = '$user_hash'
                                        AND up.home_page = p.page_hash
                                        ;");

        if($db_page)
        {
            $page = new Response_Link();
            $page->parseLink($db_page);

            $this->home_link = $page;
        }

        return $this->home_link;
    }


    public function disable($user_hash)
    {
        $session = Jk_Session::getInstance();
        $session->start();

        $user = $session->getUser();

        if($user->isAdmin())
        {
            $user_hash = $user_hash ? $user_hash : Jk_Session::get('user_hash');
            $this->doDisable($user_hash);
            $this->addMessage('admin_user_disabled', 'user has been disabled');
        } else
        {
            $user_hash = Jk_Session::get('user_hash');
            $this->doDisable($user_hash);
            Jk_Session::destroy();
            $this->addMessage('user_disabled', 'your account has been disabled you will be logged out');
        }

        $response = new Jk_ajax_response();
        $response->success  = self::doDisable($user_hash);
        $response->messages    = $this->getMessages();

        return $response;
    }


    public function doDisable($user_hash)
    {
        $vals = array
        (
            'user_status' => 'disabled'
        );

        $this->updateUser('user', $user_hash, $vals);

        return true;
    }


    public function doDelete($user_hash=false)
    {

        //$user_hash
        $session = Jk_Session::getInstance();

        $user = $session->getUser();

        if( $user->isAdmin() )
        {
            $user_hash = $user_hash;
            //$this->doDelete($user_hash);
            $this->addMessage('admin_user_deleted', 'user has been deleted');

        } else
        {
            $user_hash = Jk_Session::get('user_hash');
            //$this->doDelete($user_hash);
            Jk_Session::destroy();
            $this->addMessage('user_deleted', 'your account has been deleted you will be logged out');
        }

        $tables = array('user', 'user_address', 'user_props');

        foreach ($tables as $table)
        {
            $this->db->query("DELETE FROM `$table` WHERE `user_hash` = '$user_hash'; ");
        }

        return true;
    }

    public static function getUserRoles()
    {
        $user               = Jk_Session::getUser();
        $user_access_level  = $user->access_level;


        $access = $user->isAdmin() ? $user_access_level : $user_access_level+1;

        $return = null;

        $roles = Jk_Db::getInstance()->fetch("SELECT *
                            FROM user_groups ug
                            WHERE ug.access_level >= '$access'
                            ORDER BY ug.group_id ASC
                            ;");

        if($roles)
        {
            $return = $roles;
        }

        return $return;
    }

    public function getSimpleUserInfo($cuser=null)
    {
        $ruser = null;


        if( $this->is_linked == false || !self::getHash() )
        {
            $hash = self::getHash();
            $fuser = Jk_Db::getInstance()->fetchSingle( "SELECT u.user_hash, u.first_name, u.last_name, u.email
                                    FROM `user` u
                                    WHERE u.user_hash = '$hash'
                                    ; ");

            $ruser = $fuser;
        }elseif( self::getHash() )
        {
            $simple = new stdClass();
            $simple->user_hash  = $this->user_hash;
            $simple->first_name = $this->first_name;
            $simple->last_name  = $this->last_name;
            $simple->email      = $this->email;

            $ruser = $simple;
        }

        return $ruser;

    }

    public static function getGroupUsersByType($type = 'user')
    {
        $users      = false;
        $user       = Jk_Session::getUser();
        $user_access_level  = $user->access_level;
        $access     = $user->isAdmin() ? $user_access_level : $user_access_level+1;


        //)
		$group = Jk_Db::getInstance()->fetch("SELECT u.*, ug.*, up.*
                                    FROM `user_groups` ug, `user_props` up, `user` u
                                    WHERE ug.group_type = '$type'
                                    AND ug.access_level >= '$access'
                                    AND u.group_hash = ug.group_hash
                                    AND u.user_hash != '$user->user_hash'
                                    AND up.parent_hash = u.user_hash
                                    ORDER BY u.first_name;
                                    ;");


		if( $group )
		{
            foreach($group as $ruser){self::cleanUserVars($ruser);}
			$users = $group;
		}

		return $users;
    }



    public static function getUsers($type = false)
    {
        $user               = Jk_Session::getUser();
        $user_access_level  = $user->access_level;


        if( $user->isAdmin() )
        {
            if ($type)
            {
                $group = self::getGroupUsersByType($type);
            }
            else
            {
                $group = Jk_Db::getInstance()->fetch("SELECT u.*, ug.*, up.*
                                                  FROM `user_groups` ug, `user_props` up, `user` u
                                                  AND ug.access_level >= '$user_access_level'
                                                  AND u.group_hash = ug.group_hash
                                                  AND u.user_hash != '$user->user_hash'
                                                  and up.parent_hash = u.user_hash
                                                  order by u.first_name;
                                                  ;");
            }
        }
        else
        {
            $group = Jk_Db::getInstance()->fetch("SELECT u.*, ug.*, up.*
                                              FROM `user_groups` ug, `user_props` up, `user` u
                                              WHERE u.parent_hash = '$user->user_hash'
                                              AND ug.access_level > '$user_access_level'
                                              AND u.group_hash = ug.group_hash
                                              AND up.parent_hash = u.user_hash
                                              ORDER BY u.first_name;
                                              ;");

        }

        if( $group )
        {
            foreach($group as $ruser){self::cleanUserVars($ruser);}
            $users = $group;
        }

        return $users;
    }

    public static function getUsersByParent($hash = false)
    {
        $user               = Jk_Session::getUser();
        $user_access_level  = $user->access_level;


        if( $user->isAdmin() )
        {
            $user_hash = $hash;
            $group = Jk_Db::getInstance()->fetch("SELECT u.*, ug.*, up.*
                                                  FROM `user_groups` ug, `user_props` up, `user` u
                                                  WHERE u.parent_hash = '$user_hash'
                                                  and up.parent_hash = u.user_hash
                                                  AND u.group_hash = ug.group_hash
                                                  AND ug.access_level >= '$user_access_level'
                                                  order by u.first_name;
                                              ;");

        }
        else
        {
            $group = Jk_Db::getInstance()->fetch("SELECT u.*, ug.*, up.*
                                              FROM `user_groups` ug, `user_props` up, `user` u
                                              WHERE u.parent_hash = '$user->user_hash'
                                              AND ug.access_level > '$user_access_level'
                                              AND u.group_hash = ug.group_hash
                                              AND up.parent_hash = u.user_hash
                                              ORDER BY u.first_name;
                                              ;");

        }

        if( $group )
        {
            foreach($group as $ruser){self::cleanUserVars($ruser);}
            $users = $group;
        }

        return $users;
    }


    public static function getUserByLogin($login)
    {
        $return = null;

        $app_user = self::getAppUser();
        $ok = $app_user->fetchBy(array( 'login' => "$login"));

        if($ok)$return = $app_user;

        return $return;
    }


    public static function getGroupByHash($hash = '')
	{
		$user_group = false;

		$group = Jk_Db::getInstance()->fetchSingle("SELECT * FROM `user_groups` WHERE `group_hash` = '$hash' ;");

		if( $group )$user_group = $group;

		return $user_group;
	}



    public static  function getGroupByType($type = 'user')
    {
        $user_group = false;

        $group = Jk_Db::getInstance()->fetchSingle("SELECT * FROM `user_groups` WHERE `group_type` = '$type' ;");

        if( $group )
        {
            $user_group = $group;
        }
        else {
            // return 'user' type hash
            $group = Jk_Db::getInstance()->fetch("SELECT * FROM `user_groups` WHERE `group_type` = 'user' ;");
            $user_group = $group;
        }

        return $user_group;
    }


    protected static function cleanUserVars($user)
    {
        $clean = array('pass', 'random', 'secret', 'user_id');

        $uvars = get_object_vars($user);

        foreach($clean as $cvar)
        {
            if( array_key_exists($cvar, $uvars)) unset($user->{$cvar});
        }
    }


    #### session functions ####
    private function toSession()
    {
        if($this->isAuthorized() === true)
        {
            $user_vars = $this->getSessionVars();

            foreach ($user_vars as $key => $val) Jk_Session::set($key, $val);
        }
    }



    public static function restoreUserFromSession($authenticate = true)
    {
        $user = self::getAppUser();

        if($authenticate)
        {
            $user_hash = Jk_Session::get('user_hash');

            if($user_hash)$user->resetUser($user_hash);

        }else{
            self::mergeData($_SESSION, $user, false, true);
            $user = Jk_Session::setUser($user);
        }

        Jk_Base::debug('is user authorized: '. $user->authorized);

        if( $user->isAuthorized() )
        {
            Jk_Session::setUser($user);
        }

        return $user;
    }



    public static function getAppUser()
    {
        try
        {
            $user_class   = Jk_Config::USER_MODEL;
            return new $user_class();

        }catch (Exception $err)
        {
            // default to this
            $user_class   = 'Jk_User';
            return new $user_class();
        }
    }


} // END CLASS

?>