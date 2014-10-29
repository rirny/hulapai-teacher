<?php
class VoteModel extends BaseModel{
	public $table = 't_vote';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getVote($school=0,$select = true){
		$result = $this->getAll(array('school'=>$school,'end_time >='=>time()),'id,title');
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$data[$_result['id']] = $_result['title'];
			}
		}
		return $data;
    }
    
    public function addVote(array $data,array $option){
		if(empty($data) || empty($option)) return false;
		$this->db->trans_begin();
		$this->db->insert('t_vote',$data);
		$voteId = $this->db->insert_id();
		foreach($option as $key=>$_option){
			$optionData = array(
				'title'=>$_option,
				'vote'=>$voteId,
				'sort'=>$key
			);
			$this->db->insert('t_vote_option',$optionData);
		}
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $voteId;
		}
    }
    
    public function updateVote($id,array $data,array $option){
		if(empty($data) && empty($option)) return false;
		$this->db->trans_begin();
		if($data) $this->db->update('t_vote',$data,array('id'=>$id));
		if($option){
			$this->db->update('t_vote_option',array('sort'=>99999),array('vote'=>$id));
			foreach($option as $key=>$_option){
				$optionData = array(
					'title'=>$_option,
					'vote'=>$id,
					'sort'=>$key
				);
				$this->db->select()->from('t_vote_option')->where(array('id'=>$key,'vote'=>$id));
				$info = $this->db->get()->row_array(1);
				if($info){
					$this->db->update('t_vote_option',$optionData,array('id'=>$info['id']));
				}else{
					$this->db->insert('t_vote_option',$optionData);
				}
			}
			$this->db->delete('t_vote_option',array('sort'=>99999,'vote'=>$id));
		}
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
    
    public function deleteVote($id){
    	$this->db->trans_begin();
		$this->db->delete('t_vote',array('id'=>$id));
		$this->db->delete('t_vote_option',array('vote'=>$id));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
    
    public function doVote($id,$data=array(),$options = array()){
    	if(!$id || empty($data) || empty($options)) return false;
    	$this->db->trans_begin();
    	$this->db->update('t_vote', array("count = count+1" => NULL), array('id'=>$id));
		foreach($options as $_option){
			$data['option'] = $_option;
			$this->db->insert('t_vote_record', $data);
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