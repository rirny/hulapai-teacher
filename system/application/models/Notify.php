<?php
class NotifyModel extends BaseModel{
	public $table = 't_notify';
	public function __construct() {
    	parent::__construct();
    } 
    
    
    /**
     * 推送
     */
    public function push($id,$uid,$teachers,$students,$type=0,$data=array()){
		$hash = md5($id).rand(10000,99999);
		$logsData = array(
			'hash'=>$hash,
			'app'=>'notify',
			'act'=>'add',
			'character'=>'teacher',
			'creator'=>$uid,
			'target'=>array(),
			'ext'=>array(),
			'source'=>array(
				'notifyId'=>$id
			),
			'data' => array(),
			'type'=>$type,
		);
		if($data['source']){
			$logsData['source'] = array_merge($logsData['source'],$data['source']);
			unset($data['source']);
		}
		$logsData = array_merge($logsData,$data);
		$_Logs = new LogsModel();
		if($teachers){
			$_Logs->addLog(array_merge($logsData,array('character'=>'teacher','target'=>$teachers)));
		}
		if($students){
			$_Logs->addLog(array_merge($logsData,array('character'=>'student','target'=>$students)));
		}
    }
}