<?php
/**
 * 考勤
 */
class AttendanceController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$act = $this->get('act','index','trim');
		$event = $this->get('event',0,'intval');
		if($act == 'list'){
			if(!$event) show_message('参数错误！');
			$_Event = new EventModel();
			$eventInfo = $_Event->getRow(array('id'=>$event));
			if(!$eventInfo) show_message('课程不存在！');
			$where = array('event'=>$event);
			$studentName = $this->get('studentName','','trim');
			$studentName = $studentName != "学生名" ? $studentName:'';
			if($studentName){
				$_SchoolStudent = new School_StudentModel();
				$students = $_SchoolStudent->getSchoolStudentByStudentName($this->school,$studentName);
				if(!$students) show_message('学生不存在！');
				$studentIds = array();
				foreach($students as $student){
					$studentIds[] = $student['student'];
				}
				$studentIds = implode(',',$studentIds);
				$where["student in ($studentIds)"] = NULL;
			}
			$attendance = $this->get('attendance',0,'intval');
			if($attendance){
				if($attendance == -1){
		    		$where['attended'] = 0;
		    	}elseif($attendance == 1){
		    		$where['attended'] = 1;
		    		$where['attend'] = 1;
		    	}elseif($attendance == 2){
		    		$where['attended'] = 1;
		    		$where['absence'] = 1;
		    	}elseif($attendance == 3){
		    		$where['attended'] = 1;
		    		$where['leave'] = 1;
		    	}
			}
			$_CourseStudent = new Course_StudentModel();
			$datas = $_CourseStudent->getList($page,20,$where,'*','','event desc,id desc');
			$this->getView()->assign('pages', $datas['pages']);
			$this->getView()->assign('data', $datas['data']);
		}else{
			//根据条件搜索课程id
			$where = array('school'=>$this->school,'is_loop'=>0,'rec_type'=>'','status'=>0,'source'=>0,'end_date <'=>date('Y-m-d',strtotime('+1 day')));
			if($event){
				$where['id'] = $event;
			}else{
				$attended = $this->get('attended',-1,'intval');
				if($attended == 0){
		    		$where['attended'] = 0;
		    	}elseif($attended == 1){
		    		$where['attended'] = 1;
		    	}
				$course = $this->get('course',0,'intval');
				if($course) $where['course'] = $course;
				$text = $this->get('text','','trim');
				if($text && $text != "课程名称") $where['text like'] = "%$text%";
				$start_date = $this->get('start_date','','isDate');
				if($start_date){
					$where['end_date >='] = $start_date.' 00:00:00';
				}
				$end_date = $this->get('end_date','','isDate');
				if($end_date){
					$where['start_date <='] = $end_date.' 23:59:59';
				}
				$teacher = $this->get('teacher',0,'intval');
				if($teacher){
					$_CourseTeacher = new Course_TeacherModel();
					$eventIds = $_CourseTeacher->getAll(array('teacher'=>$teacher),'event');
					$eventIdArr = array();
					if($eventIds){
						foreach($eventIds as $eventId){
							$eventIdArr[] = $eventId['event'];
						}
					}
					if(!$eventIdArr) $where["id"] = 0;
					else $where["id in (".implode(',',$eventIdArr).")"] = NULL;
				}
			}
			$_Event = new EventModel();
			$_Course = new CourseModel();
			$_CourseTeacher = new Course_TeacherModel();
			$sorts = $this->get('sorts','start_date desc,id desc','trim');
			$events = $_Event->getList($page,20,$where,'*','',$sorts);
			if($events['data']){
				foreach($events['data'] as &$event){
					$courseInfo = $_Course->getRow(array('id'=>$event['course']),'title');
					$event['courseName'] = $courseInfo['title'];
					//获取老师
					$event['teacher'] = array();
					$teachers = $_CourseTeacher->getAll(array('event'=>$event['id']),'teacher');
					if($teachers){
						foreach($teachers as $teacher){
							$event['teacher'][] = $teacher['teacher'];
						}
					}
				}
			}
			$this->getView()->assign('pages', $events['pages']);
			$this->getView()->assign('events', $events['data']);
			//获取机构所有老师
			$_SchoolTeacher = new School_TeacherModel();
			$teachers = $_SchoolTeacher->getSchoolTeacher($this->school,true,1);
			$this->getView()->assign('teachers', $teachers);
			//获取机构课程分类
			$_Course = new CourseModel();
			$schoolCourses = $_Course->getSchoolCourseType($this->school);
			$this->getView()->assign('schoolCourses', $schoolCourses);
			$this->getView()->assign('sorts', $sorts);
		}
	}
	
	public function doAction(){
		$event = $this->post('event',0,'intval');
		if(!$event) show_message('参数错误！');
		$attendance = $this->post('attendance',array());
		$isStudentInfo = $this->post('isStudentInfo',0,'intval');
		if(!$attendance)  show_message('参数错误！');
		$_CourseStudent = new Course_StudentModel();
		foreach($attendance as $id=>$v){
			$data = array(
				'attend' => 0, 
				'leave' => 0,
				'absence' => 0
			);
			if(!in_array($v,array('attend','leave','absence'))) continue;
			$info = $_CourseStudent->getRow(array('id'=>$id));
			if(!$info) continue;
			$data[$v] = 1;
			if(!$_CourseStudent->attendance($id,$info['event'],$data)) continue;
		}
		if($isStudentInfo){
			//echo "<script>var d = window.top.art.dialog({id:'info'}).data.iframe.location.reload();</script>";
			show_message('考勤成功！','','attendance');
		}else{
			show_message('处理成功！',url('school','attendance','index','act=list&event='.$event));
		}
	}
}
