<?php
/**
 * 首页
 */
class IndexController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		
		$_AdminMenu = new Admin_MenuModel();
		$topMenus = $_AdminMenu->getAll(array('type'=>'school','pid'=>0,'display'=>1),'*','sort asc,id desc');
		if($this->user['enable'] != '*'){
			$enable = explode(',',$this->user['enable']);
			foreach($topMenus as $key=>$topMenu){
				if(!in_array($topMenu['id'],$enable)){
					unset($topMenus[$key]);
				}
			}
		}
		$_School = new SchoolModel();
		$schoolInfo = $_School->getRow(array('id'=>$this->school));
		$this->getView()->assign('school', $schoolInfo);
		$this->getView()->assign('user', $this->user);
		$this->getView()->assign('topMenus', $topMenus);
	}
}
