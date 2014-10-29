<?php
class AttachModel extends BaseModel{
	public $table = 'ts_attach';
	public function __construct() {
    	parent::__construct();
    } 
	
	public function getAttachByAttachId($attach_id){
		$this->db->select("attach_id,save_path,save_name")->from('ts_attach')->where(array('attach_id'=>$attach_id));
		$attachInfo = $this->db->get()->row_array(1);
		if($attachInfo){
			$attachInfo['path'] = $attachInfo['save_path'].$attachInfo['save_name'];
		}
		return $attachInfo;
	}
	
	public function addAttach($uid,array $info){
		$data = array(
			'name' => $info['name'],
			'app_name' => 'app',
			'table' => '',
			'uid' => $uid,
			'type' => $info['type'],
			'ctime' => time(),
			'size' => $info['size'],
			'extension' => $info['extension'],
			'hash' => $info['hash'],
			'private' => 0,
			'save_path' => $info['save_path'],
			'save_name' => $info['save_name'],
			'save_domain' => '',
			'from' => 0,
		);
		$result = $this->db->insert('ts_attach',$data);
		if($result){
			return  $this->db->insert_id();
		}
		return false;
	}
    
    public function getAttachs($attach_ids)
    {
        $query = $this->db->query("select attach_id,uid,name as attach_name,size,extension,save_path,save_name from {$this->table} where attach_id in ($attach_ids)");
        return $query->result_array();
    }
}