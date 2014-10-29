<?php
class User_StudentModel extends BaseModel{
	public $table = 't_user_student';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function parents($student,$str = false){
    	$result = $this->getAll(array('student'=>$student),'user');
    	$data = array();
		if($result){
			foreach($result as $_result){
				$data[] = $_result['user'];
			}
		}
    	if($str){
    		return implode(',',$data);
    	}else{
    		return $data;
    	}
    }
}