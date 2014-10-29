<?php
class Grade_StudentModel extends BaseModel{
	public $table = 't_grade_student';
	public function __construct() {
    	parent::__construct();
    } 
    
    
    public function updateStudentGrade($school,$student,$uid,$newGrades,$oldGrades){
    	$new = array_diff($newGrades, $oldGrades);
		$lost = array_diff($oldGrades, $newGrades);
		if(!$new && !$lost){
			return true;
		}
		$this->db->trans_begin();
    	if($new){	
			$data = array(
				'school'=>$school,
				'student'=>$student,
				'creator'=>$uid,
				'create_time'=>time(),
			);
			foreach($new as $_new){
				$data['grade'] = $_new;
				$this->db->insert('t_grade_student',$data);
			}
		}
		if($lost){			
			foreach($lost as $_lost){
				$this->db->delete('t_grade_student',array('school'=>$school,'grade'=>$_lost,'student'=>$student));
			}
		}
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
}