<?php
class StudentModel extends BaseModel{
	public $table = 't_student';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getStudentList($page=1,$pagesize=20,$studentName="",$status=-1){
		$sql =  " FROM `t_student` as a left join t_user_student as b on a.id=b.student" .
    			" where a.source = 0 and b.user > 200";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	if($studentName) $sql .= " and a.name like '%$studentName%'";
    	$sql .= " order by a.create_time desc";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery =  "SELECT a.id,a.name,a.nickname,a.gender,a.create_time".$sql." limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    public function getStudent($select = true,$simple=0,$avater=0){
		$sql = "SELECT a.id,a.name,LEFT(a.name_en,1) AS name_en,GROUP_CONCAT(b.grade) AS grade FROM t_student AS a LEFT JOIN t_grade_student AS b ON a.id=b.student AND b.school=0 WHERE a.status=0 AND a.source=0 GROUP BY a.id order by LEFT(a.name_en,1)";
		$result =  $this->db->query($sql)->result_array();
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$_result['grade'] = $_result['grade'] ? explode(',',$_result['grade']) : array();
				if($simple){
					$data[$_result['id']] = $_result['name'];
				}else{
					if($avater) $_result['avaterUrl'] = imageUrl($_result['id'],2,30,false);
					$data[$_result['id']] = $_result;
				}
			}
		}
		return $data;
    }
    
    public function addStudent($relationData = array(),$studentData = array()){
    	$this->db->trans_begin();
    	$this->db->insert('t_student',$studentData);
    	$id = $this->db->insert_id();
    	if($relationData){
    		$relationData['student'] = $id;
    		$this->db->insert('t_user_student',$relationData);
    	}
    	if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $id;
		}
    }
    
    
    public function updateStudent($sid,$uid,$relationData = array(),$studentData = array()){
    	$this->db->trans_begin();
    	if($relationData && $uid){
    		$this->db->update('t_user_student',$relationData,array('user'=>$uid,'student'=>$sid));
    	}
    	$this->db->update('t_student',$studentData,array('id'=>$sid));
    	if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $sid;
		}
    }
}