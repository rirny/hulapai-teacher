<?php
/**
 * 考勤信息
 */
class AttendanceController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$where = '';
		$attendance = $this->get('attendance',0,'intval');
		if($attendance){
			if($attendance == -1){
	    		$where .= " and a.attended  = 0";
	    	}elseif($attendance == 1){
	    		$where .= " and a.attended  = 1 and a.attend  = 1";
	    	}elseif($attendance == 2){
	    		$where .= " and a.attended  = 1 and a.absence  = 1";
	    	}elseif($attendance == 3){
	    		$where .= " and a.attended  = 1 and a.leave  = 1";
	    	}
		}		
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where .= " and b.end_date >= '$start_date 00:00:00'";
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where .= " and b.start_date <= '$end_date 23:59:59'";
		}
		$remark = $this->get('remark','','trim');
		if($remark && $remark != "课程名称") $where .= " and a.remark like '%$remark%'";
		
		$_CourseStudent = new Course_StudentModel();
		$events = $_CourseStudent->getStudentCourseList($page,10,$this->sid,$this->school,$where);
		$this->getView()->assign('pages', $events['pages']);
		$this->getView()->assign('events', $events['data']);
	}
}
