<?php
require('models/database.php');

class Media extends db {

	private $image_table = 'image';
	private $image_lang_table = 'image_lang';
	private $image_shop_table = 'image_shop';
	private $image_type_table = 'image_type';
	
	public function __construct() {
		parent::__construct();
	}

	//do_resize_image
	function resizeImage($file, $width = 0, $height = 0, $proportional = true, $output = 'file')	{
		
		if($height <= 0 && $width <= 0)	return false;
		
		$info 	= getimagesize($file);
		$image 	= '';
	
		$final_width 	= 0;
		$final_height 	= 0;
		list($width_old, $height_old) = $info;
	
		if($proportional)	{
			if ($width == 0) $factor = $height/$height_old;
			elseif ($height == 0) $factor = $width/$width_old;
			else $factor = min ( $width / $width_old, $height / $height_old);
		
			$final_width = round ($width_old * $factor);
			$final_height = round ($height_old * $factor);
		
			if($final_width > $width_old && $final_height > $height_old)	{
				$final_width = $width_old;
				$final_height = $height_old;
			}
		}
		else	{
			$final_width = ( $width <= 0 ) ? $width_old : $width;
			$final_height = ( $height <= 0 ) ? $height_old : $height;
		}
	
		switch($info[2])	{
			case IMAGETYPE_GIF:
				$image = imagecreatefromgif($file);
			break;
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($file);
			break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($file);
			break;
			default:
			
			return false;
		}
	
		$image_resized = imagecreatetruecolor( $final_width, $final_height );
	
		if(($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG))	{
			$trnprt_indx = imagecolortransparent($image);
	
			if($trnprt_indx >= 0)	{
					$trnprt_color    = imagecolorsforindex($image, $trnprt_indx);
					$trnprt_indx    = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
					imagefill($image_resized, 0, 0, $trnprt_indx);
					imagecolortransparent($image_resized, $trnprt_indx);
			}
			elseif($info[2] == IMAGETYPE_PNG)	{
					imagealphablending($image_resized, false);
					$color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
					imagefill($image_resized, 0, 0, $color);
					imagesavealpha($image_resized, true);
			}
		}
		
		imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
	
		switch( strtolower($output))	{
			case 'browser':
				$mime = image_type_to_mime_type($info[2]);
				header("Content-type: $mime");
				$output = NULL;
			break;
			case 'file':
				$output = $file;
			break;
			case 'return':
				return $image_resized;
			break;
			default:
			break;
		}
	
		if(file_exists($output))	{
			@unlink($output);
		}
	
		switch($info[2])	{
			case IMAGETYPE_GIF:
				imagegif($image_resized, $output);
			break;
			case IMAGETYPE_JPEG:
				imagejpeg($image_resized, $output, 100);
			break;
			case IMAGETYPE_PNG:
				imagepng($image_resized, $output);
			break;
			default:
			return false;
		}
		return true;
	}
	
	//delete_pic_images
	function deleteImage($thepp)	{
		global $config, $conn;
		
		if($thepp != "")	{		
			  $dp1 = $config['pdir']."/t/l-".$thepp;
			  @chmod($dp1, 0777);
			  if (file_exists($dp1))	
				  @unlink($dp1);
			  
			  $dp1 = $config['pdir']."/t/".$thepp;
			  @chmod($dp1, 0777);
			  if (file_exists($dp1))
				  @unlink($dp1);
			  
			  $dp1 = $config['pdir']."/t/s-".$thepp;
			  @chmod($dp1, 0777);
			  if (file_exists($dp1))
				  @unlink($dp1);
			  
			  $dp1 = $config['pdir']."/t/t-".$thepp;
			  @chmod($dp1, 0777);
			  if (file_exists($dp1))
				  @unlink($dp1);
		}
	}	
	
