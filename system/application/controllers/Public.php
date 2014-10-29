<?php
class PublicController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {}
	
	/**
	 * 左侧菜单
	 */
	public function leftAction() {
		$this->checkLogin();
		$menuid = $this->get('menuid',0,'intval');
		$_AdminMenu = new Admin_MenuModel();
		$menu = $_AdminMenu->getRow(array('id'=>$menuid));
		if(!$menu) show_message('menuid 不存在');
		$menus = $_AdminMenu->getAll(array('pid'=>$menuid,'display'=>1),'*','sort asc,id desc');
		if($menus){
			$enable = array();
			if($this->user['enable'] != '*'){
				$enable = explode(',',$this->user['enable']);
			}
			foreach($menus as $key=>&$menu){
				if($enable && !in_array($menu['id'],$enable)){
					unset($menus[$key]);
				}else{
					$childrens = $_AdminMenu->getAll(array('pid'=>$menu['id'],'display'=>1),'*','sort asc,id desc');
					if($childrens && !empty($childrens)){
						foreach($childrens as $_key=>$children){
							if($enable && !in_array($children['id'],$enable)){
								unset($childrens[$_key]);
							}
						}
						if($childrens && !empty($childrens)){
							$menu['childrens'] = $childrens;
						}
					}
				}
			}
		}
		$this->getView()->assign('menus', $menus);
		if($menu['type'] != "Index"){
			$this->getView()->setScriptPath(APPLICATION_PATH ."/application/modules/".ucfirst($menu['type'])."/views");
		}
	}
	/**
	 * 当前位置
	 */
	public function posAction() {
		$menuid = $this->get('menuid',0,'intval');
		$id = $this->get('id',0,'intval');
		$_AdminMenu = new Admin_MenuModel();
		$menu = $_AdminMenu->getRow(array('id'=>$menuid));
		if(!$menu) show_message('menuid 不存在');
		$__menu = $_AdminMenu->getRow(array('id'=>$menu['pid']));
		$_menu = $_AdminMenu->getRow(array('id'=>$id));
		if(!$_menu) show_message('id 不存在');
		echo $__menu['name'].' > '.$menu['name'].' > '.$_menu['name'];
		exit;
	}
	
	/**
	 * 获取班级
	 */
	public function getGradeAction() {
		$school= $this->get('school',0,'intval');
		$name= $this->get('name','','trim');
		if(!$name || $name == "班级名") exit('0');
		$_Grade = new GradeModel();
		$grades = $_Grade->getAll(array('school'=>$school,"name like '%$name%'"=>NULL),'name,id');
		if(!$grades) exit('0');
		echo json_encode($grades);
		exit;
	}
	
	/**
	 * 获取机构教师组
	 */
	public function getSchoolGroupAction() {
		$school= $this->get('school',0,'intval');
		$name= $this->get('name','','trim');
		if(!$school) exit('0');
		if(!$name || $name == "教师组名") exit('0');
		$_School_Group = new School_GroupModel();
		$groups = $_School_Group->getAll(array('school'=>$school,"name like '%$name%'"=>NULL),'name,id');
		if(!$groups) exit('0');
		echo json_encode($groups);
		exit;
	}
	
	/**
	 * 检测用户
	 */
	public function userExistAction(){
		$username = $this->get('username','','trim');
		if(!$username){
			exit('0');
		}else{
			$_User = new UserModel();
			$user = $_User->getRow("account = '$username' or hulaid = '$username'");
			if(!$user){
				exit('0');
			}else{
				exit('1');
			}
		}
	}
	
	/**
	 * 检测机构
	 */
	public function schoolExistAction(){
		$code = $this->get('code','','trim');
		if(!$code){
			exit('0');
		}else{
			$_School = new SchoolModel();
			$school = $_School->getRow("code = '$code'");
			if(!$school){
				exit('0');
			}else{
				exit('1');
			}
		}
	}
	
	//
	public function codeAction(){
		$type = $this->post('type',0,'intval');//0注册、1取回密码 2、其他
		$mobile = $this->post('mobile', '','trim');
		if(!in_array($type,array(0,1,2))) exit('非法参数');//
		if(!$mobile || !preg_match('/^(1)[0-9]{10}$/',$mobile)) exit('手机号不存在或格式错误');//
        $message = '';
        $_User = new UserModel();
        if($type == 0){
            if($_User->getRow(array('account' => $mobile))) exit('用户已存在');//
			$message = apiConfig('notice', 'register');
        }else if($type == 1)
		{
			if(!$_User->getRow(array('account' => $mobile))) exit('用户不存在');//
			$message = apiConfig('notice', 'forget');
		}
		$_Verify = new VerifyModel();
		
		$res = $_Verify->send($mobile, $type, $message);
		if(is_array($res)){
			exit('发送成功');
		}else{
			$errors = apiConfig('error', 'sms');
			$error = isset($errors[$res]) ? $errors[$res] : '发送失败';				
			exit($error);
		}
	}
	
	public function areaAction(){
		$pid = $this->get('pid',0,'intval');
		if(!$pid) exit;
		$_Area = new AreaModel();
		$areas = $_Area->getAreaByPid($pid);
		$html = "";
		if($areas){
			$html .= "<option value=''>请选择</option>";
			foreach($areas as $key=>$area){
				$html .= "<option value='$key'>$area</option>";
			}
		}
		echo $html;
		exit;
	}
	
	public function courseTypeAction(){
		$pid = $this->get('pid',0,'intval');
		if(!$pid) exit;
		$_CourseType = new Course_TypeModel();
		$courseTypeList = $_CourseType->getCourseTypeByPid($pid);
		$html = "";
		if($courseTypeList){
			$html .= "<option value=''>请选择</option>";
			foreach($courseTypeList as $key=>$courseType){
				$html .= "<option value='$key'>$courseType</option>";
			}
		}
		echo $html;
		exit;
	}
	
	public function selectTeacherAction(){
		$num = $this->get('num', 0, 'intval');
		$event = $this->get('event', 0, 'intval');
		$school = $this->get('school', 0, 'intval');
		$priv = $this->get('priv', 0, 'intval');
		$groupEdit = $this->get('groupEdit', 0, 'intval');
		if($this->_POST){
			// 老师验证
			$teachers = $this->post('teacher_op', array());
			if($groupEdit == 0){// 教师组增减老师
				$_teacher = 0;
				$script = '<script type="text/javascript">
					var right = window.top.document.getElementById("rightMain").contentWindow.document;
					var teacherArea = right.getElementById("selectTeacherArea");
					teacherArea.innerHTML = "";
				';
				if($teachers)
				{
					$_SchoolTeacher = new School_TeacherModel();
					$_Teacher = new TeacherModel();
					$_User = new UserModel();
		            foreach($teachers as $teacher=>$_priv)
		            {	
		                if($teacher && $_priv & 1 ) $_teacher = $teacher;
		                if(!$_priv) show_message('老师权限未设置!@'.$teacher);
		                if($school && !$_SchoolTeacher->getRow(array('school' => $school, 'teacher' => $teacher))) show_message('不是机构老师!@'.$teacher);
		                if(!$_Teacher->getRow(array('user' => $teacher))) show_message('没有此老师!@'.$teacher);
		                $userInfo = $_User->getRow(array('id' => $teacher));
		                if(!$userInfo) show_message('没有此用户!@'.$teacher);
		                $html = '<div class=\"select_teacher_op\" id=\"teacher_op_'.$teacher.'\" onclick=\"$(this).remove()\">'.$userInfo['firstname'].$userInfo['lastname'].'<input type=\"hidden\" name=\"teacher_op['.$teacher.']\" value=\"'.$_priv.'\"/></div>';
		            	$script .= 'teacherArea.innerHTML = "'.$html.'"+teacherArea.innerHTML;';
		            }
				}
				if(!$teachers) show_message('老师设置错误!');
				if(count($teachers) > 100) show_message('选择老师太多，请控制在100以内!');
				if($priv && !$_teacher) show_message('老师没有上课权限!');
				//Yaf_Session::getInstance()->set('teacher_op',$teachers);
				Yaf_Session::getInstance()->set('selectedTeachers',$teachers);
				$script .= 'window.top.art.dialog({id:"teacher"}).close();</script>';        
			}else{
				//取得分组老师
				$_SchoolGroupTeacher = new School_Group_TeacherModel();		
				$oldTeachers = $_SchoolGroupTeacher->getAll(array('group'=>$groupEdit,'school'=>$school),'teacher');
				$teachers = array_keys($teachers);
				$old = array_walk($oldTeachers, create_function('&$v,$k', '$v=current($v);'));				
				$new = array_diff($teachers, $oldTeachers);				
				$lost = array_diff($oldTeachers, $teachers);
				$keep = array_intersect($oldTeachers, $teachers);
				// 新增
				foreach($new as $item)
				{
					$_SchoolGroupTeacher->insertData(array('group' => $groupEdit, 'teacher' => $item, 'school' => $school));
				}
				// lost
				if($lost)
				{					
					$_SchoolGroupTeacher->deleteData("teacher in(" . join(",", $lost) . ") And school = $school and `group`='" . $groupEdit . "'");
				}				
				// 分组里添加/修改老师
				$script = '<script type="text/javascript">';
				//--
				$script .= 'window.top.art.dialog({id:"teacher"}).close();window.top.document.getElementById("rightMain").contentWindow.location.reload();</script>';        
			}
			echo $script;
			exit;
		}else{
			if(!$num){
				Yaf_Session::getInstance()->del('selectedTeachers');
			}
			if($school){
				//获取教师
				$_SchoolTeacher = new School_TeacherModel();
				$teachers = $_SchoolTeacher->getSchoolTeacher($school,true);
				//获取教师组
				$_SchoolGroup = new School_GroupModel();
				$groups = $_SchoolGroup->getSchoolGroup($this->school);
			}else{
				//获取教师
				$_Teacher = new TeacherModel();
				$teachers = $_Teacher->getTeacher(true);
				//获取教师组
				$groups = array();
			}
			if($teachers){
				$teachers = $this->azdata($teachers);
			}
			//获取选择的老师
			$selectedTeachersArr = Yaf_Session::getInstance()->get('selectedTeachers');
			if(!$selectedTeachersArr){
				if($groupEdit)
				{
					$_SchoolGroupTeacher = new School_Group_TeacherModel();		
					$courseTeachers = $_SchoolGroupTeacher->getAll(array('group'=>$groupEdit,'school'=>$school),'teacher');
					if($courseTeachers){
						foreach($courseTeachers as $key=>$courseTeacher){
							$courseTeachers[$key]['priv'] = 7;
						}
					}
				}else{
					$_CourseTeacher = new Course_TeacherModel();
					$courseTeachers =  $event ? $_CourseTeacher->getAll(array('event'=>$event),'teacher,priv'):array();
				}			
				$selectedTeachersArr = array();
				if($courseTeachers){
					foreach($courseTeachers as $courseTeacher){
						$selectedTeachersArr[$courseTeacher['teacher']] = $courseTeacher['priv'];
					}
				}
			}
			$this->getView()->assign('groupEdit', $groupEdit);
			$this->getView()->assign('groups', $groups);
			$this->getView()->assign('event', $event);
			$this->getView()->assign('school', $school);
			$this->getView()->assign('priv', $priv);
			$this->getView()->assign('teachersArr', json_encode($teachers));
			$this->getView()->assign('selectedTeachersArr', json_encode($selectedTeachersArr));
		}
	}
	
	public function selectStudentAction(){
		$num = $this->get('num', 0, 'intval');
		$event = $this->get('event', 0, 'intval');
		$school = $this->get('school', 0, 'intval');
		$gradeEdit = $this->get('gradeEdit', 0, 'intval');
		if($this->_POST){			
			$students = $this->post('student_op', array());
			if($gradeEdit == 0)
			{
				// 学生验证				
				$grade = $this->post('grade',0,'intval');
				$script = '<script type="text/javascript">
					var right = window.top.document.getElementById("rightMain").contentWindow.document;
					var studentArea = right.getElementById("selectStudentArea");
					if(right.getElementById("grade")) right.getElementById("grade").value = '.$grade.';
					studentArea.innerHTML = "";
				';
				if($students)
				{
					$_SchoolStudent = new School_StudentModel();
					$_Student = new StudentModel();
					foreach($students as $student)
					{	
						if($school && !$_SchoolStudent->getRow(array('school' => $school, 'student' => $student))) show_message('不是机构学生!@'.$student);
						$studentInfo = $_Student->getRow(array('id' => $student));
						if(!$studentInfo) show_message('没有此学生!@'.$student);
						$html = '<div class=\"select_student_op\" id=\"student_op_'.$student.'\" onclick=\"$(this).remove()\">'.$studentInfo['name'].'<input type=\"hidden\" name=\"student_op[]\" value=\"'.$student.'\" /></div>';
						$script .= 'studentArea.innerHTML = "'.$html.'"+studentArea.innerHTML;';
					}
				}
				if(!$students) show_message('学生设置错误!');
				if(count($students) > 100) show_message('选择学生太多，请控制在100以内!');
				Yaf_Session::getInstance()->set('selectedStudents',$students);
				$script .= 'window.top.art.dialog({id:"student"}).close();</script>';
			}else{ // 班级增减学生
				//-- LYL 2014/1/2
				$_GradeStudent = new Grade_StudentModel();				
				$oldStudents = $_GradeStudent->getAll(array('grade'=>$gradeEdit,'school'=>$school),'student');
				$old = array_walk($oldStudents, create_function('&$v,$k', '$v=current($v);'));				
				$new = array_diff($students, $oldStudents);				
				$lost = array_diff($oldStudents, $students);
				$keep = array_intersect($oldStudents, $students);
				// 新增
				foreach($new as $item)
				{
					$_GradeStudent->insertData(array('grade' => $gradeEdit, 'student' => $item, 'school' => $school, 'creator' => $this->uid));
				}
				// lost
				if($lost)
				{					
					$_GradeStudent->deleteData("student in(" . join(",", $lost) . ") And school = $school and grade='" . $gradeEdit . "'");
				}				
				// 班级里添加/修改学生
				$script = '<script type="text/javascript">';
				//--
				$script .= 'window.top.art.dialog({id:"student"}).close();window.top.document.getElementById("rightMain").contentWindow.location.reload();</script>';
			}
			echo $script;
			exit;
		}else{
			if(!$num){
				Yaf_Session::getInstance()->del('selectedStudents');
			}
			if($school){
				//获取学生
				$_SchoolStudent = new School_StudentModel();
				$students = $_SchoolStudent->getSchoolStudent($school,true);
				//获取班级
				$_Grade = new GradeModel();
				$grades = $_Grade->getGrade($this->school);
			}else{
				//获取学生
				$_Student = new StudentModel();
				$students = $_Student->getStudent(true);
				//获取班级
				$_Grade = new GradeModel();
				$grades = array();
			}
			if($students){
				$students = $this->azdata($students);
			}
			//获取选择的学生
			$selectedStudentsArr = Yaf_Session::getInstance()->get('selectedStudents');		
			if(!$selectedStudentsArr){
				if($gradeEdit)
				{
					$_GradeStudent = new Grade_StudentModel();
					$courseStudents = $_GradeStudent->getAll(array('grade'=>$gradeEdit,'school'=>$school),'student');
				}else{
					$_CourseStudent = new Course_StudentModel();
					$courseStudents = $event ? $_CourseStudent->getAll(array('event'=>$event),'student'):array();
				}
				$selectedStudentsArr = array();
				if($courseStudents){
					foreach($courseStudents as $courseStudent){
						$selectedStudentsArr[$courseStudent['student']] = $courseStudent['student'];
					}
				}
			}
			$this->getView()->assign('gradeEdit', $gradeEdit);
			$this->getView()->assign('grades', $grades);
			$this->getView()->assign('event', $event);
			$this->getView()->assign('school', $school);
			$this->getView()->assign('studentsArr', json_encode($students));
			$this->getView()->assign('selectedStudentsArr', json_encode($selectedStudentsArr));
		}
	}
	
	private function azdata($datas,$num=10){
		$datas2 = $datas;
		$datas = array();
		foreach($datas2 as $key=>$_data){
			if(preg_match('/[a-z]/',$_data['name_en'])){
				$datas[$_data['name_en']][$key] = $_data;
			}else{
				$datas['other'][$key] = $_data;
			}
		}		
		$datas3 = $datas;
		$datas = array();
		$mergeKey = "A";
		$mergeCount = 0;
		$mergeArr = array();		
		ksort($datas3);
		foreach($datas3 as $key=>$_data){
			$count = count($_data);
			if($key != "other"){
				if($count < $num){
					if(($mergeCount + $count) >= $num){
						if($mergeKey && $mergeKey != strtoupper($key)) $datas[$mergeKey.'-'.strtoupper($key)] =  array_values($mergeArr + $_data);
						else $datas[strtoupper($key)] =  array_values($mergeArr + $_data);
						$mergeKey = strtoupper(chr(ord(strtoupper($key))+1));
						$mergeCount = 0;
						$mergeArr = array();
					}else{
						$mergeCount += $count;
						$mergeArr += $_data;
					}							
				}else{
					if($mergeKey && $mergeKey != strtoupper($key) && $mergeKey != strtoupper(chr(ord($key)-1))) $datas[$mergeKey.'-'.strtoupper(chr(ord($key)-1))] =  array_values($mergeArr);
					else $datas[$mergeKey] =  array_values($mergeArr);
					$mergeKey = strtoupper(chr(ord(strtoupper($key))+1));
					$mergeCount = 0;
					$mergeArr = array();
					$datas[strtoupper($key)] = array_values($_data);
				}
			}else{
				$datas['其它'] = array_values($_data);
			}
		}		
		if(preg_match('/[a-zA-Z]/',$mergeKey)){
			if($mergeKey !="Z") $datas[$mergeKey.'-Z'] =  $mergeArr;
			else $datas[$mergeKey] =  $mergeArr;
		}		
		ksort($datas);
		return $datas;
	}
	public function authCodeAction(){
		$refer = $_SERVER['HTTP_REFERER'];
		$authCode = $this->post('authCode', '', 'trim');
		if(!$authCode) show_message('授权码不能为空!');
		$_Authcode = new AuthcodeModel();
		$info = $_Authcode->getRow(array('date'=>date('Y-m')));
		if(!$info) show_message('授权码未设置，请联系客服!');
		if($authCode != $info['authCode']) show_message('授权码e错误!');
		$this->user['authCode'] = $authCode;
		Yaf_Session::getInstance()->set('user',$this->user);
		show_message('验证成功!',$refer);
	}
	
	public function checkEventLockAction(){
		$event = $this->post('event', 0, 'intval');
		if(!$event) exit("fail");
		$_Event = new EventModel();
		$info = $_Event->getRow(array('id'=>$event),'lock');
		if($info['lock'] == 2){
			$num = $_Event->getCount(array('pid'=>$event,'status'=>0));
			exit("$num");
		}elseif($info['lock'] == 1){
			exit("0");
		}else{
			exit("success");
		}
	}
}
