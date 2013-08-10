<?php


class Jk_Db extends Jk_Base
{
	private static $instance = null;
    const DEBUGGING	= true;
    const VERBOSE	= false;
    

	protected static $db_config;
	
	private $is_started = false;
	private $link;
	
	//// CACHE VARS ////
	private $main_cache = array();
	
	public $affected_rows;
		
	public $result;
	
	public $log;
	
	private static $total_queries = 0;
	private static $total_execution = 0;
    private static $last_error = '';
	

	function __construct()
    {
        self::$db_config = Jk_Config::getInstance();
        
    	$this->log = new Jk_Logger(APP_PATH . 'logs/db.txt', Jk_Logger::DEBUG);
		$this->main_cache['cached_table_fields'] = array();
                
        self::open();
    }
    
	public function __destruct()
	{
		$this->freeResult();
		$this->close();
	}
	
	public static function getTotalQueries()
	{
		return self::$total_queries;
	}
	
	public static function getTotalExecution()
	{
		return self::$total_execution;
	}

    public static function getInstance()
    {
        if (null === self::$instance)
		{
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    ////// PRIVATE METHODS ////
    
   	private function open($force= false)
	{
        
		if ($this->is_started == false || $force)
        {
            $this->link = mysql_connect( self::$db_config->host, self::$db_config->user, self::$db_config->pass );
            
            
			if ( $this->link )
	        {
	            //Connect To dB
	            if ( !mysql_select_db(self::$db_config->db ))
				{
					self::trace('COULD NOT CONNECT TO DATABASE');
					//$this->db_config = new stdClass();
                    return false;
				}
	        }
	        
	        else
	        {
                self::trace("COULD NOT CONNECT TO DATABASE/SERVER");
	            //$this->db_config = new stdClass();
                return false;
	        }
	        
	        $this->is_started = true;
            //echo "db connection started: true <br>";
            //Jk_Base::debug( "connected to: {$this->db_config->env} - {$this->db_config->host} ");
	        
        }
        
        //$this->db_config = new stdClass();
        return true;
	}
	
	static function mysqlSafe($value)
    {
        if( is_array($value) || is_object($value) ) return  '';
        return mysql_real_escape_string( addslashes($value) );
	}
	
	
	private function doQuery($q)
	{
        //self::trace( self::getDebugStack()) ;
		self::trace( self::getCallee(4) . " -> SENDING QUERY: \n$q");
		self::open();
		$this->affected_rows = 0;

		$timer = 10;

		$result = false;
		
		$start_time 	= self::getTime();

		while($timer-- > 0)
		{
			if ($result = @mysql_query($q, $this->link) )
			{
				//self::trace("MYSQL LINK $this->link", true);

                self::$last_error = mysql_error();
				$this->affected_rows = mysql_affected_rows($this->link);
				self::trace("AFFECTED ROWS: $this->affected_rows");
				$this->result = $result;
				
				break;
				 
			}else
			{
				if(!mysql_errno())
				{
					self::open(true);
				}else
				{
					//$this->debug("QUERY ERROR ". mysql_error());
					self::trace("QUERY ERROR ". mysql_error(), true);
                    self::$last_error = mysql_error();
					break;
				}
			}
		}

		//if($this->affected_rows == 0) self::trace("QUERY ERROR ". mysql_error());
		
		self::$total_execution += self::getTotaltime($start_time, true);
		self::trace("QUERY TIME: " . self::getTotaltime($start_time) );
		self::$total_queries++;

		return $result;
	}
    
    // -------------------------------------
    public function query($_q)
    {
		//s$_result = '';
		$result = self::doQuery($_q);
		
		//self::freeResult();
		
		return $result;
    }

    public function getError()
    {
        return self::$last_error;
    }


    public function getCrossSelectFields($main='dealer', $child = 'state', $alias=null)
    {
        $main_fields = $this->getFields($main);
        $child_fields = $this->getFields($child);

        if($alias==null) $alias = $child;

        $fields_str = "";
        foreach($child_fields as $field => $childfield)
        {
            if($field == 'id') continue;
            if( array_key_exists($field, $main_fields) )
            {
                $fields_str .= "\n$alias.{$childfield} AS {$child}_{$childfield},";
            }else
            {
                $fields_str .= "\n{$alias}.{$childfield},";
            }
        }


        //$fields_str = trim($fields_str, "\n");
        $fields_str = trim($fields_str, ",");

        return $fields_str;

    }
    
   	public function getFields($table = 'state')
	{
		$cache = $this->main_cache['cached_table_fields'];
		$parsed = array();

		
		if( @$cache["$table"] )
		{
			$parsed = $cache["$table"];
		}
		else
		{
			$fields = $this->fetch("SHOW COLUMNS FROM `$table`");

            if($fields && is_array($fields))
            {
                foreach ($fields as $field)
                {
                    //$parsed[] = $field['Field'];
                $parsed[ $field->Field ] = $field->Field;
                }

                $this->main_cache['cached_table_fields']["$table"] = $parsed;
            }

		}
		
		return $parsed;

	}
	
	public function fieldQuery($_t = 'state')
  	{
  		$fields = $this->getFields("$_t");
  		$str = '';
  		
		foreach ($fields as $f)
		{
			$str .= "`".strtolower($f)."`,";
		}
	  	
	  	$str = substr($str, 0, -1);
	  	
	  	$str = "($str)";
	  	
	  	return $str;
  	}
  	
  	
  	//// INSERT DATA INTO TABLE
    public function insert($table, $add_data)
    {
        $vals = $this->makeInsertVals($table, $add_data);
        
        $this->insertVals($table, $vals);
        
        
        return ($this->affected_rows > 0);
    }
    
    
   	//// INSERT DATA INTO TABLE - RAW VARS
  	private function insertVals($table, $vals)
  	{
  		$fields = $this->fieldQuery("$table");
			
		$this->query("INSERT INTO `$table` $fields VALUES ($vals); ");
  	}

    
  	
  	public function makeInsertVals($table, $add_data)
  	{
		$vals     = '';
		$fields   = $this->getFields($table);
		
		foreach($fields as $f)
		{
			if(@array_key_exists($f, $add_data))
			{
                self::trace("insert field: $f={$add_data[$f]}");


                if( strpos($f, 'state') > -1 && intval( $add_data[$f] ) == 0 )
                {
                    $val = "(SELECT `state_id` FROM `states` WHERE `short_name` = '{$add_data[$f]}' )";
                    $vals .= "$val,";
                }
                elseif(strpos($f, 'date') > -1)
                {
                    $val = Jk_Functions::mysqlDate($add_data[$f]);
                    $vals .= "'$val',";

                }elseif(strpos($f, 'price') > -1)
                {
                    $val = str_replace(array(','), '', $add_data[$f]);
                    $vals .= "'$val',";
                }else
                {
                    $vals .= "'" .self::mysqlSafe( $add_data[$f] ). "',";
                }


            }else
			{
                $add_data[$f] == null ?  $vals .= "NULL," : $vals .= "'',";
			}
		}
		
		$vals = substr($vals, 0, -1);
		
		return $vals;
  	}
  	
  	
  	//// UPDATE TABLE
	   
	public function update($update_arr)
	{
		/*expecting
		$update_arr[table_name]
		$update_arr[update_mode]
		$update_arr[update_data]
		$update_arr['where_string'] : `listing_hash` = '$this->listing_hash';
		*/

        $update_arr = (object) $update_arr;


        if(!$update_arr->table)
        {
            self::trace("ERROR:: NO TABLE TO UPDATE");
            return false;
        }


        if(!$update_arr->data)
        {
            self::trace("ERROR:: NO DATA TO UPDATE");
            return false;
        }

        if(!$update_arr->where)
        {
            self::trace("WARNING: NO UPDATE CONDITION, WHERE IS MISSING");
            return false;
        }

        $mode   = $update_arr->mode ? $update_arr->mode : 'update';
        $table  = $update_arr->table;
        $data   = $update_arr->data;
        $where  = $update_arr->where;
		
		$query_str = $this->makeUpdateQuery($table, $mode, $data);
		
		$query = "UPDATE `{$table}` SET {$query_str} WHERE {$where};";

		$this->query($query);
		
	
        if($this->affected_rows > 0)
        {
            return true;
        }
        elseif( $this->affected_rows == 0 && !mysql_errno() )
        {
            return true;
        }
        else
        {
            return false;
        }
		
	}
	
	private function makeUpdateQuery($table = 'listing', $mode = 'invalid', $mod_data)
	{
		$str = '';
		$valid_data = $this->getFields($table);
		
		foreach($valid_data as $f => $v)
		{
			switch ($mode)
			{
				case 'update_change':
                case 'update':
                case 'change':
	
						if(array_key_exists($f, $mod_data))
						{
                            self::trace("update field: $f={$mod_data[$f]}");

							if( intval( $mod_data[$f] ) == 0 && strpos($f, 'state') > -1 )
							{
								$v = "(SELECT `state_id` FROM `states` WHERE `short_name` = '{$mod_data[$f]}' )";
								$str .= "`$f` = $v,";
							}
							elseif(strpos($f, 'date') > -1)
							{
								$v = Jk_Functions::mysqlDate($mod_data[$f]);
								$str .= "`$f` = '$v',";
								
							}elseif(strpos($f, 'price') > -1)
							{
								$v = str_replace(array(','), '', $mod_data[$f]);
								$str .= "`$f` = '$v',";
							}
							
							else
							{
								$v = $mod_data[$f]==null ? null : self::mysqlSafe($mod_data[$f]) ;

                                $v === null ?  $str .=  "`$f` = NULL," : $str .= "`$f` = '$v',";
							}
						}
				break;
				
				case 'update_keep' :
				
						if(!array_key_exists($f, $mod_data))
						{
							$str .= "`$f` = '',";
						}
				break;
			}
		}
		
		$str = substr($str, 0, -1);
		
		return $str;
	}
    
    public function fetchSingle($q)
    {
        $result = false;
        
        if( strrpos( $q, ';' ) > 5 )
        {
            $q = substr( $q, 0, strrpos( $q, ';' ) ) . '  LIMIT 1;';
        }else
        {
            $q = "$q LIMIT 1;";
        }
        
        $d = self::doQuery($q);
        
        if(!mysql_errno() && $this->affected_rows > 0)
        {
            $result = (object) mysql_fetch_array( $d, MYSQL_ASSOC );

            if($result)
            {
                foreach($result as $field => $val)
                    $result->{$field} = stripslashes($val);
            }

            self::freeResult();
        }
		
		return $result;
        
    }
    
    
    public function fetch($_q)
	{
    	$return = false;
    	
		$d = self::doQuery($_q);
		
        if( !mysql_errno() )
        {
    		if( $this->affected_rows > 0 )
            {
                $return = array();
                
                while ($r = mysql_fetch_array($d, MYSQL_ASSOC))
        		{
                    $robj =  (object) $r;
                    if($robj)
                    {
                        foreach($robj as $field => $val)
                            $robj->{$field} = stripslashes($val);
                    }
         			$return[] = $robj;
        		}
            }
            else
            {
                self::trace('FETCH RETURNED NO RESULTS');
            }
    		
    		self::freeResult();
		}
        
		return $return;
    }
    	
	public function freeResult()
	{
	   //if($this->result) mysql_free_result($this->result);
    }
    
    
    public function close()
	{
	   if($this->link) @mysql_close($this->link);
    }
    
    public function trace($_m, $verbose = false)
	{
		if (self::DEBUGGING)
		{
			$this->log->LogInfo(get_class($this) . " => $_m");
		}
        
        if(self::VERBOSE && $verbose)
        {
            echo get_class($this) ." => $_m <br>";
        }
	}
	
	
}// end class
?>