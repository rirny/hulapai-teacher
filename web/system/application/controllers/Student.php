<?php
/**
 * 学生
 */
class StudentController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$this->checkLogin();
		$sid = $this->get('id',0,'intval');
		$_Student = new StudentModel();
		if($sid){
			//验证账号相关联的学生
			$_User_Student = new User_StudentModel();
			if(!$_User_Student->getRow(array('student'=>$sid,'user'=>$this->uid))) show_message('该学生和您没有任何关联！');
			$_Student = new StudentModel();
			$studentInfo = $_Student->getRow(array('id'=>$sid,'source'=>0,'status'=>0));
			if(!$studentInfo) show_message('学生不存在！');
			$this->user['module'] = "Student";
			Yaf_Session::getInstance()->set('user',$this->user);
			Yaf_Session::getInstance()->set('student',$studentInfo);
			$url = $this->get('act',url('student','index','index'),'trim');
			$this->redirect($url);
		}else{
			$page = $this->get('page',1,'intval');
			//获取账号相关联的学生
			$_User_Student = new User_StudentModel();
			$students = $_User_Student->getList($page,7,array('user'=>$this->uid));
			$datas = array();
			if($students['data']){
				$_Student = new StudentModel();
				foreach($students['data'] as $key=>&$student){
					$studentInfo = $_Student->getRow(array('id'=>$student['student'], 'status'=>0));
					if($studentInfo){
						$student = array_merge($student,$studentInfo);
					}else{
						unset($students['data'][$key]);
					}
				}
			}
			$this->getView()->assign('pages', $students['pages']);
			$this->getView()->assign('datas', $students['data']);
		}
	}
	
	/**
	 * 添加学生
	 */
	public function addAction(){
		$this->checkLogin();
		if($this->_POST){
			$relation = $this->post('relation',4,'intval');
			//获取关系信息
			$_User_Student = new User_StudentModel();
			//if($relation !=4 && $_User_Student->getRow(array('user'=>$this->uid,'relation'=>$relation))) show_message('relation关系已存在！');
			//判读档案个数
			if($_User_Student->getCount(array('user'=>$this->uid)) >= 3) show_message('学生档案最多3个！');
			$relationData = array(
				'relation'=>$relation,
				'user'=>$this->uid,
				'create_time'=>time(),
			);
			$studentData = array(
				'name'=>$this->post('name','','trim'),
				'name_en'=>Ustring::topinyin($this->post('name','','trim')),
				'gender'=>$this->post('gender',0,'intval'),
				'birthday'=>$this->post('birthday','','isDate'),
				'creator'=>$this->uid,
				'create_time'=>time(),
			);
			$_Student = new StudentModel();
			$id = $_Student->addStudent($relationData,$studentData);
			if (!$id){
			    show_message('档案添加失败！');
			}else{  
			    show_message('档案添加成功！',url('index','student','index','id='.$id));
			}
		}
	}
}