<?php
/**
 * 机构老师
 */
class TeacherController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$teacherName = $this->get('teacherName','','trim');
		$teacherName = $teacherName != "老师名" ? $teacherName:'';
		$group = $this->get('group',0,'intval');
		$status = $this->get('status',-1,'intval');
		$_SchoolTeacher = new School_TeacherModel();
		
		$teachers = $_SchoolTeacher->getSchoolTeacherList($page,20,$this->school,$teacherName,$status);
		if($teachers['data']){
			$_User = new UserModel();
			$_SchoolGroupTeacher = new School_Group_TeacherModel();
			$_SchoolGroup = new School_GroupModel();
			foreach($teachers['data'] as $key=>&$teacher){
				$teacher['groupNames'] = array();
				if($group){
					if(!$_SchoolGroupTeacher->getRow(array('school'=>$this->school,'group'=>$group,'teacher'=>$teacher['teacher']),'group')){
						unset($teachers['data'][$key]);
					}
				}
				$groups = $_SchoolGroupTeacher->getAll(array('school'=>$this->school,'teacher'=>$teacher['teacher']),'group');
				if($groups){
					foreach($groups as $_group){
						$groupInfo = $_SchoolGroup->getRow(array('id'=>$_group['group']));
						if($groupInfo){
							$teacher['groupNames'][] = $groupInfo['name'];
						}
					}
				}
				$teacher['groupNames'] = $teacher['groupNames'] ? implode('|',$teacher['groupNames']):"--";
			}
		}
		$this->getView()->assign('pages', $teachers['pages']);
		$this->getView()->assign('teachers', $teachers['data']);
		$_SchoolGroup = new School_GroupModel();
		$groups = $_SchoolGroup->getSchoolGroup($this->school);
		$this->getView()->assign('groups', $groups);
	}
	
	public function addAction(){
		$username = $this->post('username','','trim');
		if($username){
			$_User = new UserModel();
			$userInfo = $_User->getRow("account = '$username' or hulaid = '$username'");
			if(!$userInfo) show_message('用户不存在！');
			//用户是否老师
			$_Teacher = new TeacherModel();
			$teacherInfo = $_Teacher->getRow(array('user'=>$userInfo['id']));
			if(!$teacherInfo) show_message('用户非教师！');
			//是否已经是机构老师
			$_SchoolTeacher = new School_TeacherModel();
			if($_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$userInfo['id']))) show_message('用户已经是机构老师！');
			//发送申请
			$_Apply = new ApplyModel();
			if($_Apply->getApplyForSchoolAddTeacher($this->school,$userInfo['id']))  show_message('已经发送过申请了，不能重复发送！');
			$id = $_Apply->sendApplyForSchoolAddTeacher($this->school,$userInfo['id'],$this->uid);
			if(!$id) show_message('申请发送失败！');
			//发送push消息
			$_Push = new PushModel();
			$_School = new SchoolModel();
			$applyInfo = $_Apply->getRow(array('id'=>$id),'id,`type`,`from`,`to`,student,create_time');
			$applyInfo['from'] = $_School->getRow(array('id'=>$this->school),'id as school,code,name,avatar');
			$_Push->addPush(array(
				'app' => 'apply', 
				'act' => 'add', 
				'from'=> $this->uid,
				'to' => $userInfo['id'], 
				'student' => '', 
				'ext'=>$applyInfo,
				'type' => '2',
				'message' => '您有新的机构邀请！', 
			));
			show_message('申请已经发出请等待对方验证！','','add');
		}
	}
	
	public function applyAction(){
		$page = $this->get('page',1,'intval');
		$_Apply = new ApplyModel();
		$applys = $_Apply->getApplyListForTeacherAddSchool($page,20,$this->school);
		$this->getView()->assign('pages', $applys['pages']);
		$this->getView()->assign('applys', $applys['data']);
	}
	
	public function doApplyAction(){
		$ids = $this->_GET['id'] ? array($this->get('id',0,'intval')):$this->post('id');
		$act = $this->get('act',0,'intval');
		if(!$ids || !in_array($act,array(1,2))) show_message('参数错误！');
		$data = array(
			'status' => $act, 
			'verify_time' => time(),
			'operator' => $this->uid
		);
		$_Apply = new ApplyModel();
		$_SchoolTeacher = new School_TeacherModel();
		$_Push = new PushModel();
		$_School = new SchoolModel();
		$fromInfo = $_School->getRow(array('id'=>$this->school),'id as school,code,name');
		foreach($ids as $id){
			$apply = $_Apply->getRow(array('id'=>$id),'id,`type`,`from`,`to`,student,create_time');
			if(!$apply || $apply['to'] != $this->school) continue;
			if(!$_Apply->deleteData(array('id'=>$id))) continue;
			$teacher = $apply['from'];
			$apply['from'] = $fromInfo;
			//添加老师
			if($act == 1){
				if($_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$teacher))) continue;
				$data = array(
					'school'=>$this->school,
					'teacher'=>$teacher,
					'create_time'=>time(),
					'operator'=>$this->uid
				);
				if(!$_SchoolTeacher->insertData($data)) continue;
				$_Push->addPush(array(
					'app' => 'apply', 
					'act' => 'add', 
					'from'=> $this->uid,
					'to' => $teacher, 
					'student' => '', 
					'ext'=>$apply,
					'type' => '2',
					'message' => '您的机构老师申请已经通过！', 
				));
				
			}
		}
		show_message('处理成功！',url('school','teacher','apply'));
	}
	
	
	public function inviteAction(){
		$page = $this->get('page',1,'intval');
		$_Apply = new ApplyModel();
		$applys = $_Apply->getApplyListForSchoolAddTeacher($page,20,$this->school);
		$this->getView()->assign('pages', $applys['pages']);
		$this->getView()->assign('applys', $applys['data']);
	}
	
	public function deleteAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('参数错误！');
		$_SchoolTeacher = new School_TeacherModel();
		if(!$_SchoolTeacher->deleteTeacher($this->school,$teacher)) show_message('删除失败！');
		show_message('删除成功！',url('school','teacher'));
	}
	
	public function groupAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('参数错误！');
		//用户是否老师
		$_Teacher = new TeacherModel();
		$teacherInfo = $_Teacher->getRow(array('user'=>$teacher));
		if(!$teacherInfo) show_message('用户非教师！');
		$_SchoolTeacher = new School_TeacherModel();
		if(!$_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$teacher))) show_message('老师不属于该机构！');
		//获取老师分组
		$_SchoolGroupTeacher = new School_Group_TeacherModel();
		$oldGroups = $_SchoolGroupTeacher->getAll(array('school'=>$this->school,'teacher'=>$teacher),'group');
		if($oldGroups){
			foreach($oldGroups as &$oldGroup){
				$oldGroup = $oldGroup['group'];
			}
		}
		if($this->_POST){
			$newGroups = $this->post('group');
			$newGroups = $newGroups ? $newGroups : array();
			if(!$_SchoolGroupTeacher->updateTeacherGroup($this->school,$teacher,$newGroups,$oldGroups)) show_message('老师分组修改失败！');
			show_message('老师分组修改成功！','','group');
		}else{
			
			$_SchoolGroup = new School_GroupModel();
			$allGroups = $_SchoolGroup->getAll(array('school'=>$this->school));
			if($allGroups){
				foreach($allGroups as &$group){
					$group['checked'] = $oldGroups && in_array($group['id'],$oldGroups) ? "checked" : "";
				}
			}
			$this->getView()->assign('teacher',$teacher);
			$this->getView()->assign('groups',$allGroups);
		}
		
	}
	
	public function infoAction(){
		$act = $this->get('act',5,'intval');
		$teacher = $this->get('teacher',0,'intval');
		$page = $this->get('page',1,'intval');
		if(!$teacher) show_message('参数错误！');
		
		//老师基本信息
		//用户是否老师
		$_Teacher = new TeacherModel();
		$teacherInfo = $_Teacher->getRow(array('user'=>$teacher));
		if(!$teacherInfo) show_message('用户非教师！');
		$_SchoolTeacher = new School_TeacherModel();
		if(!$_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$teacher))) show_message('老师不属于该机构！');
		$_User = new UserModel();
		$userInfo = $_User->getRow(array('id'=>$teacher));
		$teacherInfo['userInfo'] = $userInfo;
		$teacherInfo['groupNames'] = array();
		$_SchoolGroupTeacher = new School_Group_TeacherModel();
		$groups = $_SchoolGroupTeacher->getAll(array('school'=>$this->school,'teacher'=>$teacher),'group');
		if($groups){
			$_SchoolGroup = new School_GroupModel();
			foreach($groups as $group){
				$groupInfo = $_SchoolGroup->getRow(array('id'=>$group['group']));
				if($groupInfo){
					$teacherInfo['groupNames'][] = $groupInfo['name'];
				}
			}
		}
		$teacherInfo['groupNames'] = implode('|',$teacherInfo['groupNames']);
		$this->getView()->assign('teacher',$teacherInfo);
		if($act == 1){
			//老师课程
			//根据条件搜索课程id
			$where = '';
			$text = $this->get('text','','trim');
			if($text && $text != "课程名称"){
				$where .= " and b.text like '%$text%'";
			}
			$start_date = $this->get('start_date','','isDate');
			if($start_date){
				$where .= " and b.end_date >= '$start_date 00:00:00'";
			}
			$end_date = $this->get('end_date','','isDate');
			if($end_date){
				$where .= " and b.start_date <= '$end_date 23:59:59'";
			}
			$_CourseTeacher = new Course_TeacherModel();
			$events = $_CourseTeacher->getTeacherCourseList($page,5,$teacher,$this->school,$where);
			if($events['data']){
				$_CourseStudent = new Course_StudentModel();
				foreach($events['data'] as &$data){
					$data['studentNum'] = $_CourseStudent->getCount(array('event'=>$data['event'],'status'=>0,'source'=>0));
				}
			}
			$this->getView()->assign('pageEvents', $events['pages']);
			$this->getView()->assign('events', $events['data']);
		}elseif($act == 2){
			//学生评价
			$_Comment = new CommentModel();
			$comments = $_Comment->getList($page,5,array('school'=>$this->school,'teacher'=>$teacher,'character !='=>'teacher','event'=>0,'pid'=>0),'*','','id desc');
			if($comments['data']){
				$_Attach = new AttachModel();
				foreach($comments['data'] as &$data){
					$data['attachInfos'] = array();
					if($data['attach']){
						$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
					}
				}
			}
			$this->getView()->assign('pageComments', $comments['pages']);
			$this->getView()->assign('comments', $comments['data']);
		}elseif($act == 3){
			//老师备注
			$_SchoolTeacherRemark = new School_Teacher_RemarkModel();
			$remarks = $_SchoolTeacherRemark->getAll(array('school'=>$this->school,'teacher'=>$teacher),'id,remark','create_time desc','',5);
			$this->getView()->assign('remarks',$remarks);
		}elseif($act == 4){
			//老师点评
			$_Comment = new CommentModel();
			$comments = $_Comment->getList($page,5,array('school'=>$this->school,'teacher'=>$teacher,'character'=>'teacher','pid'=>0),'*','','id desc');
			if($comments['data']){
				$_Attach = new AttachModel();
				$_Event = new EventModel();
				foreach($comments['data'] as &$data){
					$data['attachInfos'] = array();
					if($data['attach']){
						$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
					}
					$data['eventText'] = '';
					if($data['event']){
						$eventInfo = $_Event->getRow(array('id'=>$data['event']),'text');
						$data['eventText'] = $eventInfo['text'];
					}
					
				}
			}
			$this->getView()->assign('pageComments', $comments['pages']);
			$this->getView()->assign('comments', $comments['data']);
		}elseif($act == 5){
			//老师设置
			$_Teacher_Fee = new Teacher_FeeModel();
			$fees = $_Teacher_Fee->getAll(array("teacher"=>$teacher,'type'=>1,'to'=>$this->school));
			if($fees){
				$feeTexts = array_column($fees,'text');
				$fees = array_combine($feeTexts,$fees);
			}else{
				$feeTexts = array();
			}
			//老师已上课程
			$_Course_Teacher = new Course_TeacherModel();
			$hads = $_Course_Teacher->getTeacherCoursesByText($teacher,$this->school,$feeTexts);
			foreach($hads as &$had){
				if($had['fee_setting']){
					$feeCfg= $fees[$had['text']];
					$had = array_merge($feeCfg,$had);
				}
				
			}
			$this->getView()->assign('hads',$hads);
		}
	}
	
	public function remarkAction(){
		$act = $this->get('act',0,'intval');
		if(!in_array($act,array(1,2))) show_message('act参数错误！');
		if($act == 1){
			if($this->_POST){
				$teacher = $this->post('teacher',0,'intval');
				if(!$teacher) show_message('参数错误！');
				$remark = $this->post('remark','','trim');
				if(!$remark) show_message('备注不能为空！');
				//用户是否老师
				$_Teacher = new TeacherModel();
				$teacherInfo = $_Teacher->getRow(array('user'=>$teacher));
				if(!$teacherInfo) show_message('用户非教师！');
				$_SchoolTeacher = new School_TeacherModel();
				if(!$_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$teacher))) show_message('老师不属于该机构！');
				$_SchoolTeacherRemark = new School_Teacher_RemarkModel();
				$data = array(
					'school'=>$this->school,
					'teacher'=>$teacher,
					'remark'=>$remark,
					'create_time'=>time()
				);
				if(!$_SchoolTeacherRemark->insertData($data)) show_message('添加备注失败！');
				show_message('添加备注成功！',url('school','teacher','info','teacher='.$teacher.'&act=3'));
			}else{
				$teacher = $this->get('teacher',0,'intval');
				if(!$teacher) show_message('参数错误！');
				$this->getView()->assign('teacher',$teacher);
			}
		}elseif($act == 2){
			$id = $this->get('id',0,'intval');
			if(!$id) exit('参数错误！');
			$_SchoolTeacherRemark = new School_Teacher_RemarkModel();
			if(!$_SchoolTeacherRemark->getRow(array('id'=>$id))) exit('备注不存在！');
			if(!$_SchoolTeacherRemark->deleteData(array('id'=>$id))) exit('删除备注失败！');
			exit('1');
		}
	}
	
	
	public function freezeAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('参数错误！');
		//用户是否老师
		$_Teacher = new TeacherModel();
		$teacherInfo = $_Teacher->getRow(array('user'=>$teacher));
		if(!$teacherInfo) show_message('用户非教师！');
		$_SchoolTeacher = new School_TeacherModel();
		$schoolTeacherInfo = $_SchoolTeacher->getRow(array('school'=>$this->school,'teacher'=>$teacher));
		if(!$schoolTeacherInfo) show_message('老师不属于该机构！');
		if($schoolTeacherInfo['status'] == 1)  show_message('该老师已删除！');
		$status = $schoolTeacherInfo['status'] == 0 ? 2 : 0;
		if(!$_SchoolTeacher->updateData(array('status'=>$status),array('school'=>$this->school,'teacher'=>$teacher))) show_message('操作失败！');
		$this->redirect('/School/Teacher/Index');
	}
}