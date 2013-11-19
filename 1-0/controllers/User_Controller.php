<?php
/**
 * User: JDorado
 * Date: 10/31/13
 */

/**
* @property Product Product
* @property User Dw_User
*
*/

 
class User_Controller extends _Controller
{

    public function get_sales($params = array())
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/product.log');
        $logger->LogInfo("request params: " . var_export($params,true));


		$this->load('Product');
		$this->load('User');

		$validate_names = array(
			'id_shop' => NULL,
			'id_lang' => NULL,
			'user_id' => NULL,
		);

		$validate_params = array_merge($validate_names, $params);

		// Validations
		$input_validations = array(
            'user_id' => array(
				'label' => 'User Id',
				'rules' => array(
					'is_int' => NULL
				)
			)
		);

		$this->Validate->add_many($input_validations, $validate_params, true);
		$this->Validate->run();

		$user_id = !empty($params['user_id']) ? $params['user_id'] : NULL;

		$data = $this->User->get_sales($user_id, $params['id_shop'], $params['id_lang']);

		return static::wrap_result(true, $data);
	}

    public function get_user($params = array())
    {
        $this->load('Dw_User');

        /*
        // User authentication: check login_instance
        $is_user_edit = array_key_exists('token', $params);
        if ($is_user_edit) {
            $this->validate_login_instance($params['user_id'], $params['token']);
        }
        */

        // Validations
        $input_validations = array(
            'user_id' => array(
                'label' => 'User Id',
                 'rules' => array(
                    'is_set' => NULL,
                    'is_int' => NULL
                )
            )
        );
        $this->Validate->add_many($input_validations, $params, true);
        $this->Validate->run();

        $where_params = array(
            'user_id' => $params['user_id']
        );


        $user = new Dw_User();

        // User
        $data = $user->getUser( $where_params );
        if (empty($data)) {
            _Model::$Exception_Helper->request_failed_exception('User could not be found.');
        }

        return static::wrap_result(true, $data);
    }


}

?> 