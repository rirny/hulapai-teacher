<?php
class VerifyModel extends BaseModel{
	public $table = 't_verify_code';
	public function __construct() {
    	parent::__construct();
    } 
    
    // 验证码
	public function verify($mobile, $code, $type=0){		
		$res = $this->getRow(array('type'=>$type,'code'=>$code));
		if(!$res) return false;	
		if(0 == $res['deadline']) return true;      
		if(time() > $res['deadline']) return false;	
		if($res['mobile'] == $mobile) return true;
		return false;
	}
	
	public function send($mobile, $type=0, $message=''){
		if(!$mobile) return -9020;
		$res = $this->getRow(array('mobile' => $mobile, 'type' => $type));
		$tm = time();
		$this->db->trans_begin();
		if(empty($res) || $res['deadline'] <= $tm){
			if($res){
				$this->deleteData(array('id'=>$res['id']));
			}
			$deadline = $tm + 5*60;
			$create_time = $tm;
			$code = str_pad(Rand(0, 999999), 6, "0", STR_PAD_LEFT);
			$send_time = '';
			$status = 0;
			$data = compact('mobile', 'type', 'code', 'create_time', 'deadline');			
			$id = $this->insertData($data);
		}else{
			extract($res);
		}	
		if($send_time > $tm - 30) return -2;
		$data = array();
		if($id){	
			$rs = SMS()->sendSMS(array($mobile), str_replace('{code}', $code, $message));
			if($rs == 0){
				$this->updateData(array('status' => 1, 'send_time'=> $tm), array('id'=>$id)); // 已发送
				$data =  compact('id', 'mobile', 'code', 'type');
			}
		}
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return -117;
		}else{
		    $this->db->trans_commit();
		    return $data ? $data : -117;
		}	
	}
}