<?php
    
    //Jk_Loader::loadClass("interfaces#IDTO");

    abstract class Jk_Model extends Jk_Base
    {
        protected $AUTO_CREATE_TABLE = false;
        protected $db;
        protected $request;

        protected $linked_record;
        protected $record_key;
        protected $record_table;
        protected $working_tables = array();
        
       	protected $log_file;
       	protected $log;

        protected $slug;

        protected $creation_date;


        protected $is_linked = false;

        protected $indexes      = null;
        protected $order_by     = array();
        protected $fetch_selects = array();
        protected $fetch_selectlike = array();
        protected $ignored_dbkeys = array();
        protected $group_by     = array();
        protected $duplicate_vars = Array();

        protected $multiple_join_map = array();

        protected $link_by_key = null;
        protected $link_on_key = null;

        protected $select_all = false;

        private $def_ignored_dbkeys = array
                        (
                            'id', 'creation_date', 'indexes', 'sync_objects',
                            'ignored_dbkeys', 'def_ignored_dbkeys',
                            'multiple_join_map', 'link_by_key', 'link_on_key',
                            'db', 'request', 'is_linked',
                            'working_tables', 'order_by', 'fetch_selects', 'group_by',
                            'message_stack', 'data_stack',
                            'record_table', 'record_key', 'linked_record',
                            'log_file', 'log', 'duplicate_vars',
                            'add_slug', 'remove_slug', 'slug', 'AUTO_CREATE_TABLE', 'select_all'
                        );



        protected function __construct($record_hash = false)
        {
            $this->request  = Jk_Request::getInstance();
            $this->db       = Jk_Db::getInstance();

            //always ignore key
            $this->def_ignored_dbkeys[] = $this->record_key;

            self::setLinkBy('parent_hash');

            if( $record_hash != false )
            {
                $this->fetch($record_hash);
            }

            if(!$this->working_tables) $this->working_tables = array($this->record_table);
            elseif(!in_array($this->record_table, $this->working_tables)) $this->working_tables = array($this->record_table);

            //self::addDuplicateVars('data_stack');
        }


        public function orderBy($orderby)
        {
            if(is_array($orderby))
            {
                $this->order_by = array_merge($this->order_by, $orderby);
            }else
            {
                $this->order_by[] = $orderby;
            }
        }

        public function selectBy(array $selectby)
        {
            if(is_array($selectby))
            {
                $this->fetch_selects = array_merge($this->fetch_selects, $selectby);
            }else
            {
                self::mergeData($selectby, $this->fetch_selects, true);
                //$this->fetch_selects[] = $select;
            }
        }
        public function selectLike(array $selectlike)
        {
            if(is_array($selectlike))
            {
                $this->fetch_selectlike = array_merge($this->fetch_selectlike, $selectlike);
            }else
            {
                self::mergeData($selectlike, $this->fetch_selectlike, true);
            }
        }

        public function selectAll($all=false)
        {
            $this->select_all = (bool)$all;
        }


        public function groupBy($groupby)
        {
            if(is_array($groupby))
            {
                $this->group_by = array_merge($this->group_by, $groupby);
            }else
            {
                $this->group_by[] = $groupby;
            }
        }


        public function setLinkBy($link_by=null)
        {
            if( array_key_exists($link_by, get_object_vars($this) ) )
            {
                $this->link_by_key = $link_by;
            }
        }

        public function setLinkOn($link_on_key=null)
        {
            if( array_key_exists($link_on_key, get_object_vars($this) ) )
            {
                $this->link_on_key = $link_on_key;
            }
        }

        public function getLinkOn($link_on_key=null)
        {
            return $this->link_on_key;
        }


        public function addDuplicateVars($var)
        {
            $this->duplicate_vars[] = $var;
        }

        public function getDuplicateVars()
        {
            return $this->duplicate_vars;
        }

        public function addMultipleJoin(Jk_Model $link_object, $link_by=null)
        {
            if(!@$link_object->getTable()) return;

            if($link_by != null )$link_object->setLinkBy($link_by);

            $this->multiple_join_map[ $link_object->getTable() ] = $link_object;

            $this->working_tables[] = $link_object->getTable();
        }


        public function action( $obj = null, $action = null, $sync = null)
        {
            if(  is_callable( array($obj, $action)) && method_exists($obj, $action) )
            {
                $obj->$action();

                if($sync)
                {
                    self::syncMessages($obj);
                    self::syncData($obj);
                }

                return true;
            }else
            {
                self::debug("ERROR: no action: '$action' in obj: $obj callee: ". self::getCallee());
                return false;
            }
        }




        ///// MAIN FETCH FUNCTIONS START ////

        public function fetchAll($fetchby = null)
        {
            $table_alias = 'rt';

            if($fetchby)
            {
                //self::debug(var_export($this->fetch_selects, true));

                //regular selects
                if( count($this->fetch_selects) > 0){
                    if(is_array($fetchby))
                    {
                        $this->fetch_selects = array_merge($this->fetch_selects, $fetchby);
                    }else
                    {
                        $this->fetch_selects[$fetchby] = $this->{"$fetchby"} != null ? $this->{$fetchby} : null;
                        $fetchby = $this->fetch_selects;
                    }
                }

                //select like
                if( count($this->fetch_selectlike) > 0){
                    if(is_array($fetchby))
                    {
                        $fetchby = array_merge($this->fetch_selectlike, $fetchby);
                    }else
                    {
                        $this->fetch_selects[$fetchby] = $this->{"$fetchby"} != null ? $this->{$fetchby} : null;

                        foreach($this->fetch_selectlike as $selectlike => $slval)
                        {
                            $this->fetch_selects[$selectlike] = $slval;
                        }

                        $fetchby = $this->fetch_selects;
                    }
                }

                self::debug("FETCHBY AFTER MERGE:" . var_export($fetchby, true));
                self::debug("LIKE SELECTS? :" . var_export($this->fetch_selectlike, true));


                if(is_array($fetchby))
                {
                    $squery = '';
                    foreach ($fetchby as $fkey => $fval)
                    {
                        if($fval=='' || $fval==null)
                        {
                            $val = $this->{"$fkey"} != null ? $this->{$fkey} : null;
                        }else
                        {
                            $val = $fval;
                        }

                        if($val) {
                            $selector = '=';
                            $like = 'like';
                            $conds = array('>=', '<=', '>', '<', $selector, $like);

                            foreach($conds as $cond)
                            {
                                $val = trim(str_replace($cond, '', $val, $rep));
                                if( $rep > 0)
                                {
                                    $selector = $cond;
                                    $val = $like==$cond?"%$val%":$val;
                                    break;
                                }
                            }

                            $squery .= "AND {$table_alias}.{$fkey} $selector '$val' ";
                        }
                    }
                    $fetchby = trim($squery, "AND");

                }elseif($this->{"$fetchby"} != '')
                {
                    $val = $this->{"$fetchby"} != null ? $this->{$fetchby} : null;
                    if($val) $fetchby = "{$table_alias}.{$fetchby} = '$val' ";
                }
            }else
            {
                $fetchby = ''; //seclect entire table

            }


            self::debug('FETCH BY.............');
            self::debug(var_export($fetchby, true));

            /////query defaults;

            $select  ="";
            $from    ="";
            $join    ="";
            $where   ="";
            $grouping="";


            //// grouping ////
            $grouping_counts = '';
            $grouping = "GROUP BY ";

            if( count($this->group_by) > 0)
            {
                foreach($this->group_by as $groupby)
                {
                    $grouping       .= "{$table_alias}.$groupby ,";
                    $grouping_counts  .= "\n COUNT({$table_alias}.$groupby) AS {$groupby}_count, \n {$table_alias}.$groupby AS '$groupby',";
                }

                $grouping = trim($grouping, ',');
            }else
            {
                $grouping = ($this->select_all === false? "GROUP BY {$table_alias}.{$this->record_key}" : "");
            }

            ///////////////////////////////

            self::debug(json_encode('GROUPING.............' . $grouping));
            self::debug(json_encode($this->group_by));


            /*
             SELECT
              COUNT(rt.condition) AS condition_count,
              rt.condition AS 'condition'
             FROM vehicle AS rt

             WHERE rt.dealer_hash = '3d09d134faff9636ab72482e860b5c4d'
             GROUP by rt.condition
              ;

             */

            if( count($this->working_tables) > 1)
            {
                $number = 0;

                if(count($this->group_by) > 0)
                {
                    $select = "SELECT $grouping_counts";
                }else
                {
                    $select = "SELECT {$table_alias}.* \n";
                }

                $join   = "";
                $from = "FROM $this->record_table AS $table_alias";
                $where = strlen($fetchby) > 2 ? "WHERE $fetchby" : "";
                $groupby = '';

                foreach($this->working_tables as $table)
                {
                    if($this->record_table == $table) continue;

                    $ctable = "t{$number}";
                    $fields = $this->db->getCrossSelectFields($this->record_table, $table, $ctable);
                    $fields = trim($fields);

                    if(count($this->group_by) == 0)$select .= ",\n\n$fields";

                    $linkby = $this->multiple_join_map[$table] ? $this->multiple_join_map[$table]->getLinkKey() : $this->multiple_join_map[$table]->getKey();
                    $linkon = self::getLinkOn() ? self::getLinkOn() : self::getKey();
                    $join   .= "LEFT JOIN $table AS $ctable ON {$ctable}.{$linkby} = {$table_alias}.{$linkon} \n";

                    $number++;
                }

            }else
            {
                if(count($this->group_by) > 0)
                {
                    $select = "SELECT $grouping_counts";
                }else
                {
                    $select = "SELECT {$table_alias}.* \n";
                }

                $from   =  "FROM $this->record_table AS $table_alias";
                $where  = strlen($fetchby) > 2 ? "WHERE $fetchby" : "";

                /*
                $table = self::getTable();
                $query = "SELECT * FROM $table rt";
                if(strlen($fetchby) > 2) $query .= "\nWHERE $fetchby";
                */
            }

            $select = trim($select, ",");

            $query = '';
            $query .= "$select  \n";
            $query .= "$from    \n";
            $query .= "$join    \n";
            $query .= "$where   \n";
            $query .= "$grouping \n";


            //// finally ORDER BY
            self::addSorts($query, $table_alias);

            $query.= " ;";
            ////// END QUERY ////


            $records = $this->db->fetch($query);

            if($records)
            {
                self::addData( self::getSlug(true), $records);
                return true;
            }

            if(!$records && $this->db->getError() == '')
            {
                return true;
            }

            return false;
        }


        public function fetchBy($fetchby = null)
        {
            if(!$fetchby ) return null;

            //self::debug('fetchby key: ' . var_export($fetchby, true) );

            //// START QUERY ////
            $table_alias = 'rt';

            if($fetchby)
            {
                if(is_array($fetchby))
                {
                    $squery = '';
                    foreach ($fetchby as $fkey => $fval)
                    {
                        if($fval=='' || $fval==null){
                            $val = $this->{"$fkey"} != null ? $this->{$fkey} : null;
                        }else{
                            $val = $fval;
                        }

                        if($val) {
                            $selector = '=';
                            $conds = array('>=', '<=', '>', '<', $selector);

                            foreach($conds as $cond){

                                $val = str_replace($cond, '', $val, $rep);
                                if( $rep > 0)
                                {
                                    $selector = $cond;
                                    break;
                                }
                            }

                            $squery .= "AND {$table_alias}.{$fkey} $selector '$val' ";
                        }
                    }
                    $fetchby = trim($squery, "AND");

                }elseif($this->{"$fetchby"} != '')
                {
                    $val = $this->{"$fetchby"} != null ? $this->{$fetchby} : null;
                    if($val) $fetchby = "{$table_alias}.{$fetchby} = '$val' ";
                }
            }else
            {
                self::addMessage('warning', 'can not fetch by empty reference ' . self::getSlug() );
                return null;
            }


            //if(array_key_exists($key, get_object_vars($this)))
            //{
            if( count($this->working_tables) > 1)
            {
                $select = "SELECT {$table_alias}.*";
                $join   = "";
                $from = "FROM $this->record_table AS $table_alias";
                $where = strlen($fetchby) > 2 ? " WHERE $fetchby" : " ";
                //$where = "WHERE $table_alias.{$key} = '{$val}'";

                $number = 0;

                foreach($this->working_tables as $table)
                {
                    if($this->record_table == $table) continue;

                    $ctable = "t{$number}";
                    $fields = $this->db->getCrossSelectFields($this->record_table, $table, $ctable);

                    $select .= ",\n\n$fields";

                    $linkby = $this->multiple_join_map[$table] ? $this->multiple_join_map[$table]->getLinkKey() : $this->multiple_join_map[$table]->getKey();
                    $linkon = self::getLinkOn() ? self::getLinkOn() : self::getKey();
                    $join   .= "LEFT JOIN $table AS $ctable ON {$ctable}.{$linkby} = {$table_alias}.{$linkon} ";

                    $number++;
                }

                $query = '';
                $query .= "$select  \n";
                $query .= "$from    \n";
                $query .= "$join    \n";
                $query .= "$where   \n";

            }else
            {
                $table = self::getTable();
                $query =  "SELECT {$table_alias}.*
                           FROM $table AS $table_alias "
                           . ( strlen($fetchby) > 2 ? " WHERE $fetchby" : "");
                           //WHERE {$key} = '{$val}'";

            }

            //// finally ORDER BY
            self::addSorts($query, $table_alias);


            /// EXECUTE QUERY
            $record = $this->db->fetchSingle($query);

            if($record)
            {
                $this->is_linked = true;

                foreach($record as $k => $v)
                {
                    if( strpos($k, 'date') > -1 ) $record->$k = Jk_Functions::htmlDate($v);
                }

                self::mergeData($record, $this, true, true);
                self::addData('record', $record);
                self::addData(self::getSlug(), $this);
                $this->linked_record = $record;
                return true;
            }
            //}

            return false;
        }



        protected function fetchColumn($column, $colval=null, $filters=null)
        {
            if($column==null) return self::addMessage('warning', 'can not fetch column by empty reference ' . self::getSlug() );

            $table_alias = 'rt';
            $fetchcolumn = trim($column);

            $table = self::getTable();

            $and_query = "";
            $xselects_query = "";

            if(is_array($filters))
            {
                $vars = @get_object_vars( $this );

                foreach($filters as $col => $val)
                {
                    if( array_key_exists($col, $vars) )
                    {
                        $and_query      .= "AND {$table_alias}.$col = '$val' \n";
                        $xselects_query .= ", {$table_alias}.$col";
                    }
                }
            }

            if($colval == null)
            {
                $where_qry = "WHERE ({$table_alias}.$fetchcolumn is not NULL OR {$table_alias}.$fetchcolumn != '')";
            }else{
                $where_qry = "WHERE {$table_alias}.$fetchcolumn = '$colval'";
            }

            $query =  "SELECT {$table_alias}.$fetchcolumn {$xselects_query}
                                        FROM $table AS $table_alias
                                        $where_qry
                                        $and_query
                                        ;";

            return $this->db->fetch($query);
        }


        protected function fetch($record_hash=null)
        {
            $table_alias = 'rt';

            if( count($this->working_tables) > 1)
            {
                $select = "SELECT {$table_alias}.*";
                $join   = "";
                $from = "FROM $this->record_table AS $table_alias";
                $where = "WHERE {$table_alias}.$this->record_key = '$record_hash'; ";

                $number = 0;

                foreach($this->working_tables as $table)
                {
                    if($this->record_table == $table) continue;

                    $ctable = "t{$number}";
                    $fields = $this->db->getCrossSelectFields($this->record_table, $table, $ctable);

                    $select .= ",\n\n$fields";

                    $linkby = $this->multiple_join_map[$table] ? $this->multiple_join_map[$table]->getLinkKey() : $this->multiple_join_map[$table]->getKey();
                    $linkon = self::getLinkOn() ? self::getLinkOn() : self::getKey();
                    $join   .= "LEFT JOIN $table AS $ctable ON {$ctable}.{$linkby} = {$table_alias}.{$linkon} ";

                    $number++;
                }

                $query = '';
                $query .= "$select  \n";
                $query .= "$from    \n";
                $query .= "$join    \n";
                $query .= "$where   \n";

            }else
            {
                $table = self::getTable();
                $query =  "SELECT {$table_alias}.*
                           FROM $table AS $table_alias
                           WHERE {$table_alias}.$this->record_key = '$record_hash'; ";

            }



            //// finally ORDER BY
            self::addSorts($query, $table_alias);

            $record = $this->db->fetchSingle($query);

            if($record)
            {
                $this->is_linked = true;

                foreach($record as $k => $v)
                {
                    if( strpos($k, 'date') > -1 ) $record->$k = Jk_Functions::htmlDate($v);
                }

                self::mergeData($record, $this, true, true);
                $this->linked_record = $record;
                return true;
            }


            return false;
        }

        private function addSorts(&$query, $table_alias='rt')
        {
            if($this->order_by && count($this->order_by) > 0)
            {
                $query.= "\n";
                $query.= "ORDER BY ";
                foreach($this->order_by as $key => $order_key )
                {
                    //$order_key = $order_key == '' ? "DESC" : $order_key;
                    $end = ($key+1) < count($this->order_by) ?', ' : " \n";
                    $query.= "{$table_alias}.{$order_key} $end";
                }
            }

            return $query;
        }

        ///// MAIN FETCH FUNCTION END ////



        ///// MAIN SAVE FUNCTIONS START ////
        public function save()
        {
            $record_hash  = self::getHash();
            $success = null;

            if($this->is_linked && $record_hash)
            {
                $success = self::update();
            }else
            {
                $success = self::create();
            }

            if($success)
            {
                self::addMessage('info',       self::getSlug() ." has been saved");
                //self::addData(self::getSlug(), self::getHash() );
                return true;
            }

            self::addMessage('info', self::getSlug() .' could not be saved');

            return false;
        }

        protected function create()
        {
            $user = Jk_Session::getUser();
            if( self::getHash() == '') self::autoHash();

            $this->creation_date    = date("Y-m-d H:i:s");

            //// add any changes before this line
            $data = get_object_vars($this);
            $tables = $this->working_tables;
            $success = null;

            foreach($data as $key => $val)
            {
                if( is_object($val) || is_array($val) ) $data[$key] = @json_encode($val);
            }


            foreach($tables as $table)
            {
                if ($table_link = $this->multiple_join_map[$table])
                {
                    self::debug("inserting table join: $table = $data my hash: ". self::getHash() );

                    $table_link->setSlug( self::getSlug()."_".$table_link->getSlug() );

                    //perhaps mergeData needed if critail data isnt copied to child
                    $table_link->mergeModelData($data, $table_link, true);
                    $table_link->setParent( self::getHash() );
                    $table_link->save();

                    self::debug("inserting table join: $table = $data my hash: ". self::getHash() );

                }else
                {
                    self::debug("inserting self: $table = $data my hash: ". self::getHash() );
                    $success = $this->db->insert($table, $data);
                }
            }

            //$this->debug("{$this->slug}_DAO:: --> create data sent to db");

            if($success)
            {
                $this->is_linked = true;
                self::addMessage('info',       self::getSlug() ." has been created");
                self::addData( self::getSlug(), self::getHash() );

                return true;
            }

            self::addMessage('info', self::getSlug() .' could not be saved');
            self::addMessage('mysql_error', $this->db->getError());

            return false;
        }


        protected function update()
        {
            $data = get_object_vars($this);
            $tables = $this->working_tables;
            $record_hash  = self::getHash();
            $updated = null;

            foreach($tables as $table)
            {

                self::debug("update: $table = $data");

                $linkedby = $this->record_key;

                if ($table_link = $this->multiple_join_map[$table])
                {
                    $linkedby = $table_link->getLinkKey();
                }

                $update_arr = array
                (
                    'table' => $table,
                    'data'  => $data,
                    'where' => "$linkedby = '$record_hash' "
                );

                $updated = $this->db->update($update_arr);

                if ( $table != $this->record_table && $table_link && $this->db->affected_rows == 0 )
                {
                    $table_link->setParent( self::getHash() );

                    if( $table_link->fetchBy( array($table_link->getLinkKey() => self::getHash() ) ) == false)
                    {
                        //perhaps mergeData needed if critail data isnt copied to child
                        $table_link->mergeModelData($data,$table_link, true);
                        $table_link->setParent( self::getHash() );

                        if($table_link->getHash()) $table_link->save();
                    }
                }
            }


            $this->debug("{$this->slug}_DAO:: --> update data sent to db");

            if($updated)
            {
                self::addMessage('info',       self::getSlug(). " has been updated");
                self::addData(self::getSlug(), self::getHash() );
                return true;
            }

            self::addMessage('mysql_error', $this->db->getError());
            self::addMessage('info',  " ther was an erro updating ".  self::getSlug() );
            return false;
        }


        public function delete($filters=null)
        {
            $record_hash = $this->getHash();

            if(!$record_hash || $record_hash =='')
            {
                self::addMessage('warning', 'can not delete by empty reference ' . self::getSlug() );
                return false;
            }


            foreach($this->working_tables as $table)
            {
                $linkby = $this->multiple_join_map[$table] ? $this->multiple_join_map[$table]->getLinkKey() : $this->record_key;

                $and_query = "";
                $xselects_query = "";

                if(is_array($filters))
                {
                    $vars = @get_object_vars( $this );

                    foreach($filters as $col => $val)
                    {
                        if( array_key_exists($col, $vars) )
                        {
                            $and_query  .= "AND {$table}.$col = '$val' \n";
                        }
                    }

                    $query =  "DELETE $table.* FROM {$table}
                                                WHERE $linkby = '$record_hash'
                                                $and_query
                                                ;";
                }else{
                    $query = "DELETE $table.* FROM $table WHERE $linkby = '$record_hash';";
                }

                $this->db->query($query);
            }



            if($this->db->affected_rows > 0)
            {
                $this->is_linked = false;
                self::addData( self::getSlug(), self::getHash());
                self::addMessage( self::getSlug(), self::getSlug() . ": " .self::getHash() . ' was deleted');
                return true;
            }

            self::addMessage('info', 'there was an error deleting ' . self::getSlug() );
            return false;
        }


        //// MAIN SAVE FUNCTIONS END ////

        public function getObjects($php_objects=false, $sync_dup_keys = false)
        {
            $return_objects = $db_objects = self::getData()->{self::getSlug(true)};
            //$return_objects = $db_objects;

            if($php_objects && $db_objects)
            {
                $return_objects = array();
                if(is_array($db_objects)) foreach($db_objects as $db_object)
                {
                    //self::debug($this->getSlug() . " objects, i can get here step 1");

                    $this_class = $this->getClassName();
                    //self::debug($this->getSlug() . " objects, i can get here step 2");
                    $php_object = new $this_class();

                    //self::debug($this->getSlug() . " objects, i can get here step 3, object ". $php_object->getSlug());
                    self::debug( $db_object->{$php_object->getKey()} );
                    $php_object->mergeData($db_object, $php_object, true, true);

                    //self::debug($this->getSlug() . " objects, i can get here step 4");
                    $php_object->setHash( $db_object->{$php_object->getKey() } );

                    $return_objects[] = $php_object;

                    //self::debug($this->getSlug() . " objects, i can get here step 5");

                    self::debug( $php_object->getSlug() . " object fetched");
                }

                if($sync_dup_keys) self::syncDuplicateData( $return_objects );
            }

            return $return_objects ? $return_objects : null;
        }


        public function clearDataObjects()
        {
            unset( self::getData()->{self::getSlug(true)} );
        }

        public function clearDataObject()
        {
            unset( self::getData()->{self::getSlug(false)} );
        }

        public function getObject($php_objects=false, $sync_dup_keys=false)
        {
            $return_object = $db_object = self::getData()->{self::getSlug()};

            if($php_objects && $db_object)
            {
                $this_class = $this->getClassName();
                $php_object = new $this_class();
                $php_object->mergeModelData($db_object, $php_object, true);
                $php_object->setHash($db_object->image_hash);

                $return_object = $php_object;

                if($sync_dup_keys) self::syncDuplicateData(array($return_object));
            }

            return $return_object ? $return_object : null;
        }



        public function syncDuplicateData($return_objects)
        {
            $this_vars = get_object_vars($this);

            if(is_array($return_objects)) foreach($return_objects as $return_object)
            {
                $vars = self::getDuplicateVars();
                self::debug(self::getSlug() ." add duplicate vars to php_object() ". var_export($vars, true));

                foreach($vars as $var)
                {
                    if(array_key_exists($var, $this_vars )){
                        $return_object->{$var} = $this->{$var};
                        self::debug($this->getSlug() . " add var to php_object()->$var");
                    }
                }
            }
        }


        public function getRecord()
        {
            return $this->linked_record;
        }


        public function getHash()
        {
            //return $this->{$this->record_key};
            return $this->{$this->record_key} != '' || $this->{$this->record_key} != null ? $this->{$this->record_key} : null;
        }


        protected function rehash()
        {
            $this->{$this->record_key} = md5(microtime() . rand(1,100) );
            $this->is_linked = false;
            return $this->{$this->record_key};
        }

        public function getTable()
        {
            return $this->record_table;
        }


        public function getKey()
        {
            return $this->record_key;
        }


        public function getLinkKey()
        {
            return $this->link_by_key;
        }


        public function getDate()
        {
            return $this->creation_date;
        }


        public function getSlug($plural = false)
        {
            return $plural ? "{$this->slug}s" : $this->slug;
        }


        public function setSlug($slug)
        {
            $this->slug = $slug;
        }

        public function autoHash()
        {
            $this->{$this->record_key} = md5(microtime()+rand(0,10000));
            $this->is_linked = false;
        }

        public function setLinked($linked = false)
        {
            $this->is_linked = $linked;
        }

		protected function validateInsert($a)
        {
        	if($a)
        	{
        		foreach ($a as $v)
        		{
        			if(!$this->$v)
        			{
        				self::debug("ERROR: $v is required to insert");
        				self::addMessage("error", "$v is required to insert");
        				return false;
        			}
        		}
        	}

        	return true;
        }

        protected function unsetKeys(&$data)
        {
            $keys = array($this->record_key, 'id', 'creation_date');
            foreach ($keys as $val) unset( $data["$val"] );
        }


        protected function addOutputVar($name, $val)
        {
            if($name == null) return;
            $this->addData('variables', array( "$name" => $val ) );
        }


        protected function injectOutputVar($name, $val)
        {
            if($name == null) return;
            $this->addData('inject', array( "$name" => $val ) );
        }

        protected function loadFromPost()
        {

            $post_hash = Jk_Request::getVar( $this->getKey() );
            if($post_hash)self::fetch($post_hash);

            return $this->is_linked;
        }

        protected function syncPost($post = false, $_rmslug=null, $_addslug=null)
        {
        	$request_data = (object) Jk_Request::fetchAll( ($post ? 'POST' : 'ALL') );

            $vars = get_object_vars($this);

            //self::debug($request_data);

            $post_copy = null;

            if($_addslug)
            {
                $post_copy = (object) array();
                foreach($request_data as $k => $v)
                {
                    $addslug = $_addslug."_$k";
                    array_key_exists($addslug, $vars) ? $post_copy->$addslug = $v : $post_copy->$k = $v;
                }
            }

            if($_rmslug)
            {
                $post_copy = (object) array();
                foreach($request_data as $k => $v)
                {
                    $rmslug = str_replace( $_rmslug."_", '', $k);

                    array_key_exists($rmslug, $vars) ? $post_copy->$rmslug = $v : $post_copy->$k = $v;
                }
            }

            $request_data = count($post_copy) > 0 ? $post_copy : $request_data;
            //self::debug($request_data);

            if($request_data->{$this->link_by_key}) unset($request_data->{$this->link_by_key});

        	self::mergeData( $request_data , $this);
        	//self::mergeModelData( $request_data , $this);
        }


        protected function unsetNoDBKeys($data)
        {
            $ndata = array();
            $keys = array_merge($this->def_ignored_dbkeys, $this->ignored_dbkeys);

            $data = array_keys( $data );

            foreach ($data as $k)
            {
                if (!in_array($k, $keys))
                {
                    $ndata[] = $k;
                }
            }

            return $ndata;
        }


        protected function confirmDataTable()
        {
            if( Jk_Config::AUTO_CREATE_TABLES == false && $this->AUTO_CREATE_TABLE == false ) return;

            if(!$this->record_table && !$this->record_key) return;

            $query = "CREATE TABLE IF NOT EXISTS `$this->record_table` \n( \n" .
                     "`id` int(16) unsigned NOT NULL auto_increment, \n" .
                     "`$this->record_key` varchar(34) default NULL, \n";

            //// MODEL VARS
            $vars= get_object_vars($this);
            $vars= $this->unsetNoDBKeys($vars);

            foreach($vars as $k)
            {
                $query.= "`$k` varchar(128) default NULL,\n";
            }
            //// DEAFAUL DATE TIME AND UNIQUE KEYS
            $query.=   "`creation_date` datetime default NULL, \n" .
                    "PRIMARY KEY  (`id`), \n" .
                    "KEY `$this->record_key` (`$this->record_key`) ";

            //// ADD INDEXES
            if($this->indexes && count($this->indexes) > 0)
            {
                $query.= ",\n";
                foreach($this->indexes as $index => $val )
                {
                    $end = ($val+1) < count($this->indexes) ?',' : '';
                    $query.= "UNIQUE KEY `$val` (`$val`) $end \n";
                }
            }

            $query.= ") \nENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";

            $this->db->query($query);

        }

        ///// USE THIS FUNCTION ONLY TO COPY DATA FOR DB INSERTS OR WHEN KEY HASHES NOT NEEDED
        public function mergeModelData( $omodel, $dobj= null, $add = true, $linear=true)
        {
            if( $omodel == null )
            {
                Jk_Base::debug("ERROR DONT SEND NULL MODELS TO MERGE \n" . Jk_Base::getDebugStack());
                return;
            }

            $dvars = @get_object_vars( $this );
            $dobj  = $dobj ? $dobj : $this;

            $modelvars = array
            (
                'id',
                'db',
                'request',
                'record_key',
                'record_table',
                'working_tables',
                'log_file',
                'log',
                'slug',
                'creation_date',
                'is_linked',
                'indexes',
                'order_by',
                'ignored_dbkeys',
                'multiple_join_map',
                'link_by_key',
                'link_on_key'
            );

            if($dobj->record_key)
            {
                if( $dobj->{$dobj->record_key} != '' ) $modelvars[]="$dobj->record_key";
            }
            if($dobj->parent_key)
            {
                if( $dobj->{$dobj->parent_key} != '') $modelvars[]="$dobj->parent_key";
            }

            $jsonvars = json_decode(json_encode($omodel), true);

            foreach( $jsonvars as $var => $val )
            {
                //Jk_Base::debug(self::getSlug() . "::mergeModelData() ... $var => $val");

                if ( in_array($var, $modelvars) ) continue;

                if ($add)
                {
                    if( is_object( $val ))
                    {
                        Jk_Base::debug(self::getSlug() ."ARRAY FOUND, SKIPPING COPYING ARRAYS/OBJECTS ... var skipped:$var");
                    }
                    elseif ($dobj->$var)
                    {
                        //Jk_Base::debug(self::getSlug() . "::mergeModelData() WILL COPY ... $var => $val");
                        // models will only have linear vars.. but!!!!
                        if($linear) $dobj->$var = $val;
                        else is_array($dobj->$var) ? array_push($dobj->$var, $val) : $dobj->$var = array($dobj->$var, $val);
                    }
                    else
                    {
                        //Jk_Base::debug(self::getSlug() . "::mergeModelData() WILL COPY ... $var => $val");
                        $dobj->$var = $val;
                    }

                }
                else
                {
                    if (array_key_exists($var, $dvars))
                    {
                        $dobj->$var = $val;
                    }
                }
            }

            return true;
        }
        
        protected function trace($_m, $verbose = false)
		{
            if(is_array($_m) || is_object($_m)) $_m =  var_export($_m, true);

			if( $this->log_file != null  )
			{
                if($this->log==null) $this->log = new Jk_Logger(APP_PATH . $this->log_file, Jk_Logger::DEBUG);
				$this->log->LogInfo(get_class($this) . " => $_m");
			}
	        else
	        {
	            self::debug(get_class($this) ." => $_m ");
	        }
		}


        
    }//END OF CLASS
    
    
?>