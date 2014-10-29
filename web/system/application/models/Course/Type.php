<?php
class Course_TypeModel extends BaseModel{
	public $table = 't_course_type';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getCourseTypeByPid($pid,$select = true){
		$result = $this->getAll(array('pid'=>$pid),'id,name');
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$data[$_result['id']] = $_result['name'];
			}
		}
		return $data;
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