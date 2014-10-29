<?php
class SchoolModel extends BaseModel{
	public $table = 't_school';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function addSchool($uid=0,$gid=0,array $data,$type=0){
    	if(!$uid || !$data['code'] || !$data['name']) return false;
    	$insertData = array(
			'code'=>$data['code'],
			'name'=>$data['name'],
			'pid'=>$data['pid'],
			'type'=>$data['type'],
			'province'=>$data['province'],
			'city'=>$data['city'],
			'area'=>$data['area'],
			'address'=>$data['address'],
			'contact'=>$data['contact'],
			'phone'=>$data['phone'],
			'phone2'=>$data['phone2'],
			'description'=>$data['description'],
			'creator'=>$uid,
			'create_time'=>time(),
		);
    	$this->db->trans_begin();
		$this->db->insert('t_school',$insertData);
		$schoolId = $this->db->insert_id();
		//创建机构教务组
		$insertData = array(
			'name'=>'教务组',
			'type'=>'school',
			'school'=>$schoolId,
			'enable'=>''
		);
		$this->db->insert('t_admin_user_group',$insertData);
		$id = 0;
		if($type){
			//创建机构超管
			$insertData = array(
				'uid'=>$uid,
				'gid'=>$gid,
				'type'=>'school',
				'school'=>$schoolId,
				'enable'=>'*'
			);
			$this->db->insert('t_admin_user',$insertData);
			$id = $this->db->insert_id();
		}
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		   return false;
		}else{
		    $this->db->trans_commit();
		    if($type) return $id;
		    return $schoolId;
		}
    }
    
    public function deleteSchool($id=0){
    	if(!$id) return false;
    	$idInfos = $this->getAll("id = $id or pid = $id",'id');
    	$ids = array();
    	if($idInfos){
    		foreach($idInfos as $idInfo){
    			$ids[] = $idInfo['id'];
    		}
    	}
    	if(!$ids) return false;
    	$ids = implode(',',$ids);
    	$this->db->trans_begin();
		$this->db->delete('t_school',"id in ($ids)");
		$this->db->delete('t_admin_user',"type = 'school' and school in ($ids)");
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		   return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
}