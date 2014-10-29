<?php
class PushModel extends BaseModel{
	public $table = 't_push';
	public $_data = array(
		'app' => '', 
		'act' => '', 
		'from'=> '',
		'to' => '', 
		'student' => '', 
		'ext'=>array(),
		'type' => 0,
		'message' => '', 
	);
	public function __construct() {
    	parent::__construct();
    } 
    
    public function addPush(array $data){
    	$dataModel = array_merge($this->_data,$data);
    	$to = $dataModel['to'];
    	foreach($dataModel as &$_data){
    		if(is_array($_data)){
    			$_data = json_encode($_data);
    		}
    	}
    	$dataModel['create_time'] = time();
    	if($to != $dataModel['to']){
    		foreach($to as $_to){
    			$dataModel['to'] = $_to;
    			$this->insertData($dataModel);
    		}
    	}else{
    		$this->insertData($dataModel);
    	}
    	return true;
    }
}