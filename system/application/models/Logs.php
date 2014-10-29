<?php
class LogsModel extends BaseModel{
	public $table = 't_logs';
	public $_data = array(
		'hash' => '', 
		'app' => '', 
		'act' => '', 
		'character'=> '',
		'creator' => '', 
		'target' => array(), 
		'ext' => array(),
		'source' => array(), 
		'data' => array(),
		'type'=>0,
	);
	public function __construct() {
    	parent::__construct();
    } 
    
    public function addLog(array $data){
    	$data = array_merge($this->_data,$data);
    	foreach($data as &$_data){
    		if(is_array($_data)){
    			$_data = json_encode($_data);
    		}
    	}
    	$data['create_time'] = time();
    	return $this->insertData($data);
    }
}