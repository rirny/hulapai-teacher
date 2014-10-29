<?php
/**
 * 课程统计
 */
class Total_EventController extends Yaf_Controller_Base_Abstract {
	public function indexAction(){
		$agent = $this->get('agent',-1,'intval');
		$where = array(
			'is_loop'=>0,
			'rec_type'=>''			
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Event = new EventModel();
    	$total = $_Event->getAll($where,'count(1) as nums,school,agent','','school,agent');
    	$this->getView()->assign('total',$total);
    	$this->getView()->assign('agent',$agent);
	}
	
	public function dayAction(){
		$agent = $this->get('agent',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = strtotime($start_date.' 00:00:00');
		$end = strtotime($end_date.' 23:59:59');
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(
			'is_loop'=>0,
			'rec_type'=>'',				
			'create_time >='=>$start,
			'create_time <='=>$end,
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Event = new EventModel();
		$total = $_Event->getAll($where,array("COUNT(1) AS nums,school,agent,FROM_UNIXTIME(create_time,'%Y-%m-%d') AS create_date"),'create_date desc','create_date,school,agent');
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('agent',$agent);
    	$this->getView()->assign('total',$total);
	}
	
	public function detailAction(){
		$page = $this->get('page',1,'intval');
		$agent = $this->get('agent',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = strtotime($start_date.' 00:00:00');
		$end = strtotime($end_date.' 23:59:59');
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(
			'is_loop'=>0,
			'rec_type'=>'',				
			'create_time >='=>$start,
			'create_time <='=>$end,
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Event = new EventModel();
		$events = $_Event->getList($page,20,$where,'*','','create_time desc');
		if($events['data']){
			$_Course = new CourseModel();
			foreach($events['data'] as &$event){
				$courseInfo = $_Course->getRow(array('id'=>$event['course']),'title');
				$event['courseName'] = $courseInfo['title'];
			}
		}
		$this->getView()->assign('pages', $events['pages']);
		$this->getView()->assign('datas', $events['data']);
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('agent',$agent);
	}
}