<?php
/**
 * 通知
 */
class NotifyController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		$detail = $this->get('detail',0,'intval');
		if(!$detail){
			$page = $this->get('page',1,'intval');
			$content = $this->get('content','','trim');
			$content = $content != "内容" ? $content:'';
			$where = array('type'=>2,'school'=>$this->school,'vote'=>0);
			if($content){
				$where["content like '%$content%'"]= NULL;
			}
			$_Notify = new NotifyModel();
			$notifies = $_Notify->getList($page,20,$where,'*','','id desc');
			if($notifies['data']){
				$_Vote = new VoteModel();
				foreach($notifies['data'] as &$notify){
					if($notify['student']){
						$notify['student'] = json_decode($notify['student']);
						$studentName = '';
						foreach($notify['student'] as $student){
							$studentName .= studentName($student).' ';
						}
						$notify['student'] = $studentName;
					}
					if($notify['teacher']){
						$notify['teacher'] = json_decode($notify['teacher']);
						$teacherName = '';
						foreach($notify['teacher'] as $teacher){
							$teacherName .= teacherName($teacher).' ';
						}
						$notify['teacher'] = $teacherName;
					}
				}
			}
			$this->getView()->assign('notifies', $notifies['data']);
			$this->getView()->assign('pages', $notifies['pages']);
		}else{
			$notifyId = $this->get('notifyId',0,'intval');
			if(!$notifyId) show_message('参数错误！');
			$page = $this->get('page',1,'intval');
			$_Message = new MessageModel();
			$datas = $_Message->getList($page,10,array('pid'=>$notifyId),'*','','id desc');
			$this->getView()->assign('messages', $datas['data']);
			$this->getView()->assign('pages', $datas['pages']);
		}
	}
	
	/**
	 * 发通知
	 */
	public function sendAction() {
		if($this->_POST){
			$type = $this->post('type',0,'intval');
			if(!in_array($type,array(1,2))) show_message('参数错误！');
			$content = $this->post('content','','trim');
			$vote = $this->post('vote',0,'intval');
			if($type == 1){
				if(!$content) show_message('内容不能为空！');
				$vote = 0;
			}
			if($type == 2){
				if(!$vote) show_message('投票不能为空！');
				$_Vote = new VoteModel();
				if(!$_Vote->getRow(array('school'=>$this->school,'id'=>$vote))) show_message('问卷不存在！');
				$content = '';
			}
			$students = $this->post('student_op', array());
			$teachers = $this->post('teacher_op', array());
			if(!$students && !$teachers) show_message('发送对象错误！');
			//验证学生
			if($students){
				$_SchoolStudent = new School_StudentModel();
				$_Student = new StudentModel();
	            foreach($students as $student)
	            {	
	                
	                if(!$_SchoolStudent->getRow(array('school' => $this->school, 'student' => $student))) show_message('不是机构学生!@'.$student);
	                if(!$_Student->getRow(array('id' => $student))) show_message('没有此学生!@'.$student);
	            }
			}
			//验证老师
			if($teachers){
				$_SchoolTeacher = new School_TeacherModel();
				$_Teacher = new TeacherModel();
	            foreach($teachers as $teacher=>$priv)
	            {	
	                if(!$_SchoolTeacher->getRow(array('school' => $this->school, 'teacher' => $teacher))) show_message('不是机构老师!@'.$teacher);
	                if(!$_Teacher->getRow(array('user' => $teacher))) show_message('没有此老师!@'.$teacher);
	            }
			}
			$data = array(
				'creator'=>$this->uid,
				'type'=>2,
				'event'=>0,
				'create_time'=>time(),
				'student'=>json_encode(array_values($students)),
				'teacher'=>json_encode(array_keys($teachers)),
				'content'=>$content,
				'attachs'=>json_encode(array()),
				'vote'=>$vote,
				'school'=>$this->school,
				'receipt'=>0,
			);
			$_Notify = new NotifyModel();
			$id = $_Notify->insertData($data);
			if(!$id) show_message('通知发送失败！');
			// $_Notify->push($id,$this->uid,array_keys($teachers),array_values($students),$type == 2 ? 2 : 0);
			$_Notify->push($id,$this->uid,array_keys($teachers),array_values($students), 2);
			show_message('通知发送成功！',url('school',$vote ? 'vote':'notify'));
		}else{
			//获取投票
			$vote = $this->get('vote',0,'intval');
			$_Vote = new VoteModel();
			$votes = $_Vote->getVote($this->school);
			if($vote){
				$info = $_Vote->getRow(array('id'=>$vote));
				if(!$info) show_message('问卷不存在！');
				if($info['end_time'] < time()) show_message('问卷投票时间已结束！');
			}
			$this->getView()->assign('school', $this->school);
			$this->getView()->assign('votes', $votes);
			$this->getView()->assign('vote', $vote);
		}	
	}
}
