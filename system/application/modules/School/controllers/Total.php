<?php
/**
 * 统计
 */
class TotalController extends Yaf_Controller_Base_Abstract {
	private function getSchoolCourse(){
		//获取机构课程分类
		$_Course = new CourseModel();
		$schoolCourses = $_Course->getSchoolCourseType($this->school);
		if(!$schoolCourses) show_message('请先设置授课内容！',url('school','course','index'));
		$this->getView()->assign('schoolCourses', $schoolCourses);
	}
	
	public function eventAction() {
		$page = $this->get('page',1,'intval');
		$pageSize = 20;
		//根据条件搜索课程id
		$where = array('school'=>$this->school,'is_loop'=>0,'rec_type !='=>'none','status'=>0,'source'=>0,'end_date <'=>date('Y-m-d'));
		$course = $this->get('course',0,'intval');
		if($course) $where['course'] = $course;
		$text = $this->get('text','','trim');
		if($text && $text != "课程名称") $where['text like'] = "%$text%";
		$teacher = $this->get('teacher',0,'intval');
		if($teacher) $where['teacher'] = $teacher;
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where['end_date >='] = $start_date.' 00:00:00';
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where['start_date <='] = $end_date.' 23:59:59';
		}
		$_Event = new EventModel();
		$_Course = new CourseModel();
		$_CourseStudent = new Course_StudentModel();
		$events = $_Event->getList($page,$pageSize,$where,'*','','id desc');
		if($events['data']){
			foreach($events['data'] as &$event){
				$courseInfo = $_Course->getRow(array('id'=>$event['course']),'title');
				$event['courseName'] = $courseInfo['title'];
				//学生数
				$event['studentNum'] = $_CourseStudent->getCount(array('event'=>$event['id']));
				
			}
		}
		$this->getView()->assign('events', $events['data']);
		$this->getView()->assign('pages', $events['pages']);
		//获取机构所有老师学生
		$_SchoolTeacher = new School_TeacherModel();
		$teachers = $_SchoolTeacher->getSchoolTeacher($this->school,true,1);
		$this->getView()->assign('teachers', $teachers);
		//获取机构课程分类
		$_Course = new CourseModel();
		$schoolCourses = $_Course->getSchoolCourseType($this->school);
		$this->getView()->assign('schoolCourses', $schoolCourses);
	}
	
	
	public function teacherAction() {
		$this->getSchoolCourse();
		$page = $this->get('page',1,'intval');
		$export = $this->get('export','','trim');
		$pageSize = $export ? 9999: 20;
		$teacherName = $this->get('teacherName','','trim');
		$teacherName = $teacherName != "老师名" ? $teacherName:'';
		$course = $this->get('course',0,'intval');
		$search = $this->get('search','搜索','trim');
		$courseName = '';
		if($course){
			$_Course = new CourseModel();
			$courseInfo = $_Course->getRow(array('id'=>$course),'title');
			$courseName = $courseInfo['title'];
		}
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$max_end_date = date('Y-m-d',strtotime('-1 day'));
		$start_date = $start_date && $start_date >= $max_end_date ? $max_end_date:$start_date;
		$end_date = !$end_date || $end_date >= $max_end_date ? $max_end_date:$end_date;
		$sorts = $this->get('sorts','teacher asc','trim');
		$_SchoolTeacher = new School_TeacherModel();
		$teachers = $_SchoolTeacher->getTeacherTotal($page,$pageSize,$this->school,$teacherName,$course,$start_date,$end_date,$sorts);
		if($teachers['data']){
			$_Comment = new CommentModel();
			if($export){
				$filename = '老师统计';
				$headerArr = array('老师','授课内容','课次数','课时数','点评数','回复数');
				$data = array();
				foreach($teachers['data'] as $teacher){
					$comments = 0;
					$replies = 0;
					if($teacher['event_ids']){
						//获取老师点评id
						$comments = $_Comment->getAll(array('event in ('.$teacher['event_ids'].')'=>NULL,'creator'=>$teacher['teacher'],'character'=>'teacher','pid'=>0,'status'=>0),'id');
						$commentNums = 0;
						$commentReplyNums = 0;
						if($comments){
							$commentIdArr = array();
							foreach($comments as $comment){
								$commentIdArr[] = $comment['id'];
							}
							$commentNums = count($commentIdArr);
							$commentIdStr = implode(',',$commentIdArr);
							$commentReplyNums = $_Comment->getCount(array("pid in ($commentIdStr)"=>NULL,'character !='=>'teacher'));
						}
						$comments = $commentNums;
						$replies = $commentReplyNums;
					}
					$data[] = array(
						teacherName($teacher['teacher']),
						$courseName ? $courseName : '--',
						intval($teacher['event_nums']),
						intval($teacher['classes']),
						$comments,
						$replies
					);
				}
				excelExport($filename,$headerArr,$data);
			}else{
				foreach($teachers['data'] as &$teacher){
					$teacher['course'] = $course;
					$teacher['courseName'] = $courseName;
					$teacher['comments'] = 0;
					$teacher['replies'] = 0;
					if($teacher['event_ids']){
						//获取老师点评id
						$comments = $_Comment->getAll(array('event in ('.$teacher['event_ids'].')'=>NULL,'creator'=>$teacher['teacher'],'character'=>'teacher','pid'=>0,'status'=>0),'id');
						$commentNums = 0;
						$commentReplyNums = 0;
						if($comments){
							$commentIdArr = array();
							foreach($comments as $comment){
								$commentIdArr[] = $comment['id'];
							}
							$commentNums = count($commentIdArr);
							$commentIdStr = implode(',',$commentIdArr);
							$commentReplyNums = $_Comment->getCount(array("pid in ($commentIdStr)"=>NULL,'character !='=>'teacher'));
						}
						$teacher['comments'] = $commentNums;
						$teacher['replies'] = $commentReplyNums;
					}
				}
			}
			
		}
		$this->getView()->assign('pages', $teachers['pages']);
		$this->getView()->assign('teachers', $teachers['data']);
		$this->getView()->assign('sorts', $sorts);
	}
	
	public function studentAction() {
		$this->getSchoolCourse();
		$page = $this->get('page',1,'intval');
		$export = $this->get('export','','trim');
		$pageSize = $export ? 9999: 20;
		$studentName = $this->get('studentName','','trim');
		$studentName = $studentName != "学生名" ? $studentName:'';
		$course = $this->get('course',0,'intval');
		$courseName = '';
		if($course){
			$_Course = new CourseModel();
			$courseInfo = $_Course->getRow(array('id'=>$course),'title');
			$courseName = $courseInfo['title'];
		}
		$start_date = $this->get('start_date','','isDate');
		$end_date = $this->get('end_date','','isDate');
		$max_end_date = date('Y-m-d',strtotime('-1 day'));
		$start_date = $start_date && $start_date >= $max_end_date ? $max_end_date:$start_date;
		$end_date = !$end_date || $end_date >= $max_end_date ? $max_end_date:$end_date;
		$sorts = $this->get('sorts','student asc','trim');
		$_SchoolStudent = new School_StudentModel();
		$students = $_SchoolStudent->getStudentTotal($page,$pageSize,$this->school,$studentName,$course,$start_date,$end_date,$sorts);
		if($students['data']){
			if($export){
				$filename = '学生统计';
				$headerArr = array('学生','授课内容','课次数','课时数','出勤数','缺勤数','请假数','未考勤数');
				$data = array();
				foreach($students['data'] as $student){
					$data[] = array(
						studentName($student['student']),
						$courseName ? $courseName : '--',
						intval($student['event_nums']),
						intval($student['classes']),
						intval($student['attend']),
						intval($student['absence']),
						intval($student['leave']),
						intval($student['event_nums']) - intval($student['attend']) - intval($student['absence']) - intval($student['leave']),
					);
				}
				excelExport($filename,$headerArr,$data);
			}else{
				foreach($students['data'] as &$student){
					$student['course'] = $course;
					$student['courseName'] = $courseName;
				}
			}
			
		}
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('students', $students['data']);
		$this->getView()->assign('sorts', $sorts);
	}
	
	
	public function studentFeeAction() {
		$page = $this->get('page',1,'intval');
		$export = $this->get('export','','trim');
		$pageSize = $export ? 9999: 20;
		$studentName = $this->get('studentName','','trim');
		$studentName = $studentName != "学生名" ? $studentName:'';
		$sorts = $this->get('sorts','student asc','trim');
		$money_start = $this->get('money_start','0','intval');
		$money_end = $this->get('money_end','0','intval');
		$_SchoolStudent = new School_StudentModel();
		$students = $_SchoolStudent->getStudentFeeTotal($page,$pageSize,$this->school,$studentName,$money_start,$money_end,$sorts);
		if($students['data']){
			if($export){
				$filename = '学费统计';
				$headerArr = array('学生','已缴费金额','已消费金额','剩余金额');
				$data = array();
				foreach($students['data'] as $student){
					$data[] = array(
						studentName($student['student']),
						$student['money'],
						$student['used'],
						$student['balance']
					);
				}
				excelExport($filename,$headerArr,$data);
			}
			
		}
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('students', $students['data']);
		$this->getView()->assign('sorts', $sorts);
	}
}
