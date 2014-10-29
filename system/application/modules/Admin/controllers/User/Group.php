<?php
/**
 * 用户组
 */
class User_GroupController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_AdminUserGroup = new Admin_User_GroupModel();
		$groups = $_AdminUserGroup->getList($page,20);
		if($groups['data']){
			$_School = new SchoolModel();
			foreach($groups['data'] as &$group){
				if($group['type'] == 'school' && $group['school']){
					$schoolInfo = $_School->getRow(array('id'=>$group['school']),'id as school,code,name');
					if($schoolInfo){
						$group['school'] = $schoolInfo;
					}
				}
			}
		}
		$this->getView()->assign('pages', $groups['pages']);
		$this->getView()->assign('groups', $groups['data']);
	}
	/**
	 * 添加用户组
	 */
	public function addAction(){
		if($this->_POST['info']){
			$this->_POST['info']['enable'] = '';
			$_AdminUserGroup = new Admin_User_GroupModel();
			$id = $_AdminUserGroup->insertData($this->_POST['info']);
			if(!$id) show_message('添加失败！');
			show_message('添加成功！','','add');
		}
	}
	/**
	 * 修改用户组
	 */
	public function editAction(){
		if($this->_POST['info']){
			$gid = $this->post('gid',0,'intval');
			if(!$gid || $gid == 1) show_message('参数错误！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group= $_AdminUserGroup->getRow(array('gid'=>$gid));
			if(!$group) show_message('用户组不存在！');
			if(!$_AdminUserGroup->updateData($this->_POST['info'],array('gid'=>$gid))) show_message('修改失败！');
			show_message('修改成功！','','edit');
		} else {
			$gid = $this->get('gid',0,'intval');
			if(!$gid) show_message('参数错误！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group= $_AdminUserGroup->getRow(array('gid'=>$gid));
			$this->getView()->assign('group', $group);
		}
	}
	/**
	 * 删除用户组
	 */
	public function deleteAction(){
		$gid = $this->get('gid',0,'intval');
		if(!$gid) show_message('参数错误！');
		$_AdminUserGroup = new Admin_User_GroupModel();
		$group= $_AdminUserGroup->getRow(array('gid'=>$gid));
		if(!$group) show_message('用户组不存在！');
		if(!$_AdminUserGroup->deleteData("gid = $gid")) show_message('删除失败！');
		show_message('删除成功！',url('admin','user_group'));
	}
	/**
	 * 修改权限
	 */
	public function enableAction(){
		if($this->_POST['menuid']){
			$gid = $this->post('gid',0,'intval');
			if(!$gid) show_message('参数错误！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group= $_AdminUserGroup->getRow(array('gid'=>$gid));
			if(!$group) show_message('用户组不存在！');
			$data = array('enable'=>implode(',',$this->_POST['menuid']));
			if(!$_AdminUserGroup->updateData($data,array('gid'=>$gid))) show_message('修改失败！');
			show_message('修改成功！','','enable');
		} else {
			$gid = $this->get('gid',0,'intval');
			if(!$gid) show_message('参数错误！');
			$_AdminUserGroup = new Admin_User_GroupModel();
			$group= $_AdminUserGroup->getRow(array('gid'=>$gid));
			if(!$group) show_message('用户组不存在！');
			$tree = new Tree();
			$tree->icon = array('│ ','├─ ','└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$_AdminMenu = new Admin_MenuModel();
			$menus = $_AdminMenu->getAll(array('type'=>$gid == 1 ?'admin':'school'),'*','sort asc,id desc');
			foreach ($menus as $n=>$t) {
				$menus[$n]['checked'] = ($_AdminMenu->hasPriv($t['id'],$group['enable']))? ' checked' : '';
				$menus[$n]['level'] = $_AdminMenu->getLevel($t['id'],$menus);
				$menus[$n]['pid_node'] = ($t['pid'])? ' class="child-of-node-'.$t['pid'].'"' : '';
			}
			$str  = "<tr id='node-\$id' \$pid_node>
						<td style='padding-left:30px;'>\$spacer<input type='checkbox' name='menuid[]' value='\$id' level='\$level' \$checked onclick='javascript:checknode(this);'> \$name</td>
					</tr>";
			$tree->init($menus);
			$categorys = $tree->get_tree(0, $str);
			$this->getView()->assign('group', $group);
			$this->getView()->assign('categorys', $categorys);
		}
		
	}
}