<?php
class Manufacturer extends db {
	
	public function add($postData)
	{			
		
		$arr['name'] = $postData['name'];
		$arr['date_add'] = $arr['date_upd'] = date('Y-m-d H:i:s');
		$arr['active'] = $postData['active'];

		$manufacturer_insert_status = $db->insert( 'manufacturer', $arr );
		
		$id_manufacturer  = $db->insert_id;
		
		$id_lang=$_POST['id_lang'];
		$lang_insert_status = $db->insert( 'manufacturer_lang', array('id_lang'=>$id_lang,'id_manufacturer'=>$id_manufacturer));
		
		$id_shop=$_POST['id_shop'];
		$shop_insert_status = $db->insert( 'manufacturer_shop', array('id_shop'=>$id_shop,'id_manufacturer'=>$id_manufacturer));
		
		if($manufacturer_insert_status && $lang_insert_status && $shop_insert_status)
			echo json_encode(array('msg'=>'Manufacturer Inserted','status'=>1,'id_manufacturer'=>$id_manufacturer));
		else
			echo json_encode(array('msg'=>'Manufacturer Not Inserted','status'=>0));
		
	}
	
	function update($postData)
	{		
		
		$arr['name'] = $postData['name'];
		$arr['date_upd'] = date('Y-m-d H:i:s');
		$arr['active'] = $postData['active'];
		
		$id_manufacturer = $postData['id_manufacturer'];
		
		$manufacturer_update_status = $db->update('manufacturer', $arr, array('id_manufacturer'=>$id_manufacturer));
		
		if($manufacturer_update_status)
			echo json_encode(array('msg'=>'Manufacturer Updated','status'=>1,'id_manufacturer'=>$id_manufacturer));
		else
			echo json_encode(array('msg'=>'Manufacturer Not Updated','status'=>0));		
	}
	
	function delete($id_manufacturer)
	{		

		$manufacturer_delete_status = $db->delete('manufacturer', array('id_manufacturer'=>$id_manufacturer));
		
		$lang_delete_status = $db->delete('manufacturer_lang', array('id_manufacturer'=>$id_manufacturer));
		$shop_delete_status = $db->delete('manufacturer_shop', array('id_manufacturer'=>$id_manufacturer));
		
		if($manufacturer_delete_status && $lang_delete_status && $shop_delete_status)
			echo json_encode(array('msg'=>'Manufacturer Deleted','status'=>1));
		else
			echo json_encode(array('msg'=>'Manufacturer Not Deleted','status'=>0));		
	}
	
	function get($id_manufacturer)
	{			
		
		$result = $db->get_row( 'manufacturer', array( 'id_manufacturer'=> $id_manufacturer ));

		if($result)
			echo json_encode($result);		
	}
	
	function getAll()
	{
		$result = $db->get_all('manufacturer');

		if($result)
			echo json_encode($result);		
	}	

	function getAllManufacturer()
	{
		
		$result = $db->get_all('manufacturer');

		return $result;
	}	

	
	function getManufacturer($id_manufacturer)
	{			
		
		$result = $db->get_row( 'manufacturer', array( 'id_manufacturer'=> $id_manufacturer ));

		return $result;		
	}

	/*get_manufacturers written by kuldeep*/
	public function get_manufacturers($params){

		if($params['conditions']['id_shop']=='' ){
		return resultArray(false, NULL, 'Please pass id_shop in parameter!');
		}
        
        if($params['conditions']['id_lang']=='' ){
		return resultArray(false, NULL, 'Please pass id_lang in parameter!');
		}

		$query = '
			SELECT manufacturer.*,manufacturer_shop.*, manufacturer_lang.*
			FROM manufacturer
				INNER JOIN manufacturer_shop ON manufacturer.id_manufacturer = manufacturer_shop.id_manufacturer
				INNER JOIN manufacturer_lang ON manufacturer.id_manufacturer  = manufacturer_lang.id_manufacturer 
			WHERE manufacturer_shop.id_shop = :id_shop
				AND manufacturer_lang.id_lang = :id_lang
			
		';
		$values = array(
			':id_shop' => $params['conditions']['id_shop']
			, ':id_lang' => $params['conditions']['id_lang']
		);
	
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get manufacturer.');
      	}

        return resultArray(true, $this->result);
	
	}

	public function get_manufacturer_info($params){

       if($params['conditions']['id_manufacturer']=='' ){
		return resultArray(false, NULL, 'Please pass id_manufacturer in parameter!');
		}

		$query = '
			SELECT manufacturer.*,manufacturer_shop.*, manufacturer_lang.*
			FROM manufacturer
				INNER JOIN manufacturer_shop ON manufacturer.id_manufacturer = manufacturer_shop.id_manufacturer
				INNER JOIN manufacturer_lang ON manufacturer.id_manufacturer  = manufacturer_lang.id_manufacturer 
			WHERE manufacturer.id_manufacturer = :id_manufacturer			
			
		';
		$values = array(
			':id_manufacturer' => $params['conditions']['id_manufacturer']			
		);
	
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get manufacturer.');
      	}

        return resultArray(true, $this->result);
	
	
	
	}


	public function add_manufacturer($param){
          
         if($param['data']['manufacturer']=='' ){
		return resultArray(false, NULL, 'Please pass manufacturer in parameter!');
		}
        
        if($param['data']['id_shop']=='' ){
		return resultArray(false, NULL, 'Please pass shop in parameter!');
		}
		if($param['data']['lang']=='' ){
		return resultArray(false, NULL, 'Please pass lang in parameter!');
		}
		
		date_default_timezone_set('America/Los_Angeles'); 	
		$manufacturer = array();
		$shop = array();
		$lang = array();
	
		$manufacturer = $param['data']['manufacturer'];
		$manufacturer['date_add']=date('Y-m-d H:i:s');
		$manufacturer['date_upd']=date('Y-m-d H:i:s');

		$shop = $param['data']['shop'];
		$lang = $param['data']['lang'];		
		
		$manufacturer_insert_status = $this->insert('manufacturer', $manufacturer );		
		$id_manufacturer  =$this->insert_id;
		
		$shop['id_manufacturer']=$id_manufacturer;
		$lang['id_manufacturer']=$id_manufacturer;
	
		$lang_insert_status = $this->insert('manufacturer_lang', $lang);		
		$shop_insert_status = $this->insert('manufacturer_shop', $shop);
		
		if($manufacturer_insert_status && $lang_insert_status && $shop_insert_status)
		
			return resultArray(true, $id_manufacturer);
		else
			return resultArray(false, NULL, 'Could not insert manufacturer.');
	
	
	}
	

}	
	
?>
