<?php
/**
 * 机构学生
 */
class StudentController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$studentName = $this->get('studentName','','trim');
		$studentName = $studentName != "学生名" ? $studentName:'';
		$gradeName = $this->get('gradeName','','trim');
		$gradeName = $gradeName != "班级名" ? $gradeName:'';
		$_Grade = new GradeModel();
		$grades = '';
		if($gradeName){
			$grades = $_Grade->getAll("school = $this->school and name like '%$gradeName%'",'id');
			if($grades){
				$grades = implode(',',array_column($grades,'id'));
			}else{
				$grades = -1;
			}
		}
		$status = $this->get('status',-1,'intval');
		$_SchoolStudent = new School_StudentModel();
		$students = $_SchoolStudent->getSchoolStudentList($page,20,$this->school,$studentName,$status,$grades);
		if($students['data']){
			$_GradeStudent = new Grade_StudentModel();
			foreach($students['data'] as &$student){
				$student['gradeNames'] = array();
				$grades = $_GradeStudent->getAll(array('school'=>$this->school,'student'=>$student['student']),'grade');
				if($grades){
					foreach($grades as $grade){
						$gradeInfo = $_Grade->getRow(array('id'=>$grade['grade']));
						if($gradeInfo){
							$student['gradeNames'][] = "<a href='javascript:showStudent(\"".$gradeInfo['name']."\")' class='link'>".$gradeInfo['name']."</a>";
						}
					}
				}
				$student['gradeNames'] = $student['gradeNames'] ? implode('|',$student['gradeNames']):"--";
			}
		}
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('students', $students['data']);
	}
	
	public function addAction(){
		$username = $this->post('username','','trim');
		if($username){
			$_User = new UserModel();
			$userInfo = $_User->getRow("account = '$username' or hulaid = '$username'");
			if(!$userInfo) show_message('用户不存在！');
			//用户是否学生
			$_Student = new StudentModel();
			$userStudentInfos = $_Student->getAll(array('creator'=>$userInfo['id']));
			if(!$userStudentInfos) show_message('用户无学生！');
			//是否已经是机构学生
			$_SchoolStudent = new School_StudentModel();
			$allSchoolStudent = 0;
			foreach($userStudentInfos as $userStudentInfo){
				if($_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$userStudentInfo['student']))){
					$allSchoolStudent = 1;
				}else{
					$allSchoolStudent = 0;
				}
			}
			if($allSchoolStudent) show_message('用户的学生已经是机构学生！');
			//发送申请
			$_Apply = new ApplyModel();
			if($_Apply->getApplyForSchoolAddStudent($this->school,$userInfo['id']))  show_message('已经发送过申请了，不能重复发送！');
			$id = $_Apply->sendApplyForSchoolAddStudent($this->school,$userInfo['id'],$this->uid);
			if(!$id) show_message('申请发送失败！');
			//发送push消息
			$_Push = new PushModel();
			$_School = new SchoolModel();
			$applyInfo = $_Apply->getRow(array('id'=>$id),'id,`type`,`from`,`to`,student,create_time');
			$applyInfo['from'] = $_School->getRow(array('id'=>$this->school),'id as school,code,name');
			$_Push->addPush(array(
				'app' => 'apply', 
				'act' => 'add', 
				'from'=> $this->uid,
				'to' => $userInfo['id'], 
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
		$applys = $_Apply->getApplyListForStudentAddSchool($page,20,$this->school);
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
		$_SchoolStudent = new School_StudentModel();
		$_Push = new PushModel();
		$_School = new SchoolModel();
		$fromInfo = $_School->getRow(array('id'=>$this->school),'id as school,code,name,avatar');
		foreach($ids as $id){
			$apply = $_Apply->getRow(array('id'=>$id),'id,`type`,`from`,`to`,student,create_time');
			if(!$apply || $apply['to'] != $this->school) continue;
			if(!$_Apply->deleteData(array('id'=>$id))) continue;
			$apply['from'] = $fromInfo;
			//添加学生
			if($act == 1){
				if($_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$apply['student'])))  continue;
				$data = array(
					'school'=>$this->school,
					'student'=>$apply['student'],
					'create_time'=>time(),
					'operator'=>$this->uid,
					'source'=>1,
				);
				if(!$_SchoolStudent->insertData($data)) continue;
				//发送push消息
				$_Push->addPush(array(
					'app' => 'apply', 
					'act' => 'add', 
					'from'=> $this->uid,
					'to' => $apply['from'], 
					'student' => $apply['student'], 
					'ext'=>$apply,
					'type' => '2',
					'message' => '您的机构学生申请已经通过！', 
				));
			}
		}
		show_message('处理成功！',url('school','student','apply'));
	}
	
	
	public function inviteAction(){
		$page = $this->get('page',1,'intval');
		$_Apply = new ApplyModel();
		$applys = $_Apply->getApplyListForSchoolAddStudent($page,20,$this->school);
		$this->getView()->assign('pages', $applys['pages']);
		$this->getView()->assign('applys', $applys['data']);
	}
	
	
	public function deleteAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('参数错误！');
		$_SchoolStudent = new School_StudentModel();
		if(!$_SchoolStudent->deleteStudent($this->school,$student)) show_message('删除失败！');
		show_message('删除成功！',url('school','student'));
	}
	
	public function gradeAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('参数错误！');
		//用户是否学生
		$_Student = new StudentModel();
		$studentInfo = $_Student->getRow(array('id'=>$student));
		if(!$studentInfo) show_message('用户非学生！');
		$_SchoolStudent = new School_StudentModel();
		if(!$_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$student))) show_message('学生不属于该机构！');
		//获取学生班级
		$_GradeStudent = new Grade_StudentModel();
		$oldGrades = $_GradeStudent->getAll(array('school'=>$this->school,'student'=>$student),'grade');
		if($oldGrades){
			foreach($oldGrades as &$oldGrade){
				$oldGrade = $oldGrade['grade'];
			}
		}
		if($this->_POST){
			$newGrades = $this->post('grade');
			$newGrades = $newGrades ? $newGrades : array();
			if(!$_GradeStudent->updateStudentGrade($this->school,$student,$this->uid,$newGrades,$oldGrades)) show_message('学生班级修改失败！');
			show_message('学生班级修改成功！','','grade');
		}else{
			$_Grade = new GradeModel();
			$allGrades = $_Grade->getAll(array('school'=>$this->school));
			if($allGrades){
				foreach($allGrades as &$grade){
					$grade['checked'] = $oldGrades && in_array($grade['id'],$oldGrades) ? "checked" : "";
				}
			}
			$this->getView()->assign('student',$student);
			$this->getView()->assign('grades',$allGrades);
		}
		
	}
	
	public function createAction(){
		if($this->_POST){
			$name = $this->post('name','','trim');
			$parent_name = $this->post('parent_name','','trim');
			$phone = $this->post('phone','','trim');
			if(!$name || !$parent_name || !$phone) show_message('参数错误！');
			$name_en = Ustring::topinyin($name);
			$_Student = new StudentModel();
			$studentInfo = $_Student->getAll(array('phone'=>$phone,'name'=>$name),'id');
			$studentIds = $studentInfo ? implode(',',array_column($studentInfo,'id')) : "";
			$_SchoolStudent = new School_StudentModel();
			if($studentIds){
				if($_SchoolStudent->getRow("school = $this->school and student in ($studentIds)",'id')) show_message('学生已存在！');
			}
			if(!$_SchoolStudent->createStudent($this->uid,$this->school,$name,$name_en,$parent_name,$phone)) show_message('创建学生失败！');
			show_message('创建学生成功！','','create');
		}
	}
	
	public function infoAction(){
		$act = $this->get('act',4,'intval');
		$student = $this->get('student',0,'intval');
		$page = $this->get('page',1,'intval');
		if(!$student) show_message('参数错误！');
		
		//学生基本信息
		//用户是否学生
		$_Student = new StudentModel();
		$studentInfo = $_Student->getRow(array('id'=>$student),'id,name,avatar,gender,tag,birthday,creator,parent_name,phone,source');
		if(!$studentInfo) show_message('用户非学生！');
		$_SchoolStudent = new School_StudentModel();
		$schoolStudentInfo = $_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$student));
		if(!$schoolStudentInfo) show_message('学生不属于该机构！');
		$studentInfo['create_time'] = $schoolStudentInfo['create_time'];
		$_User = new UserModel();
		$createrInfo = $_User->getRow(array('id'=>$studentInfo['creator']),'hulaid,nickname,account');
		$studentInfo['hulaid'] = $createrInfo['hulaid'];
		$parents = array();
		//获取学生家长
		if($studentInfo['source'] != 1){
			$_UserStudent = new User_StudentModel();
			$parents = $_UserStudent->getAll(array('student'=>$student));
			if($parents){
				foreach($parents as &$parent){
					$info = $_User->getRow(array('id'=>$parent['user']),'hulaid,nickname,account');
					$parent['nickname'] = $info['nickname'];
					$parent['hulaid'] = $info['hulaid'];
					$parent['account'] = $info['account'];
				}
			}
		}else{
			$parents[] = array(
				'user'=>0,
				'student'=>$student,
				'relation'=>4,
				'nickname'=>$studentInfo['parent_name'],
				'account'=>$studentInfo['phone']
			);
		}
		$this->getView()->assign('student',$studentInfo);
		$this->getView()->assign('parents',$parents);
		if($act == 1){
			//学生课程
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
			$_CourseStudent = new Course_StudentModel();
			$events = $_CourseStudent->getStudentCourseList($page,5,$student,$this->school,$where);
			$this->getView()->assign('pageEvents', $events['pages']);
			$this->getView()->assign('events', $events['data']);
		}elseif($act == 2){
			//学生评价
			$_Comment = new CommentModel();
			$comments = $_Comment->getList($page,5,array('student'=>$student,'character !='=>'student','event'=>0,'pid'=>0),'*','','id desc');
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
			//学生备注
			$_SchoolStudentRemark = new School_Student_RemarkModel();
			$remarks = $_SchoolStudentRemark->getAll(array('school'=>$this->school,'student'=>$student),'id,remark','create_time desc','',5);
			$this->getView()->assign('remarks',$remarks);
		}elseif($act == 4){
			//学生设置
			$_Student_Fee = new Student_FeeModel();
			$fees = $_Student_Fee->getAll(array("student"=>$student,'type'=>1,'to'=>$this->school));
			if($fees){
				$feeTexts = array_column($fees,'text');
				$fees = array_combine($feeTexts,$fees);
			}else{
				$feeTexts = array();
			}
			//学生缴费
			$_Student_Money = new Student_MoneyModel();
			$hadMoney = $_Student_Money->getSum('money',array("student"=>$student,'school'=>$this->school));
			//学生已上课程
			$_Course_Student = new Course_StudentModel();
			$hads = $_Course_Student->getStudentCoursesByText($student,$this->school,$feeTexts);
			$usedMoney  = 0;
			$lessedMoney  = 0;
			foreach($hads as &$had){
				if($had['fee_setting']){
					$feeCfg= $fees[$had['text']];
					$had = array_merge($feeCfg,$had);
					$needMoney = $had['fee']*$had['attend']*$had['discount'];
					$used = ($hadMoney - $usedMoney) > 0 ? (($hadMoney - $usedMoney - $needMoney) > 0 ? $needMoney : ($hadMoney - $usedMoney)) : 0;
					$lessedMoney += $used < $needMoney ? ($needMoney - $used) : 0;
					$usedMoney += $used;
					$had['used'] = $used;
					$had['lessed'] = $needMoney - $used;
				}
			}
			$this->getView()->assign('hads',$hads);
			$this->getView()->assign('hadMoney',$hadMoney);
			$this->getView()->assign('usedMoney',$usedMoney);
			$this->getView()->assign('lessedMoney',$lessedMoney);
		}
	}
	
	public function remarkAction(){
		$act = $this->get('act',0,'intval');
		if(!in_array($act,array(1,2))) show_message('act参数错误！');
		if($act == 1){
			if($this->_POST){
				$student = $this->post('student',0,'intval');
				if(!$student) show_message('参数错误！');
				$remark = $this->post('remark','','trim');
				if(!$remark) show_message('备注不能为空！');
				//用户是否学生
				$_Student = new StudentModel();
				$studentInfo = $_Student->getRow(array('id'=>$student),'id,name,avatar,tag,parent_name,phone,source');
				if(!$studentInfo) show_message('用户非学生！');
				$_SchoolStudent = new School_StudentModel();
				$schoolStudentInfo = $_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$student));
				if(!$schoolStudentInfo) show_message('学生不属于该机构！');
				$_SchoolStudentRemark = new School_Student_RemarkModel();
				$data = array(
					'school'=>$this->school,
					'student'=>$student,
					'remark'=>$remark,
					'create_time'=>time()
				);
				if(!$_SchoolStudentRemark->insertData($data)) show_message('添加备注失败！');
				show_message('添加备注成功！',url('school','student','info','student='.$student.'&act=3'));
			}else{
				$student = $this->get('student',0,'intval');
				if(!$student) show_message('参数错误！');
				$this->getView()->assign('student',$student);
			}
		}elseif($act == 2){
			$id = $this->get('id',0,'intval');
			if(!$id) exit('参数错误！');
			$_SchoolStudentRemark = new School_Student_RemarkModel();
			if(!$_SchoolStudentRemark->getRow(array('id'=>$id))) exit('备注不存在！');
			if(!$_SchoolStudentRemark->deleteData(array('id'=>$id))) exit('删除备注失败！');
			exit('1');
		}
	}
	
	
	public function freezeAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('参数错误！');
		//用户是否学生
		$_Student = new StudentModel();
		$studentInfo = $_Student->getRow(array('id'=>$student),'id,name,avatar,gender,tag,parent_name,phone,source');
		if(!$studentInfo) show_message('用户非学生！');
		$_SchoolStudent = new School_StudentModel();
		$schoolStudentInfo = $_SchoolStudent->getRow(array('school'=>$this->school,'student'=>$student));
		if(!$schoolStudentInfo) show_message('学生不属于该机构！');
		if($schoolStudentInfo['status'] == 1)  show_message('该学生已删除！');
		$status = $schoolStudentInfo['status'] == 0 ? 2 : 0;
		if(!$_SchoolStudent->updateData(array('status'=>$status),array('school'=>$this->school,'student'=>$student))) show_message('操作失败！');
		$this->redirect('/School/Student/Index');
	}
}