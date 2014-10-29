<?php
class ApplyModel extends BaseModel{
	public $table = 't_apply';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function sendApplyForSchoolAddTeacher($school,$teacher,$creator){
    	$data = array(
    		'from'=>$school,
    		'to'=>$teacher,
    		'type'=>3,
    		'create_time'=>date('Y-m-d H:i:s'),
    		'creator'=>$creator
    	);
    	$id = $this->insertData($data);
    	return $id;
    }
    
    public function getApplyForSchoolAddTeacher($school,$teacher){
    	return $this->getRow(array('from'=>$school,'to'=>$teacher,'type'=>3,'status'=>0));
    }
   
    
    public function getApplyListForSchoolAddTeacher($page=1,$pagesize=20,$school=0){
    	return $this->getList($page,$pagesize,array('from'=>$school,'type'=>3,'status !='=>1));
    }
    
    public function getApplyListForTeacherAddSchool($page=1,$pagesize=20,$school=0){
    	return $this->getList($page,$pagesize,array('to'=>$school,'type'=>4,'status !='=>1));
    }
    
    public function sendApplyForSchoolAddStudent($school,$uid,$creator){
    	$data = array(
    		'from'=>$school,
    		'to'=>$uid,
    		'type'=>7,
    		'create_time'=>date('Y-m-d H:i:s'),
    		'creator'=>$creator
    	);
    	$id = $this->insertData($data);
    	return $id;
    }
    
    public function getApplyForSchoolAddStudent($school,$uid){
    	return $this->getRow(array('from'=>$school,'to'=>$uid,'type'=>7,'status'=>0));
    }
   
    
    public function getApplyListForSchoolAddStudent($page=1,$pagesize=20,$school=0){
    	return $this->getList($page,$pagesize,array('from'=>$school,'type'=>7,'status !='=>1));
    }
    
    public function getApplyListForStudentAddSchool($page=1,$pagesize=20,$school=0){
    	return $this->getList($page,$pagesize,array('to'=>$school,'type'=>6,'status !='=>1));
    }
    
    
    public function sendApplyForStudentAddSchool($uid,$school,$student){
    	$data = array(
    		'from'=>$uid,
    		'to'=>$school,
    		'student'=>$student,
    		'type'=>6,
    		'create_time'=>date('Y-m-d H:i:s'),
    		'creator'=>$uid
    	);
    	$id = $this->insertData($data);
    	return $id;
    }
    
    public function getApplyForStudentAddSchool($uid,$school,$student){
    	return $this->getRow(array('from'=>$uid,'to'=>$school,'student'=>$student,'type'=>6,'status'=>0));
    }
    
    public function sendApplyForTeacherAddSchool($uid,$school){
    	$data = array(
    		'from'=>$uid,
    		'to'=>$school,
    		'type'=>4,
    		'create_time'=>date('Y-m-d H:i:s'),
    		'creator'=>$uid
    	);
    	$id = $this->insertData($data);
    	return $id;
    }
    
    public function getApplyForTeacherAddSchool($uid,$school){
    	return $this->getRow(array('from'=>$uid,'to'=>$school,'type'=>4,'status'=>0));
    }
}