<?php
class File_System {
    private $the_file;
    private $open_mode;
    private $file_handle;
    private $existing_data;
    private $new_data;
	private static $Exception_Helper;
	
	public function __construct() {
		self::$Exception_Helper = new Exception_Helper();
	}
	
    private function open_file() {
        $this->file_handle = fopen($this->the_file, $this->open_mode);
		
		if ($this->file_handle === false) {
			self::$Exception_Helper->request_failed_exception('File could not be opened.');
		}
		
		return $this->file_handle;
	}
	
    private function get_file_contents() {
        $this->existing_data = file_get_contents($this->the_file);
	}
	
    private function insert_data_to_file() {
        fwrite($this->file_handle, $this->new_data);
	}
	
    private function close_file() {
        fclose($this->file_handle);
	}
	
	/**
	 * Read a file
	 *
	 * @param string $file {'test.txt'} A file name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return string File data.
	 */
    public function read_file($file) {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
		$this->the_file = $file;
		$this->open_mode = 'r';
		$this->the_file = $file;
		$this->open_file();
		$this->get_file_contents();
		$this->close_file();
		
		$data = $this->existing_data;
		
		return $data;
		
	}
	
	/**
	 * Read a file line
	 *
	 * @param string $file {'test.txt'} A file name.
	 * @param int $line_number {1} Line number to read.
	 * @param string $line_ending ('') Ending delimiter.
	 *
	 * @throws Exception on failure.
	 *
	 * @return string File line.
	 */
	public function read_line($file, $line_number, $line_ending = '') {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
        $this->open_mode = 'r';
        $this->the_file = $file;
        $this->open_file();  
		$this->get_file_contents();  
        $l = 1;
        while (!feof($this->file_handle)) {
            $buffer = stream_get_line($this->file_handle, 1024, $line_ending);
			if ($l == $line_number) {
				$data = $buffer;
                return $data;
			}
            $l++;
            $buffer = '';
		}
		
        return false;
	}
	
	/**
	 * Count lines in a file
	 *
	 * @param string $file {'test.txt'} A file name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return int Number of lines.
	 */
	public function count_lines($file) {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
        $this->open_mode = 'r';
        $this->the_file = $file;
        $this->open_file();
        $count = 0;
        while (fgets($this->file_handle)) {
            $count++;
		}
        $this->close_file();
        
		$data = $count;
		
		return $data;
		
	}
	
	/**
	 * Create a file
	 *
	 * @param string $file {'test6.txt'} A file name.
	 * @param string $contents ('') {'data'} File data.
	 *
	 * @throws Exception on failure.
	 *
	 * @return int Filesize in bytes.
	 */
    public function create_file($file, $contents = '') {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
		
        $this->open_mode = 'w';
        $this->the_file = $file;
        $this->new_data = $contents;
        $this->open_file();
		if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('Unable to create file.');
		}
		/*
		$path_parts = pathinfo($file);
		print_r($path_parts);
		$path_parts = pathinfo('test.txt');
		print_r($path_parts);
		*/
        $this->insert_data_to_file();
        $this->close_file();
		
		$data = filesize($file); // may return unexpected results for files > 2GB
		
