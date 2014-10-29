<?php
class Logs_LoginModel extends BaseModel{
	public $table = 't_logs_login';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getLoginDay($start='',$end=''){
    	
    	$sql = "SELECT COUNT(1) AS nums,COUNT(DISTINCT `user`) AS disnums,agent,LEFT(create_time,10) AS login_date FROM t_logs_login where 1";
    	if($start) $sql .= " and create_time >='$start'";
    	if($end) $sql .= " and create_time <='$end'";
    	$sql .= " GROUP BY login_date,agent ORDER BY login_date DESC";
    	$sqlQuery = "SELECT SUM(a.nums) AS nums,SUM(a.disnums) AS disnums,a.login_date,GROUP_CONCAT(a.nums) AS daynums,GROUP_CONCAT(a.disnums) AS daydisnums,GROUP_CONCAT(a.agent) AS daytype from ($sql) as a group by a.login_date";
    	$datas = $this->db->query($sqlQuery)->result_array();
    	if($datas){
    		foreach($datas as &$data){
    			$data['daytype'] = explode(',',$data['daytype']);
    			$data['daynums'] = explode(',',$data['daynums']);
    			$data['daydisnums'] = explode(',',$data['daydisnums']);
    			foreach($data['daytype'] as $key=>$daytype){
    				$data['detail'][$daytype] = array($data['daynums'][$key],$data['daydisnums'][$key]);
    			}
    		}
    	}
    	return $datas;
    }
}