<?
class Activity_Log extends _Model {
	const TABLE = 'activity_log';
	const PRIMARY_KEY_FIELD = 'activity_log_id';




    protected $user_last_log_time = null;

    public function __construct($db_host = ADMIN_API_HOST, $db_user = ADMIN_API_USER, $db_password = ADMIN_API_PASSWORD, $db_name = ADMIN_API_DATABASE)
    {
        parent::__construct($db_host, $db_user, $db_password, $db_name );
    }


	protected $fields = array(
		'user_id'
		, 'api_website_id'
		, 'activity_id'
		, 'note'
		, 'entity'
		, 'entity_id'
	);

    public static function saveActivity($params)
    {
        $input_validations = array(
            'user_id' => array(
                'label' => 'User ID'
                , 'rules' => array(
                    'is_set' => NULL
                    , 'is_int' => NULL
                )
            )
            , 'activity_id' => array(
                'label' => 'Activity ID'
                , 'rules' => array(
                    'is_set' => NULL
                    , 'is_int' => NULL
                )
            )
            , 'note' => array(
                'label' => 'Note'
                , 'rules' => array(
                    'is_set' => NULL
                )
            )
            , 'api_website_id' => array(
                'label' => 'API Website ID'
                , 'rules' => array(
                    'is_int' => NULL
                )
            )
        );

        $logger = new Jk_Logger(APP_PATH.'logs/activity_log.log');
        $logger->LogInfo("LOGGING ACTIVITY WITH params: ". var_export($params, true));

        $validator = new Validate();
        $validator->add_many($input_validations, $params, true);
        $validator->run();

        $activity_log = new Activity_Log();
        $activity = array(
            'user_id' => $params['user_id'],
            'api_website_id' => !empty($params['api_website_id']) ? $params['api_website_id'] : NULL,
            'activity_id' => $params['activity_id'],
            'note' => $params['note'],
            'entity' => !empty($params['entity']) ? $params['entity'] : NULL,
            'entity_id' => !empty($params['entity']) ? (int)$params['entity_id'] : NULL,
        );

        $logger->LogInfo("LOGGING ACTIVITY WITH activity data: ". var_export($activity, true));
        $data = $activity_log->save($activity);

        $logger->LogInfo("LOGGING ACTIVITY WITH activity response: ". var_export($data, true));
    	return $data;
    }

}
?>