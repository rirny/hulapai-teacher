<?php
class GradeModel extends BaseModel{
	public $table = 't_grade';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getGrade($school=0,$select = true){
		$result = $this->getAll(array('school'=>$school),'id,name');
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$data[$_result['id']] = $_result['name'];
			}
		}
		return $data;
    }
    
    public function deleteGrade($school,$id){
    	$this->db->trans_begin();
    	$this->db->update('t_event',array('grade'=>0),array('school'=>$school,'grade' => $id));// 更新班级课程班级为0
    	$this->db->delete('t_grade_student',array('school'=>$school,'grade' => $id));// 删除班级学生关系
        $this->db->delete('t_event_grade',array('grade' => $id));    // 删除班级课程关系
        $this->db->delete('t_grade',array('school'=>$school,'id'=>$id));//删除班级
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
    
    public function pushDelete($school,$id,$pushModel,$events = array(),$students=array()){
    	if(!$students) return false;
    	$_Push = new PushModel();
    	$_UserStudent = new User_StudentModel();
        foreach($students as $student){  
           	$parents = $_UserStudent->parents($student['student']);
           	$pushData = array_merge($pushModel,array('to' => $parents,'student'=>$student['student']));
           	$pushData['ext']['event'] = isset($events[$student['student']]) ? $events[$student['student']] : array();
			$_Push->addPush($pushData);
        }
        return true;
    }
}