	function upload_iamge($file, $upload_path) {
		$allowedExts = array("jpg", "jpeg", "gif", "png");
		$extension = end(explode(".", $file["name"]));
		if ((($file["type"] == "image/gif")	|| ($file["type"] == "image/jpeg")	|| ($file["type"] == "image/png") || ($file["type"] == "image/pjpeg"))	&& ($file["size"] < 20000)	&& in_array($extension, $allowedExts))  {
		  if ($file["error"] > 0){
				return "Return Code: " . $file["error"] . "<br>";
		  }
		  else {
				if (file_exists($upload_path . '/' . $file["name"])) {
					return $file["name"] . " already exists. ";
				}else {
				  move_uploaded_file($file["tmp_name"],  $upload_path . '/' . $file["name"]);
				  $this->resizeImage($upload_path . '/' . $file["name"]);
				  return "success";
				}
		  }
		}else {
		  return "Invalid file";
	    }
	}
	
	public function addImage($data=array()){
		$resp = $this->upload_iamge($data['file_details'], $data['upload_path']);
		if($resp !== 'success') {
			return $resp;
		}else{
			$image_id = $this->insert($this->image_table,$data);
			$this->addImagelang(array('lang_info' => $data['lang_info']), $image_id);
			$this->addImageshop(array('shop_id' => $data['shop_id']), $image_id);
			return true;
		}
	}
	
	public function addImageshop($data, $image_id){
		$data = array('id_image' => $image_id, 'id_shop' => $data['shop_id']);
		$this->insert($this->image_shop_table,$data);
		return true;
	}
	
	public function addImagelang($data=array(), $image_id){
		foreach($data as $key => $value) {
			if($value != '') {
				$data = array('id_image' => $image_id, 'id_lang' => $key, 'legend' => $value['legend']);
				$this->insert($this->image_lang_table,$data);
			}
		}
		return true;
	}

	public function addImagetype($data=array()){
		$this->insert($this->image_type_table,$data);
		return $this->insert_id;
	}
	
	public function deleteImagelang($where=array()){
		$res = $this->delete($this->image_lang_table,$where);
		return $res;
	}
	
	public function updateImage($data=array(), $where=array()){
		$where_image_id = array( 'id_image' =>$where['id_image']);
		$this->deleteImagelang($where_image_id);
		$this->addImagelang($data['lang_info']);
		return $res;
	}
	
	public function deleteImage($where=array()){
		$this->deleteImage($where['image_path']);
		$this->delete($this->image_shop_table,$where['id_image']);
		$this->delete($this->image_type_table,$where['id_image']);
		$this->deleteImagelang($where['id_image']);
		$this->delete($this->image_table,$where['id_image']);
		return true;
	}

	public function getImage($condition = array()){
		$image = array();
		$image['info'] = $this->get_row($this->image_table,$condition);
		$image['lang'] = $this->getImagelang($condition);
		$image['type'] = $this->getImagetype($condition);
		return json_encode($image) ;
	}
	
	public function getAllImages($condition = array()){
		$image_op = array();
		$images = $this->get_all($this->image_table,$condition, array('id_image'));
		foreach($images as $key => $value) {
			$image_op[] = $this->getImage(array('id_image' => $value));
			
		}
		return json_encode($image_op) ;
	}
	
	public function getAllImagesbyshop($condition = array()){
		$image_op = array();
		$images = $this->run("select i.id_image from image as i left join image_shop as is on is.id_image = i.id_image where id_shop = $condition['id_shop']");
		foreach($images as $key => $value) {
			$image_op[] = $this->getImage(array('id_image' => $value));
			
		}
		return json_encode($image_op) ;
	}
	
	public function getImagelang($condition){
		$image_lang = $this->get_all($this->image_lang_table,$condition);
		return $image_lang;
	}
	
	public function getImageshop($condition){
		$image_shop = $this->get_all($this->image_shop_table,$condition);
		return $image_shop;
	}
	
	public function getImagetype($condition){
		$image_type= $this->get_all($this->image_type_table,$condition);
		return $image_type;
	}
}
?>