		return $data;
	}
	
	/**
	 * Append to a file
	 *
	 * @param string $file {'test6.txt'} A file name.
	 * @param string $contents ('') {'a'} File data to append.
	 *
	 * @throws Exception on failure.
	 *
	 * @return int Filesize in bytes.
	 */
    public function append_to_file($file, $contents = '') {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
        $this->open_mode = 'a'; 
        $this->the_file = $file;
        $this->new_data = $contents;
        $this->open_file();
        $this->insert_data_to_file();
        $this->close_file();
		
		$data = filesize($file); // may return unexpected results for files > 2GB
		
		return $data;
	}
	
	/**
	 * Delete a file
	 *
	 * @param string $file {'test5.txt'} A file name.
	 *
	 * @throws Exception on failure.
	 */
	public function delete_file($file) {
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
		unlink($file);
		
		return true;
	}
	
	
	/**
	 * Get directory details.
	 *
	 * Get info about a directory such as number of files, lines, and characters, total size, and list of files.
	 *
	 * @param string $directory_name {'lib'} Directory to get details for.
	 * @param bool $check_subdirectories (true) {true} Flag to search sub-directories.
	 * @param array $filters (array()) {array('/\.php/i', '/\.php4/i', '/\.php5/i')} File type regular expressions to filter results by.
	 * @param int $depth (100) Number of levels deep to go.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Directory details: Number of files, number of lines, number of characters, total size, and a list of files. File listings include file name, number of lines, number of character, and file size.
	 */
	public function get_directory_file_report($directory_name, $check_subdirectories = true, $filters = array(), $depth = 100) {
		if (empty($directory_name)) {
			self::$Exception_Helper->bad_request_exception('Directory is not set.');
		}
		
		$this->process_directory($directory_name, $check_subdirectories, $filters, $depth);
		
		$results_array = array();
		$results_array['total_number_of_files'] = $this->total_files;
		$results_array['total_number_of_lines']	= $this->total_lines;
		$results_array['total_number_of_alphabetic_characters'] = $this->total_chars;
		$results_array['total_size_in_bytes'] = $this->total_size;
	
		$result_add_file = array();
		foreach ($this->files as $file_info) {
			$result_file = array();
			//$result_file['directory_name'] = $file_info['directory_name'];
			$result_file['file_name'] = $file_info['file_name'];
			$result_file['number_of_lines'] = $file_info['num_lines'];
			$result_file['number_of_characters'] = $file_info['num_chars'];
			$result_file['file_size'] = $file_info['file_size'];
			
			array_push($result_add_file, $result_file);
		}
		
		$results_array['file_listing'] = !empty($result_add_file) ? $result_add_file : NULL;  
		
		return $results_array;
	}
	
	private function process_directory($directory_name, $check_subdirectories = true, $filters = array(), $depth = 100) {
		/*
		if ($depth > $max_depth) {
			self::$Exception_Helper->request_failed_exception('Maximum depth is reached in ' . $directory_name . '.');
		}
		*/
		if (empty($directory_name)) {
			self::$Exception_Helper->bad_request_exception('Directory is not set.');
		}
		
		$this->total_lines = NULL;
		$this->total_chars = NULL;
		$this->total_size = NULL;
		$this->total_files = NULL;
					
		$directory_name = str_replace('\\', '/', $directory_name);
		
		if (substr($directory_name, -1) != '/') {
			$directory_name .= '/';
		}
			
		if (!is_dir($directory_name)) {
			self::$Exception_Helper->request_failed_exception('Cannot find ' . $directory_name . ' directory.');
		}

		if (!is_readable($directory_name)) {
			self::$Exception_Helper->request_failed_exception('The directory ' . $directory_name . ' is not readable.');
		}
			
		if (!($dir_handle = opendir($directory_name))) {
			self::$Exception_Helper->request_failed_exception('The directory ' . $directory_name . ' cannot be opened.');
		}
			
		$this->files = array();
		$subdirs = array();
		while (false !== ($fname = readdir($dir_handle))) {
			if ($fname == '.' || $fname == '..')	continue;

			$fname = $directory_name . $fname;
			if (is_dir($fname) && !$check_subdirectories)	continue;
	
			if (is_dir($fname))	$subdirs[] = $fname;
			else {
				if ($this->check_file($fname, $filters)) {
					$finfo = $this->get_file_info($fname);
					
					$this->files[] 		= $finfo;
					$this->total_lines 	+= $finfo['num_lines'];
					$this->total_chars 	+= $finfo['num_chars'];
					$this->total_size 	+= $finfo['file_size'];
					$this->total_files++;
				}
			}
		}
		
		closedir($dir_handle);
		
		foreach ($subdirs as $subdir) {
			$this->files = array_merge($this->files, $this->process_directory($subdir, $check_subdirectories, $filters, $depth + 1));
		}
		
		return $this->files;
	}
	
	private function check_file($file_name, $filters) {
		if (empty($file_name)) {
			self::$Exception_Helper->bad_request_exception('File name is not set.');
		}
		
		if (empty($filters))	return true;
			
		$matched = false;
		foreach ($filters as $filter) {
			if (preg_match($filter, $file_name)) {
				$matched = true;
				break;
			}
		}
		
		return $matched;
	}
	
	/**
	 * Get file info.
	 *
	 * @todo May be missing some good info to return... Enchance this function.
	 * @todo Also may be use other function in this class to open and read the file.
	 *
	 * @param string $file {'test.txt'} File name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array File details: file name, number of lines, number of character, and file size.
	 */
	public function get_file_info($file) {
		$info = array();
		
		if (empty($file)) {
			self::$Exception_Helper->bad_request_exception('File is not set.');
		}
        else if (!file_exists($file)) {
			self::$Exception_Helper->request_failed_exception('File does not exist.');
		}
		
		if (!($lines = file($file))) {
			self::$Exception_Helper->request_failed_exception('Cannot read the file ' . $file . '.');
		}
		
		$contents = implode('', $lines);
		$contents = str_replace(' ', '', $contents);
		$contents = str_replace("\n", '', $contents);
		$contents = str_replace("\r", '', $contents);
		$contents = str_replace("\t", '', $contents);
		
		$info['file_name'] = $file;
		$info['file_size'] = filesize($file);
		$info['num_lines'] = sizeof($lines);
		$info['num_chars'] = strlen($contents);
		
		return $info;
	}
	
	/**
	 * Parse XML to array.
	 *
	 * @todo Only tested with Google Finance, need to make sure its generalized for any and every use.
	 *
	 * @param string $xml_data {'<xml_api_reply><finance><symbol data="GOOG" /><company data="Google Inc" /></finance></xml_api_reply>'} Well-formed XML string.
	 * @param array $include_list (array()) Array of values to grab from XML object. 
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Associative array of values filtered by $include_list if provided.
	 */
	public function parse_xml_to_json($xml_data, $include_list = array()) {
		
		if (empty($xml_data)) {
			return NULL;
		}
		
		$xml = simplexml_load_string($xml_data);
		$json = json_encode($xml);
		$temp_data = json_decode($json, true);
		
		$result_parsed_data = array();
		
		if (!empty($include_list)) {
			foreach ($include_list as $key => $value) {
				$result_parsed_data[$value] = $temp_data['finance'][$value]['@attributes']['data'];
			}
		}
		else {
			foreach ($temp_data['finance'] as $key => $info ) {
				$result_parsed_data[$key] = $info['@attributes']['data'];
			}
		}
		
		return $result_parsed_data;
	}
}
?>