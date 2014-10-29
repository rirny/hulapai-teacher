<?php
class UserModel extends BaseModel{
	public $table = 't_user';
	public function __construct() {
    	parent::__construct();
    } 
    
    // 注册
	public function register($user, $code=''){		
		$this->db->trans_begin();
		$this->db->insert('t_user',$user);
		$id = $this->db->insert_id();	
		$this->db->update('t_user',array('hulaid'=>'h_' . sprintf("%u", crc32($id))),array('id'=>$id));
		$code && $this->db->delete('t_verify_code',array('mobile'=>$user['account'],'type'=>0,'deadline >'=>0));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $id;
		}
		
	}
	
	
	// 找回密码
	public function findPwd($account,$data,$code=''){		
		$this->db->trans_begin();
		$this->db->update('t_user',$data,array('account'=>$account));
		$code && $this->db->delete('t_verify_code',array('mobile'=>$account,'type'=>1));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
		
	}
}