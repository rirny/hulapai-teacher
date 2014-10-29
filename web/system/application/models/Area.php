<?php
class AreaModel extends BaseModel{
	public $table = 't_area';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getAreaByPid($pid,$select = true){
		$result = $this->getAll(array('pid'=>$pid),'id,title');
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$data[$_result['id']] = $_result['title'];
			}
		}
		return $data;
    }
}