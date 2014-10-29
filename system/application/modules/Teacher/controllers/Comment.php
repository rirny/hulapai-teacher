<?php
/**
 * 点评信息
 */
class CommentController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$where = '';
		$commented = $this->get('commented',-1,'intval');
		
		if($commented == 0){
    		$where .= " and b.commented = 0";
    	}elseif($commented == 1){
    		$where .= " and b.commented = 1";
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
	
	public function infoAction(){
		$page = $this->get('page',1,'intval');
		$id = $this->get('id',0,'intval');
		$student = $this->get('student',0,'intval');
		if(!$id || !$student) show_message('参数错误！');
		$_CourseStudent = new Course_StudentModel();
		$eventInfo = $_CourseStudent->getStudentCourse($student,$this->school,$id);
		if(!$eventInfo) show_message('课程不存在！');
		$this->getView()->assign('event', $eventInfo);
		//获取课程的相关点评
		$_Comment = new CommentModel();
		$_Attach = new AttachModel();
		$teacherComment = $_Comment->getRow(array('event'=>$id,'student'=>$student,"character in ('teacher','school')"=>null),'*','id asc');
		if($teacherComment['attach']){
			$teacherComment['attachInfos'] = $_Attach->getAttachs($teacherComment['attach']);
		}
		$datas = $teacherComment ? $_Comment->getList($page,5,array('event'=>$id,'student'=>$student,'id >'=>$teacherComment['id']),'*','','id asc'):array();
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
	public function doAction(){
		$event = $this->_GET['event'] ? $this->get('event',0,'intval') : $this->post('event',0,'intval');
		if(!$event) show_message('参数错误！');
		$act = $this->get('act',0,'intval');
		if(!$act || !in_array($act,array(1,2,3))) show_message('参数错误！');
		//老师是否有点评权限
		$_CourseTeacher = new Course_TeacherModel();
		$teacherCourseInfo = $_CourseTeacher->getRow(array('event'=>$event,'teacher'=>$this->tid),'priv');
		if(!$teacherCourseInfo) show_message('您不是该课程的老师！');
		if($teacherCourseInfo['priv'] & 4 == false) show_message('您没有点评的权限！');
		if($act == 1 || $act == 2){
			$student = $this->get('student',0,'intval');
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
			if($event && $student){
				$info = $_CourseStudent->getRow(array('event'=>$event,'student'=>$student));
				if($info){
					$datas[$event][$info['id']] = $student;
				}
			}
			if($act == 1 && empty($datas)) show_message('参数错误！');
			if($act == 2 && empty($datas)) show_message('请选择学生！');
			$this->getView()->assign('datas', $datas);
			$this->getView()->assign('act', $act);
			$this->getView()->assign('event', $event);
			$timestamp = time();
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
			
		}elseif($act == 3){
			$act = $this->post('act',0,'intval');
			if(!$act || !in_array($act,array(1,2))) show_message('参数错误！');
			$content = $this->post('content','','trim');
			$datas = $this->post('datas',array());
			$attach = $this->post('attach',array());
			if(!$content || !$datas) show_message('参数错误！');
			$data = array(
				'creator'=>$this->uid,
				'teacher'=>$this->tid,
				'content'=>$content,
				'school'=>$this->school,
				'attach'=>$attach ? implode(',',$attach): '',
				'character'=>'teacher',
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
			show_message('点评成功！','','comment');
		}
	}
	
	public function replyAction(){
		$pid = $this->post('pid',0,'intval');
		$content = $this->post('content','','trim');
		if(!$pid) exit('参数错误！');
		if(!$content) exit('回复内容不能为空！');
		$attach = array();
		$_Comment = new CommentModel();
		$pInfo = $_Comment->getRow(array('id'=>$pid));
		if(!$pInfo) exit('回复的内容不存在！');
		$data = array(
			'creator'=>$this->uid,
			'teacher'=>$this->tid,
			'student'=>$pInfo['student'],
			'event'=>$pInfo['event'],
			'pid'=>$pid,
			'reply'=>1,
			'content'=>$content,
			'school'=>$this->school,
			'attach'=>$attach ? implode(',',$attach): '',
			'character'=>'teacher',
			'create_time'=>date('Y-m-d H:i:s')
		);
		$commentId = $_Comment->insertData($data);
		if(!$commentId) exit('回复失败！');
		$_UserStudent = new User_StudentModel();
		$parents = $_UserStudent->parents($pInfo['student']);
		$_Push = new PushModel();
		$_Push->addPush(array(
			'app' => 'comment', 
			'act' => 'reply', 
			'from'=> $this->uid,
			'to' => $parents, 
			'student'=>$pInfo['student'],
			'character'=>'student',
			'ext'=> $_Comment->getCommentInfo($commentId),
			'type' => '2',
			'message' => '您有新的点评回复！', 
		));
		
		exit('1');		
	}
}
