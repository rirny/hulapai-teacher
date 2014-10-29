<?php
/**
 * 呼啦圈统计
 */
class Total_BlogController extends Yaf_Controller_Base_Abstract {
	public function indexAction(){
		$from = $this->get('from',-1,'intval');
		$where = array();
		if($from >=0) $where['from'] = $from;
		$_Blog = new BlogModel();
    	$total = $_Blog->getAll($where,'count(1) as nums,from','','from');
    	$this->getView()->assign('total',$total);
    	$this->getView()->assign('from',$from);
	}
	
	public function dayAction(){
		$from = $this->get('from',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = strtotime($start_date.' 00:00:00');
		$end = strtotime($end_date.' 23:59:59');
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(			
			'publish_time >='=>$start,
			'publish_time <='=>$end,
		);
		if($from >=0) $where['from'] = $from;
		$_Blog = new BlogModel();
		$total = $_Blog->getAll($where,array("COUNT(1) AS nums,`from`,FROM_UNIXTIME(publish_time,'%Y-%m-%d') AS create_date"),'create_date desc','create_date,from');
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('from',$from);
    	$this->getView()->assign('total',$total);
	}
	
	public function detailAction(){
		$page = $this->get('page',1,'intval');
		$from = $this->get('from',-1,'intval');
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$start_date || $start_date = date('Y-m-d');
		$end_date || $end_date = date('Y-m-d');
		$start = strtotime($start_date.' 00:00:00');
		$end = strtotime($end_date.' 23:59:59');
		if($start >= $end) show_message('结束日期必须大于开始日期！');
		$where = array(					
			'publish_time >='=>$start,
			'publish_time <='=>$end,
		);
		if($from >=0) $where['from'] = $from;
		$_Blog = new BlogModel();
		$blogs = $_Blog->getList($page,20,$where,'*','','publish_time desc');
		$this->getView()->assign('pages', $blogs['pages']);
		$this->getView()->assign('datas', $blogs['data']);
    	$this->getView()->assign('start_date',$start_date);
    	$this->getView()->assign('end_date',$end_date);
    	$this->getView()->assign('from',$from);
	}
}