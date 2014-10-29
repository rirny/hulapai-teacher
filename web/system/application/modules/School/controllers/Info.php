<?php
/**
 * 机构信息
 */
class InfoController extends Yaf_Controller_Base_Abstract {
	/**
	 * 信息
	 */
	public function indexAction(){
		$_School = new SchoolModel();
		$school = $_School->getRow(array('id'=>$this->school));
		$schools = $_School->getAll("pid = 0 and id != $this->school");
		$timestamp = time();
		$this->getView()->assign('timestamp', $timestamp);
		$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
		$this->getView()->assign('id', $this->school);
		$this->getView()->assign('school', $school);
		$this->getView()->assign('schools', $schools);
	}
	
	public function editAction(){
		if(!$this->_POST['info'])  show_message('参数错误！');
		$_School = new SchoolModel();
		$school = $_School->getRow(array('id'=>$this->school));
		if(!$school) show_message('机构不存在！');
		$data = array(
			'name'=>$this->_POST['info']['name'],
			'type'=>$this->_POST['info']['type'],
			'province'=>$this->_POST['info']['province'],
			'city'=>$this->_POST['info']['city'],
			'area'=>$this->_POST['info']['area'],
			'address'=>$this->_POST['info']['address'],
			'contact'=>$this->_POST['info']['contact'],
			'phone'=>$this->_POST['info']['phone'],
			'phone2'=>$this->_POST['info']['phone2'],
			'description'=>$this->_POST['info']['description'],
			'operator'=>$this->uid
		);
		if(!$_School->updateData($data,array('id'=>$this->school))) show_message('修改失败！');
		show_message('修改成功！',url('school','info'));
	}
}