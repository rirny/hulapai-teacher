<?php
class TeacherModel extends BaseModel{
	public $table = 't_teacher';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getTeacherList($page=1,$pagesize=20,$teacherName="",$status=-1){
		$sql =  " FROM (`t_teacher` AS a)" .
    			" JOIN `t_user` AS b ON `a`.`user` = `b`.`id`" .
    			" where 1 and b.id > 200";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	if($teacherName) $sql .= " and (b.firstname like '%$teacherName%' or b.lastname like '%$teacherName%')";
    	$sql .= " order by a.create_time desc";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery =  "SELECT a.*,b.firstname,b.lastname,b.gender,b.account".$sql." limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    public function getTeacher($select = true,$simple =0,$avater=0){
		$sql = "SELECT a.user,b.firstname,b.lastname,LEFT(b.firstname_en,1) AS name_en,b.account,b.hulaid FROM t_teacher AS a LEFT JOIN t_user AS b ON a.user=b.id WHERE a.status=0 order by LEFT(b.firstname,1)";
		$result =  $this->db->query($sql)->result_array();
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				if($simple){
					$data[$_result['user']] = ($_result['firstname'] || $_result['lastname']) ? $_result['firstname'].$_result['lastname'] : $_result['nickname'];;
				}else{
					if($avater) $_result['avaterUrl'] = imageUrl($_result['user'],1,30,false);
					$data[$_result['user']] = $_result;
				}
			}
		}
		return $data;
    }
    
    
    public function addTeacher($uid,$userData = array(),$teacherData = array()){
    	$this->db->trans_begin();
    	if($userData){
    		$userData['teacher'] = 1;
    		$this->db->update('t_user',$userData,array('id'=>$uid));
    	}
    	$this->db->insert('t_teacher',$teacherData);
    	if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $uid;
		}
    }
    
    
    public function updateTeacher($uid,$userData = array(),$teacherData = array()){
    	$this->db->trans_begin();
    	if($userData){
    		$userData['teacher'] = 1;
    		$this->db->update('t_user',$userData,array('id'=>$uid));
    	}
    	$this->db->update('t_teacher',$teacherData,array('user'=>$uid));
    	if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $uid;
		}
    }
    
    
    
}