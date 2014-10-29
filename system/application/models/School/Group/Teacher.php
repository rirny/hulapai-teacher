<?php
class School_Group_TeacherModel extends BaseModel{
	public $table = 't_school_group_teacher';
	public function __construct() {
    	parent::__construct();
    } 
    
    
    public function updateTeacherGroup($school,$teacher,$newGroups,$oldGroups){
    	$new = array_diff($newGroups, $oldGroups);
		$lost = array_diff($oldGroups, $newGroups);
		if(!$new && !$lost){
			return true;
		}
		$this->db->trans_begin();
    	if($new){	
			$data = array(
				'school'=>$school,
				'teacher'=>$teacher,
			);
			foreach($new as $_new){
				$data['group'] = $_new;
				$this->db->insert('t_school_group_teacher',$data);
			}
		}
		if($lost){			
			foreach($lost as $_lost){
				$this->db->delete('t_school_group_teacher',array('school'=>$school,'group'=>$_lost,'teacher'=>$teacher));
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