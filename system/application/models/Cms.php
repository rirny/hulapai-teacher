<?php
class CmsModel extends BaseModel{
	public $table = 'v9_page';
	public function __construct() {
    	parent::__construct();
		$this->db = DB('dbrCms');
    } 
    
    public function getContent($catid){
		return $this->getAll(array('catid'=>$catid),'*');
    }


	public function catalogList($catid){
		$sql = "select v.*,c.content from v9_hulapai v left join v9_hulapai_data c on v.id=c.id where catid=" . $catid;
		$query = $this->db->query($sql);
		return $query->result_array();		
    }
}