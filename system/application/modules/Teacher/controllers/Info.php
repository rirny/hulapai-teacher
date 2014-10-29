<?php
/**
 * 老师信息
 */
class InfoController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		if(!$this->_POST){
			//获取用户信息
			$_User = new UserModel();
			$userInfo = $_User->getRow(array('id'=>$this->uid),'firstname,lastname');
			$userInfo = $userInfo ? $userInfo : array();
			//获取老师信息
			$_Teacher = new TeacherModel();
			$teacherInfo = $_Teacher->getRow(array('user'=>$this->uid));
			if($teacherInfo){
				$teacherInfo = array_merge($teacherInfo,$userInfo);
			}else{
				$teacherInfo = $userInfo;
				$teacherInfo['user'] = $this->uid;
			}
			$this->getView()->assign('teacher', $teacherInfo);
			$timestamp = time();
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
		}else{
			$_Teacher = new TeacherModel();
			$teacherInfo = $_Teacher->getRow(array('user'=>$this->uid));
			$userData = array(
				'firstname'=>$this->_POST['info']['firstname'],
				'firstname_en'=>Ustring::topinyin($this->_POST['info']['firstname']),
				'lastname'=>$this->_POST['info']['lastname'],
				'lastname_en'=>Ustring::topinyin($this->_POST['info']['lastname']),
			);
			$teacherData = $this->_POST['info'];
			$teacherData['user'] = $this->uid;
			unset($teacherData['firstname']);
			unset($teacherData['lastname']);
			unset($teacherData['firstname_en']);
			unset($teacherData['lastname_en']);
			if(!$teacherInfo){
				$teacherData['create_time'] = time();
				if(!$_Teacher->addTeacher($this->uid,$userData,$teacherData))  show_message('档案更新失败！');
				$this->user['teacher'] = 1;
			}else{
				if(!$_Teacher->updateTeacher($this->uid,$userData,$teacherData)) show_message('档案更新失败！');
			}
			$this->user['firstname'] = $userData['firstname'];
			$this->user['lastname'] = $userData['lastname'];
			Yaf_Session::getInstance()->set('user',$this->user);
			show_message('档案更新成功！');
		}
	}
}
