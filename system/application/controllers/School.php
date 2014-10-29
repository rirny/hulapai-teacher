<?php
/**
 * 机构
 */
class SchoolController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$this->checkLogin();
		$id = $this->get('id',0,'intval');
		$_AdminUser = new Admin_UserModel();
		if($id){
			$userInfo = $_AdminUser->getRow(array('id'=>$id,'uid'=>$this->uid));
			if(!$userInfo) show_message('id不存在！');
			$url = '';
			$this->user['enable'] = $userInfo['enable'];
			$this->user['gid'] = $userInfo['gid'];
			$this->user['school'] = $userInfo['school'];
			if($userInfo['type'] == "school"){
				$url = url('school');
				$this->user['module'] = "School";
			}
			if(!$url) show_message('url不存在！');
			Yaf_Session::getInstance()->set('user',$this->user);
			$this->redirect($url);
		}else{
			//判读权限
			$page = $this->get('page',1,'intval');
			$userInfos = $_AdminUser->getList($page,7,array('uid'=>$this->uid));
			$datas = array();
			if($userInfos['data']){
				$_School = new SchoolModel();
				foreach($userInfos['data'] as $key=>$userInfo){
					if($userInfo['type'] == "school" && $userInfo['school']){
						$schoolInfo = $_School->getRow(array('id'=>$userInfo['school']),'id,code,name');
						if($schoolInfo){
							$userInfo['school'] = $schoolInfo;
							$datas[] = $userInfo;
						}
					}
					
				}
			}
			$this->getView()->assign('pages', $userInfos['pages']);
			$this->getView()->assign('datas', $datas);
		}
	}
	
	/**
	 * 添加机构
	 */
	public function addAction(){
		$this->checkLogin();
		if($this->_POST['info']){
			if(!$this->_POST['info']['code'] || !$this->_POST['info']['name']) show_message('参数错误！');
			$_School = new SchoolModel();
			if($_School->getRow(array('code'=>$this->_POST['info']['code']))) show_message('机构号已存在！');
			$data = array(
				'code'=>$this->_POST['info']['code'],
				'name'=>$this->_POST['info']['name'],
				'pid'=>0,
				'type'=>$this->_POST['info']['type'],
				'province'=>$this->_POST['info']['province'],
				'city'=>$this->_POST['info']['city'],
				'area'=>$this->_POST['info']['area'],
				'address'=>$this->_POST['info']['address'],
				'contact'=>$this->_POST['info']['contact'],
				'phone'=>$this->_POST['info']['phone'],
				'phone2'=>$this->_POST['info']['phone2'],
				'description'=>$this->_POST['info']['description']
			);
			$id = $_School->addSchool($this->uid,2,$data,1);
			if (!$id){
			    show_message('添加失败！');
			}else{  
			    show_message('添加成功！',url('index','school','index','id='.$id));
			}
		}else{
			$_School = new SchoolModel();
			$schools = $_School->getAll(array('pid'=>0));
			$this->getView()->assign('schools', $schools);
			$timestamp = time();
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
			$this->getView()->assign('id', $this->school);
		}
	}
}