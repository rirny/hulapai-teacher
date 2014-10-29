<?php
/**
 * 点评
 */
class CommentController extends Yaf_Controller_Base_Abstract {
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
			$commented = $this->get('commented',-1,'intval');
			if($commented == 0){
	    		$where['commented'] = 0;
	    	}elseif($commented == 1){
	    		$where['commented'] = 1;
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
				$commented = $this->get('commented',-1,'intval');
			
				if($commented == 0){
		    		$where['commented'] = 0;
		    	}elseif($commented == 1){
		    		$where['commented'] = 1;
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
		$event = $this->_GET['event'] ? $this->get('event',0,'intval') : $this->post('event',0,'intval');
		if(!$event) show_message('参数错误！');
		$act = $this->get('act',0,'intval');
		if(!$act || !in_array($act,array(1,2,3))) show_message('参数错误！');
		if($act == 1 || $act == 2){
			$isStudentInfo = $this->get('isStudentInfo',0,'intval');
			$student = $this->get('student',0,'intval');
			$ids = $this->post('id',array());
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
			if($event && $student){
				$info = $_CourseStudent->getRow(array('event'=>$event,'student'=>$student));
				if($info){
					$datas[$event][$info['id']] = $student;
				}
			}
			if(empty($datas)) show_message('请选择需要点评的学生！');
			$this->getView()->assign('datas', $datas);
			$this->getView()->assign('act', $act);
			$this->getView()->assign('event', $event);
			$this->getView()->assign('isStudentInfo', $isStudentInfo);
			$timestamp = time();
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
			
		}elseif($act == 3){
			$act = $this->post('act',0,'intval');
			$isStudentInfo = $this->post('isStudentInfo',0,'intval');
			if(!$act || !in_array($act,array(1,2))) show_message('参数错误！');
			$content = $this->post('content','','trim');
			$datas = $this->post('datas',array());
			$attach = $this->post('attach',array());
			if(!$content || !$datas) show_message('参数错误！');
			$data = array(
				'creator'=>$this->uid,
				'teacher'=>$this->uid,
				'content'=>$content,
				'school'=>$this->school,
				'attach'=>$attach ? implode(',',$attach): '',
				'character'=>'school',
				'create_time'=>date('Y-m-d H:i:s')
			);
			$_Comment = new CommentModel();
			$_Push = new PushModel();
			$_UserStudent = new User_StudentModel();
			$_Event = new EventModel();
			foreach($datas as $event=>$students){
				$data['event']=$event;
				foreach($students as $id=>$student){
					$data['student']=$student;
					$commentId = $_Comment->addCourseStudentComment($id,$event,$data);
					if(!$commentId) continue;
					$parents = $_UserStudent->parents($student);
					$_Push->addPush(array(
						'app' => 'comment', 
						'act' => 'add', 
						'from'=> $this->uid,
						'to' => $parents, 
						'student'=>$student,
						'ext'=> $_Comment->getCommentInfo($commentId),
						'type' => '2',
						'message' => '您有新的点评！', 
					));
				}
			}	
			if($act == 1){
				if($isStudentInfo){
					//echo "<script>var d = window.top.art.dialog({id:'info'}).data.iframe.location.reload();</script>";
				}else{
					echo "<script>var d = window.top.art.dialog({id:'list'}).data.iframe.location.reload();</script>";
				}
				show_message('点评成功！','','comment');
			}else{
				show_message('点评成功！',url('school','comment','index','act=list&event='.$event));
			}
		}
	}
	/**
	 * 点评回复
	 */
	public function replyAction(){
		$id = $this->_GET['id'] ? $this->get('id',0,'intval'):$this->post('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Comment = new CommentModel();
		$pInfo = $_Comment->getRow(array('id'=>$id));
		if(!$pInfo) show_message('点评不存在！');
		if($this->_POST){
			$act = $this->post('act','','trim');
			$content = $this->post('content','','trim');
			$attach = $this->post('attach',array());
			if(!$content) show_message('参数错误！');
			$data = array(
				'creator'=>$this->uid,
				'teacher'=>$pInfo['teacher'],
				'pid'=>$id,
				'content'=>$content,
				'student'=>$pInfo['student'],
				'event'=>$pInfo['event'],
				'school'=>$pInfo['school'],
				'attach'=>$attach ? implode(',',$attach): '',
				'reply'=>1,
				'character'=>'school',
				'create_time'=>date('Y-m-d H:i:s')
			);
			$commentId = $_Comment->insertData($data);
			if(!$commentId) show_message('回复失败！');
			$_Push = new PushModel();
			$_Push->addPush(array(
				'app' => 'comment', 
				'act' => 'reply', 
				'from'=> $this->uid,
				'to' => $pInfo['teacher'], 
				'student'=>0,
				'ext'=> $_Comment->getCommentInfo($commentId),
				'type' => '2',
				'message' => '您有新的点评回复！', 
			));
			$_UserStudent = new User_StudentModel();
			$parents = $_UserStudent->parents($pInfo['student']);
			$_Push->addPush(array(
				'app' => 'comment', 
				'act' => 'reply', 
				'from'=> $this->uid,
				'to' => $parents, 
				'student'=>$pInfo['student'],
				'ext'=> $_Comment->getCommentInfo($commentId),
				'type' => '2',
				'message' => '您有新的点评回复！', 
			));
			if($act == "show"){
				exit('1');
			}else{
				echo "<script>var d = window.top.art.dialog({id:'showcomment'}).data.iframe.location.reload();</script>";
				show_message('回复成功！','','reply');
			}
			
		}
		$this->getView()->assign('commentInfo', $pInfo);
		$timestamp = time();
		$this->getView()->assign('timestamp', $timestamp);
		$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
	}
	
	public function infoAction(){
		$page = $this->get('page',1,'intval');
		$student = $this->get('student',0,'intval');
		$event = $this->get('event',0,'intval');
		if(!$student || !$event) show_message('参数错误！');
		$_CourseStudent = new Course_StudentModel();
		$info = $_CourseStudent->getRow(array('event'=>$event,'student'=>$student));
		if(!$info) show_message('课程不存在！');
		$_Comment = new CommentModel();
		$teacherComment = $_Comment->getRow(array('event'=>$event,'student'=>$student,"character in ('teacher','school')"=>null),'*','id asc');
		$datas = $_Comment->getList($page,20,array('event'=>$event,'student'=>$student));
		if($datas['data']){
			$_Attach = new AttachModel();
			foreach($datas['data'] as &$data){
				$data['attachInfos'] = array();
				if($data['attach']){
					$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
				}
			}
		}
		$this->getView()->assign('teacherComment', $teacherComment);
		$this->getView()->assign('data', $datas['data']);
		$this->getView()->assign('pages', $datas['pages']);
	}
	
	public function listAction(){
		$page = $this->get('page',1,'intval');
		$sorts = $this->get('sorts','a.create_time desc,a.id desc','trim');
		$event = $this->get('event',0,'intval');
		$where = "";
		if($event){
			$where .= " and a.event = $event ";
		}else{
			$where .= " and a.event > 0 ";
		}
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
			$where .= " and a.student in ($studentIds) ";
		}else{
			$where .= " and a.student > 0 ";
		}
		$teacher = $this->get('teacher',0,'intval');
		if($teacher){
			$where .= " and a.teacher = $teacher ";
		}else{
			$where .= " and a.teacher > 0 ";
		}
		$content = $this->get('content','','trim');
		$content = $content != "关键字" ? $content:'';
		if($content){
			$where .= " and a.content like '%$content%' ";
		}
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where .= " and a.create_time >= '$start_date 00:00:00' ";
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where .= " and a.create_time <= '$end_date 23:59:59' ";
		}
		$_Comment = new CommentModel();
		$datas = $_Comment->getCommentList($page,20,$this->school,$where,$sorts);
		if($datas['data']){
			foreach($datas['data'] as &$comment){
				$comment['replies'] = $_Comment->getCount(array('pid'=>$comment['id']));
			}
		}
		$this->getView()->assign('comments', $datas['data']);
		$this->getView()->assign('pages', $datas['pages']);
		//获取机构所有老师
		$_SchoolTeacher = new School_TeacherModel();
		$teachers = $_SchoolTeacher->getSchoolTeacher($this->school,true,1);
		$this->getView()->assign('teachers', $teachers);
		$this->getView()->assign('sorts', $sorts);
	}
	
	public function showAction(){
		$page = $this->get('page',1,'intval');
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		//获取课程的相关点评
		$_Comment = new CommentModel();
		$_Attach = new AttachModel();
		$teacherComment = $_Comment->getRow(array('id'=>$id));
		if($teacherComment['attach']){
			$teacherComment['attachInfos'] = $_Attach->getAttachs($teacherComment['attach']);
		}
		$datas = $teacherComment ? $_Comment->getList($page,3,array('pid'=>$id),'*','','id asc'):array();
		if($datas['data']){
			foreach($datas['data'] as &$data){
				$data['attachInfos'] = array();
				if($data['attach']){
					$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
				}
			}
		}
		$this->getView()->assign('teacherComment', $teacherComment);
		$this->getView()->assign('comments', $datas['data']);
		$this->getView()->assign('pages', $datas['pages']);
	}
}
