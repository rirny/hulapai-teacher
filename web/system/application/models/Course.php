<?php
class CourseModel extends BaseModel{
	public $table = 't_course';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getSchoolCourseType($school,$select = true){
		$result = $this->getAll(array('school'=>$school),'id,title');
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