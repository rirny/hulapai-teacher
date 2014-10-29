<?php
/**
 * 点评统计
 */
class Total_CommentController extends Yaf_Controller_Base_Abstract {
	public function indexAction(){
		$agent = $this->get('agent',-1,'intval');
		$where = array(
			'pid'=>0,		
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Comment = new CommentModel();
    	$total = $_Comment->getAll($where,'count(1) as nums,school,agent','','school,agent');
    	$this->getView()->assign('total',$total);
    	$this->getView()->assign('agent',$agent);
	}
	
	public function dayAction(){
		$agent = $this->get('agent',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = $start_date.' 00:00:00';
		$end = $end_date.' 23:59:59';
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(
			'pid'=>0,				
			'create_time >='=>$start,
			'create_time <='=>$end,
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Comment = new CommentModel();
		$total = $_Comment->getAll($where,array("COUNT(1) AS nums,school,agent,DATE_FORMAT(create_time,'%Y-%m-%d') AS create_date"),'create_date desc','create_date,school,agent');
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
		$start = $start_date.' 00:00:00';
		$end = $end_date.' 23:59:59';
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(
			'pid'=>0,					
			'create_time >='=>$start,
			'create_time <='=>$end,
		);
		if($agent >=0) $where['agent'] = $agent;
		$_Comment = new CommentModel();
		$comments = $_Comment->getList($page,20,$where,'*','','create_time desc');
		if($comments['data']){
			$_Event = new EventModel();
			foreach($comments['data'] as &$comment){
				$comment['eventName'] = '';
				if($comment['event']){
					$eventInfo = $_Event->getRow(array('id'=>$comment['event']),'text');
					$comment['eventName'] = $eventInfo['text'];
				}
			}
		}
		$this->getView()->assign('pages', $comments['pages']);
		$this->getView()->assign('datas', $comments['data']);
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('agent',$agent);
	}
}