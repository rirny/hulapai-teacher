<?php
/**
 * 用户
 */
class UserController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_AdminUser = new Admin_UserModel();
		$users = $_AdminUser->getList($page,20,array('type'=>'school','school'=>$this->school));
		if($users['data']){
			$_User = new UserModel();
			$_AdminUserGroup = new Admin_User_GroupModel();
			foreach($users['data'] as &$user){
				$userInfo = $_User->getRow(array('id'=>$user['uid']));
				$user['userInfo'] = $userInfo;
				$groupInfo = $_AdminUserGroup->getRow(array('gid'=>$user['gid']),'name');
				$user['group'] = '';
				if($groupInfo){
					$user['group'] = $groupInfo['name'];
				}
			
			}
		}
		$this->getView()->assign('pages', $users['pages']);
		$this->getView()->assign('users', $users['data']);
	}
	/**
	 * 添加用户
	 */
	public function addAction(){
		if($this->_POST['info']){
			$username = $this->_POST['info']['username'];
			$gid = $this->_POST['info']['gid'];
			if(!$username || !$gid || $gid == 1) show_message('参数错误！');
			$_User = new UserModel();
			$user = $_User->getRow("account = '$username' or hulaid = '$username'");
			if(!$user) show_message('用户不存在！');
			$_AdminUser = new Admin_UserModel();
			if($_AdminUser->getRow(array('uid'=>$user['id'],'gid'=>'school','school'=>$this->school))) show_message('用户已添加！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group = $_AdminUserGroup->getRow(array('gid'=>$gid));
			if(!$group) show_message('用户组不存在！');
			$data = array(
				'uid'=>$user['id'],
				'gid'=>$group['gid'],
				'type'=>'school',
				'school'=>$this->school,
				'enable'=>$group['enable'],
			);
			if(!$_AdminUser->insertData($data)) show_message('添加失败！');
			show_message('添加成功！','','add');
		}else{
			$_AdminUserGroup = new Admin_User_GroupModel();
			$groups = $_AdminUserGroup->getAll(array('school'=>$this->school));
			$this->getView()->assign('groups', $groups);
		}
	}
	
	
	/**
	 * 修改用户
	 */
	public function editAction(){
		if($this->_POST['info']){
			$id = $this->_POST['id'];
			$gid = $this->_POST['info']['gid'];
			if(!$id || !$gid || $gid <= 2) show_message('参数错误！');
			$_AdminUser = new Admin_UserModel();
			$user= $_AdminUser->getRow(array('id'=>$id));
			if(!$user) show_message('用户不存在！');
			if($user['gid'] == 2) show_message('机构超级管理员不能修改！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group = $_AdminUserGroup->getRow(array('gid'=>$gid));
			if(!$group) show_message('用户组不存在！');
			$data = array(
				'gid'=>$group['gid'],
				'enable'=>$group['enable'],
			);
			if(!$_AdminUser->updateData($data,array('id'=>$id))) show_message('修改失败！');
			show_message('修改成功！','','edit');
		} else {
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_AdminUser = new Admin_UserModel();
			$user= $_AdminUser->getRow(array('id'=>$id));
			$_User = new UserModel();
			$userInfo = $_User->getRow(array('id'=>$user['uid']));
			$user['userInfo'] = $userInfo;
			$_AdminUserGroup = new Admin_User_GroupModel();
			$groups = $_AdminUserGroup->getAll(array('school'=>$this->school));
			$this->getView()->assign('user', $user);
			$this->getView()->assign('groups', $groups);
		}
	}
	/**
	 * 删除用户
	 */
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_AdminUser = new Admin_UserModel();
		$user= $_AdminUser->getRow(array('id'=>$id));
		if(!$user) show_message('用户不存在！');
		if($user['gid'] == 2) show_message('机构超级管理员不能删除！');
		if(!$_AdminUser->deleteData("id = $id")) show_message('删除失败！');
		show_message('删除成功！',url('school','user'));
	}
	/**
	 * 修改权限
	 */
	public function enableAction(){
		if($this->_POST){
			if(!$this->_POST['menuid']) show_message('请选择权限！');
			$id = $this->post('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_AdminUser = new Admin_UserModel();
			$user= $_AdminUser->getRow(array('id'=>$id));
			if(!$user) show_message('用户不存在！');
			if($user['gid'] == 2) show_message('机构超级管理员为最高权力拥有者，拥有全部权限，不可修改！！');
			$data = array('enable'=>implode(',',$this->_POST['menuid']));
			if(!$_AdminUser->updateData($data,array('id'=>$id))) show_message('修改失败！');
			show_message('修改成功！','','enable');
		} else {
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_AdminUser = new Admin_UserModel();
			$user= $_AdminUser->getRow(array('id'=>$id));
			if(!$user) show_message('用户不存在！');
			$tree = new Tree();
			$tree->icon = array('│ ','├─ ','└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$_AdminMenu = new Admin_MenuModel();
			$menus = $_AdminMenu->getAll(array('type'=>$user['type']),'*','sort asc,id desc');
			foreach ($menus as $n=>$t) {
				$menus[$n]['checked'] = ($_AdminMenu->hasPriv($t['id'],$user['enable']))? ' checked' : '';
				$menus[$n]['level'] = $_AdminMenu->getLevel($t['id'],$menus);
				$menus[$n]['pid_node'] = ($t['pid'])? ' class="child-of-node-'.$t['pid'].'"' : '';
			}
			$str  = "<tr id='node-\$id' \$pid_node>
						<td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
					</tr>";
			$tree->init($menus);
			$categorys = $tree->get_tree(0, $str);
			$this->getView()->assign('user', $user);
			$this->getView()->assign('categorys', $categorys);
		}
		
	}
}