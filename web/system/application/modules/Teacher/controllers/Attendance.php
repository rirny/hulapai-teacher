<?php
/**
 * 考勤信息
 */
class AttendanceController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$where = '';
		$attended = $this->get('attended',-1,'intval');
		
		if($attended == 0){
    		$where .= " and b.attended  = 0";
    	}elseif($attended == 1){
    		$where .= " and b.attended  = 1";
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
		
		$_CourseTeacher = new Course_TeacherModel();
		$events = $_CourseTeacher->getTeacherCourseList($page,10,$this->tid,$this->school,$where);
		$this->getView()->assign('pages', $events['pages']);
		$this->getView()->assign('events', $events['data']);
	}
	
	public function doAction(){
		$event = $this->_GET['event'] ? $this->get('event',0,'intval') : $this->post('event',0,'intval');
		if(!$event) show_message('参数错误！');
		$act = $this->get('act',0,'intval');
		if(!$act || !in_array($act,array(1,2,3))) show_message('参数错误！');
		//老师是否有考勤权限
		$_CourseTeacher = new Course_TeacherModel();
		$teacherCourseInfo = $_CourseTeacher->getRow(array('event'=>$event,'teacher'=>$this->tid),'priv');
		if(!$teacherCourseInfo) exit('您不是该课程的老师！');
		if($teacherCourseInfo['priv'] & 2  == false) exit('您没有考勤的权限！');
		if($act == 2){
			$ids = $this->get('id',array());
			$datas = array();
			$_CourseStudent = new Course_StudentModel();
			if($ids){
				foreach($ids as $eventtostudent){
					$tmp = explode('_',$eventtostudent);	
					$info = $_CourseStudent->getRow(array('event'=>$tmp[0],'student'=>$tmp[1]));
					if($info){
						$datas[$tmp[0]][$info['id']] = $tmp[1];
					}
				}
			}
			if(empty($datas)) show_message('请选择学生！');
			$this->getView()->assign('datas', $datas);
			$this->getView()->assign('act', $act);
			$this->getView()->assign('event', $event);
		}elseif($act == 3){
			$act = $this->post('act',1,'intval');
			$attendance = $this->post('attendance',0,'intval');
			$datas = $this->post('datas',array());
			if(!$event || !$attendance || !$datas){
				if($act == 1) exit('参数错误！');
				show_message('参数错误！');
			}
			$data = array(
				'attend' => $attendance == 1 ? 1: 0, 
				'absence' => $attendance == 2 ? 1: 0,
				'leave' => $attendance == 3 ? 1: 0
			);
			$_CourseStudent = new Course_StudentModel();
			foreach($datas as $event=>$students){
				foreach($students as $id=>$student){
					if(!$_CourseStudent->attendance($id,$event,$data)) continue;
				}
			}	
			if($act == 1){
				exit('1');
			}else{
				show_message('考勤成功！','','attendance');
			}
		}
		
	}
}
