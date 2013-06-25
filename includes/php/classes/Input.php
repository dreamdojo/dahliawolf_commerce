<?
class Input {
	public $name;
	public $label;
	public $value;
	public $source = array();
	public $validations = array();
	
	public function __construct($name, $label, $value, $source = array()) {
		$this->name = $name;
		$this->label = $label;
		$this->value = $value;
		$this->source = $source;
	}
	
	public function add_validation($rule) {
		$validation = array(
			'rule' => $rule
		);
		
		$num_args = func_num_args();
		if ($num_args > 1) {
			$arg_list = func_get_args();
			array_shift($arg_list);
			$validation['arg_list'] = $arg_list;
		}
		
		array_push($this->validations, $validation);
		
		return $this;
	}
}
?>