<?php
class School_GroupModel extends BaseModel{
	public $table = 't_school_group';
	public function __construct() {
    	parent::__construct();
    } 
    
    
    public function getSchoolGroup($school,$select = true){
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
    
    public function deleteGroup($school,$id){
    	$this->db->trans_begin();
		$this->db->delete('t_school_group',array('school'=>$school,'id'=>$id));
		$this->db->delete('t_school_group_teacher',array('school'=>$school,'group'=>$id));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
}