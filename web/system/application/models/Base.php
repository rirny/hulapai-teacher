<?php
/**
 * @name BaseModel
 * @author liida
 */
class BaseModel {
    public $db = NULL;
    public $table = NULL;
    public function __construct() {
		$this->db = DB();
    }  
	
	public function getRow($where='',$field='*',$order=''){
		$this->db->select($field)->from($this->table);
		if($where) $this->db->where($where);
		if($order) $this->db->order_by($order);
		$this->db->limit(1);
		return $this->db->get()->row_array(1);
	}
	
	public function getAll($where='',$field='*',$order='',$group='',$limit=null,$offset=null){
		$this->db->select($field)->from($this->table);
		if($where) $this->db->where($where);
		if($group) $this->db->group_by($group);
		if($order) $this->db->order_by($order);
		if(! is_null($limit)){
			$this->db->limit($limit,$offset);
		}
		return $this->db->get()->result_array();
	}
	
	public function getCount($where=''){
		$this->db->from($this->table);
		if($where) $this->db->where($where);
		$total = $this->db->count_all_results();
		return $total;
	}
	
	public function getSum($field='',$where=''){
		$this->db->select_sum($field,$field)->from($this->table);
		if($where) $this->db->where($where);
		$data = $this->db->get()->row_array(1);
		return $data[$field] ? $data[$field]:0;
	}
	
	
	public function getList($page=1,$pagesize=20,$where='',$field='*',$group='',$order=''){
		$total = $this->getCount($where);
		$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$data = $this->getAll($where,$field,$order,$group,$pagesize,$offset);
		return array(
			'pages'=>$pages,
			'data'=>$data
		);
	}
	
	public function insertData(array $data){
		if(!$data) return false;
		$result = $this->db->insert($this->table,$data);
		if($result){
			return  $this->db->insert_id();
		}
		return false;
	}
	
	public function replaceData(array $data){
		if(!$data) return false;
		$result = $this->db->replace($this->table,$data);
		if($result){
			return true;
		}
		return false;
	}
	
	public function updateData(array $data,$where=''){
		if(!$data || !$where) return false;
		$result = $this->db->update($this->table,$data,$where);
		if($result){
			return true;
		}
		return false;
	}
	
	public function deleteData($where=''){
		if(!$where) return false;
		$result = $this->db->delete($this->table,$where);
		if($result){
			return true;
		}
		return false;
	}

	// 自增
	public function increment($key, $where, $step=1) 
	{
		if(!$key || !$where) return false;		
		return $this->db->update($this->table, array($key . " = " . $key . "+" .$step => NULL), $where);        
    }     
    // 自减        
	public function decrement($key, $where, $step=1) 
	{
		if(!$key || !$where) return false;		
		return $this->db->update($this->table, array($key . " = " . $key . "+" .$step => NULL), $where);
    }  

}
