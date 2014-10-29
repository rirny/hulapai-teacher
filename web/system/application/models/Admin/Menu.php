<?php
class Admin_MenuModel extends BaseModel{
	public $table = 't_admin_menu';
	public function __construct() {
    	parent::__construct();
    } 
	
	/**
	 *  检查指定菜单是否有权限
	 * @param array $id menuid
	 * @param string $priv_data 需要检查的权限字符串
	 */
	public function hasPriv($id,$priv_data='*') {
		$enable = array();
		if($priv_data == '*'){
			return true;
		}else{
			$enable = explode(',',$priv_data);
		}
		if(!$enable || !in_array($id,$enable)){
			return false;
		}else{
			return true;
		}
	}
	
	
	/**
	 * 获取菜单深度
	 * @param $id
	 * @param $array
	 * @param $i
	 */
	public function getLevel($id,$array=array(),$i=0) {
		foreach($array as $n=>$value){
			if($value['id'] == $id)
			{
				if($value['pid']== '0') return $i;
				$i++;
				return $this->getLevel($value['pid'],$array,$i);
			}
		}
	}
}