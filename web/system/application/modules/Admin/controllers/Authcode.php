<?php
/**
 * 授权码
 */
class AuthcodeController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_Authcode = new AuthcodeModel();
		$lists = $_Authcode->getList($page,20,'','*','','date desc');
		$this->getView()->assign('pages', $lists['pages']);
		$this->getView()->assign('authcodes', $lists['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$authCode = $this->post('authCode','','trim');
			$date = $this->post('date',date('Y-m-d'),'isDate');
			if(!$authCode) show_message('参数错误！');
			$_Authcode = new AuthcodeModel();
			$today = date('Y-m',strtotime($date));
			$info = $_Authcode->getRow(array('date'=>$today));
			if($info) show_message('本月的authCode已存在，无法重复添加！');
			$data = array(
				'authCode'=>$authCode,
				'date'=>$today,
			);
			if(!$_Authcode->insertData($data)) show_message('添加失败！');
			show_message('添加成功！',url('admin','authcode'));
		}
	}
	
	
	
	public function editAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$authCode = $this->post('authCode','','trim');
			if(!$id || !$authCode) show_message('参数错误！');
			$_Authcode = new AuthcodeModel();
			$info= $_Authcode->getRow(array('id'=>$id));
			if(!$info) show_message('id不存在！');
			$data = array(
				'authCode'=>$authCode,
			);
			if(!$_Authcode->updateData($data,array('id'=>$id))) show_message('修改失败！');
			show_message('修改成功！',url('admin','authcode'));
		} else {
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Authcode = new AuthcodeModel();
			$info= $_Authcode->getRow(array('id'=>$id));
			if(!$info) show_message('id不存在！');
			$this->getView()->assign('info', $info);
		}
	}
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Authcode = new AuthcodeModel();
		$info= $_Authcode->getRow(array('id'=>$id));
		if(!$info) show_message('id不存在！');
		$this->getView()->assign('info', $info);
		if(!$_Authcode->deleteData("id = $id")) show_message('删除失败！');
		show_message('删除成功！',url('admin','authcode'));
	}
}