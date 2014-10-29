<?php
/**
 * 学生信息
 */
class InfoController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		if(!$this->_POST){
			//获取关系信息
			$_User_Student = new User_StudentModel();
			$relationInfo = $_User_Student->getRow(array('user'=>$this->uid,'student'=>$this->sid),'user,student,relation');
			$relationInfo = $relationInfo ? $relationInfo : array();
			//获取用户信息
			$_Student = new StudentModel();
			$studentInfo = $_Student->getRow(array('id'=>$this->sid));
			$studentInfo = $studentInfo ? array_merge($studentInfo,$relationInfo) : $relationInfo;
			$this->getView()->assign('student', $studentInfo);
			$timestamp = time();
			$this->getView()->assign('uid', $this->uid);
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
		}else{
			$relation = $this->post('relation',4,'intval');
			//获取关系信息
			$_User_Student = new User_StudentModel();
			$relationInfo = $_User_Student->getRow(array('student'=>$this->sid,'relation'=>$relation));
			if($relation !=4 && $relationInfo['relation'] != $relation) show_message('relation关系已存在！');
			$relationData = array(
				'relation'=>$relation,
			);
			$studentData = array(
				'name'=>$this->post('name','','trim'),
				'name_en'=>Ustring::topinyin($this->post('name','','trim')),
				'gender'=>$this->post('gender',0,'intval'),
				'operator'=>$this->uid,
				'birthday'=>$this->post('birthday','','isDate'),
			);
			$_Student = new StudentModel();
			if(!$_Student->updateStudent($this->sid,$this->uid,$relationData,$studentData)) show_message('档案更新失败！');
			$this->student['name'] = $studentData['name'];
			Yaf_Session::getInstance()->set('student',$this->student);
			show_message('档案更新成功！');
		}
	}
}
