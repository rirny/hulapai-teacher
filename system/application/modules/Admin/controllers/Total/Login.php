<?php
/**
 * 登录统计
 */
class Total_LoginController extends Yaf_Controller_Base_Abstract {
	public function indexAction(){
		
	}
	
	public function dayAction(){
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = $start_date.' 00:00:00';
		$end = $end_date.' 23:59:59';
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$_Logs_Login = new Logs_LoginModel();
		$total = $_Logs_Login->getLoginDay($start,$end);
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
		$this->getView()->assign('total',$total);
	}
	
	
	public function detailAction(){
		$page = $this->get('page',1,'intval');
		$agent = $this->get('agent',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = $start_date.' 00:00:00';
		$end = $end_date.' 23:59:59';
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(
			'create_time >='=>$start,
			'create_time <='=>$end,
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Logs_Login = new Logs_LoginModel();
		$students = $_Logs_Login->getList($page,20,$where,'*','','create_time desc');
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('datas', $students['data']);
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('agent',$agent);
	}
}