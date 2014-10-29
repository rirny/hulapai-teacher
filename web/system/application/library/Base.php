<?php
Yaf_Loader::import('Base/Fun.php');
Yaf_Loader::import('Base/Cache.php');
class Yaf_Controller_Base_Abstract extends Yaf_Controller_Abstract{
	public $wap = 0;
	public $user = array();
	public $uid = 0;
	public $gid = 0;
	public $school = 0;
	public $tid = 0;
	public $student = array();
	public $sid = 0;
	public $module = '';
	public $controller = '';
	public $action = '';
	public $_POST = array();
	public $_GET = array();
	public $path = '';
	public function init(){
		$this->wap = isMobile() ? 1 : 0;
		$this->module = $this->getModuleName();
		$this->controller = $this->getRequest()->getControllerName();
		$this->action = $this->getRequest()->getActionName();
		$this->user = Yaf_Session::getInstance()->get('user');
		if($this->user){
			$this->uid = $this->user['uid'] ? intval($this->user['uid']) : 0;
			$this->gid = $this->user['gid'] ? intval($this->user['gid']) : 0;
			$this->school = $this->user['school'] ? intval($this->user['school']) : 0;
			$this->tid = $this->uid;
		}
		$this->student = Yaf_Session::getInstance()->get('student');
		if($this->student){
			$this->sid = $this->student['id'] ? intval($this->student['id']) : 0;
		}
		if($this->module != "Index"){
			$this->getView()->setScriptPath(APPLICATION_PATH ."/application/modules/".$this->module."/views");
			$this->checkLogin();
		}
		if(DOMAIN != "common" && $this->module != ucfirst(DOMAIN) && $this->module != 'Index'){	
			$this->redirect('/User');
		}
		if($this->module == "Admin"){
			$this->checkAdmin();
			$this->checkAuth();
		}elseif($this->module == "School"){
			$this->checkSchool();
			$this->checkAuth();
		}elseif($this->module == "Teacher"){
			$this->checkTeacher();
		}elseif($this->module == "Student"){
			$this->checkStudent();
		}
		$this->_POST = $this->getRequest()->getPost();
		//$this->_POST && $this->_POST = new_addslashes($this->_POST);
		$this->_GET = $this->getRequest()->getQuery();
		//$this->_GET && $this->_GET = new_addslashes($this->_GET);
		$this->path = Yaf_Registry::get('config')->path;
		$this->getView()->assign('wap', $this->wap);
		$this->getView()->assign('path', $this->path);
		$this->getView()->assign('_GET', $this->_GET);
		$this->getView()->assign('USER', $this->user);
		$this->getView()->assign('STUDENT', $this->student);
		$this->getView()->assign('MENU', Yaf_Registry::get('config')->main->menu->toArray());
	}
	public function checkLogin(){
		if(!$this->uid){
			Yaf_Session::getInstance()->del('user');
			echo "<script>window.top.location.href='/Login';</script>";
			exit;	
		}
	}
	
	public function checkAdmin(){
		if($this->uid && $this->module == $this->user['module']){
			return true;
		}else{
			$this->redirect('/User');
		}
	}
	
	public function checkTeacher(){
		if($this->tid && $this->module == $this->user['module']){
			//获取老师机构
			$_School_Teacher = new School_TeacherModel();
			$teacherSchools = $_School_Teacher->getAll(array("teacher"=>$this->tid),'school');
			$schools = array();
			$currentSchoolName = "";
			if($teacherSchools){
				$_School = new SchoolModel();
				foreach($teacherSchools as $key=>$teacherSchool){
					$schools[$key] = $_School->getRow(array('id'=>$teacherSchool['school']),'id,name');
					if($this->school && $teacherSchool['school'] == $this->school){
						$currentSchoolName = $schools[$key]['name'];
					}
				}
			}
			if(!$currentSchoolName){
				$currentSchoolName = $schools[0]['name'];
				$this->user['school'] = $schools[0]['id'];
				Yaf_Session::getInstance()->set('user',$this->user);
				$this->school = $this->user['school'];
			}
			$this->getView()->assign('currentSchoolName', $currentSchoolName);
			$this->getView()->assign('schools', $schools);
			//获取老师消息
			$this->getView()->assign('messageNum', 0);
			if($this->controller != 'Info' && !$this->user['teacher']) show_message('请先完善老师档案！',url('teacher','info'));
			if(!$this->school && !in_array($this->controller,array('Index','Course','Info','Message','Apply'))) show_message('没有机构，无法访问！',url('teacher','index'));
			return true;
		}else{
			$this->redirect('/User');
		}
	}
	
	public function checkStudent(){
		if($this->sid && $this->module == $this->user['module']){
			//获取学生机构
			$_School_Student= new School_StudentModel();
			$studentSchools = $_School_Student->getAll(array("student"=>$this->sid),'school');
			$schools = array();
			$currentSchoolName = "";
			if($studentSchools){
				$_School = new SchoolModel();
				foreach($studentSchools as $key=>$studentSchool){
					$schools[] = $_School->getRow(array('id'=>$studentSchool['school']),'id,name');
					if($this->school && $studentSchool['school'] == $this->school){
						$currentSchoolName = $schools[$key]['name'];
					}
				}
			}
			if(!$currentSchoolName){
				$currentSchoolName = $schools[0]['name'];
				$this->user['school'] = $schools[0]['id'];
				Yaf_Session::getInstance()->set('user',$this->user);
				$this->school = $this->user['school'];
			}
			$this->getView()->assign('currentSchoolName', $currentSchoolName);
			$this->getView()->assign('schools', $schools);
			//获取学生消息
			$this->getView()->assign('messageNum', 0);
			if(!$this->school && !in_array($this->controller,array('Index','Info','Message','Apply'))) show_message('没有机构，无法访问！',url('student','index'));
			return true;
		}else{
			$this->redirect('/User');
		}
	}
	
	public function checkSchool(){
		if($this->uid && $this->school && ($this->module == $this->user['module'] || $this->user['module'] == 'Admin')){
			return true;
		}else{
			$this->redirect('/User');
		}
	}
	
	public function checkAuth(){
		//验证权限
		$_AdminMenu = new Admin_MenuModel();
		$menu = $_AdminMenu->getRow(array('module'=>$this->module,'controller'=>$this->controller,"action like '$this->action%'"=>NULL));
		if($menu){
			//验证权限
			if(!$_AdminMenu->hasPriv($menu['id'],$this->user['enable'])){
				show_message('没有权限');
			}
		}else{
			if($this->module != "Index" && !in_array($this->controller,array('Index','Welcome'))){
				show_message('非法访问','/User');
			}
		}
	}
	
	public function get($key='',$default='',$func='',$pargm=''){
		$val = $this->getRequest()->getQuery($key,$default);
		if(function_exists($func)){
			if($pargm) $val = $func($val,$pargm);
			else $val = $func($val);
		}
		$this->_GET[$key] = $val;
		return $val;
	}
	
	public function post($key='',$default='',$func='',$pargm=''){
		$val = $this->getRequest()->getPost($key,$default);
		if(function_exists($func)){
			if($pargm) $val = $func($val,$pargm);
			else $val = $func($val);
		}
		$this->_POST[$key] = $val;
		return $val;
	}
}