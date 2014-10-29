<?php
/**
 * 菜单
 */
class MenuController extends Yaf_Controller_Base_Abstract {
	public $_menuTypes = array(
		'admin'=>'系统',
		'school'=>'机构'
	);
	/**
	 * 列表
	 */
	public function indexAction() {
		$type = $this->get('type','admin','trim');
		$tree = new Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$_AdminMenu = new Admin_MenuModel();
		$menus = $_AdminMenu->getAll(array('type'=>$type),'*','sort asc,id desc');
		$array = array();
		foreach($menus as $r) {
			$r['str_manage'] = "<a href='/admin/menu/add?type=$type&id=".$r['id']."'>添加子菜单</a> | <a href='/admin/menu/edit?id=".$r['id']."'>修改</a> | <a href='javascript:confirmurl(\"/admin/menu/delete?id=".$r['id']."\",\"确认删除 『 ".$r['name']." 』 吗？\")'>删除</a>";
			$array[] = $r;
		}
		$tree->init($array);
		$str  = "<tr>
					<td align='center'><input name='sort[\$id]' type='text' size='3' value='\$sort' class='input-text-c'></td>
					<td align='center'>\$id</td>
					<td >\$spacer\$name</td>
					<td align='center'>\$str_manage</td>
				</tr>";
		$categorys = $tree->get_tree(0, $str);
		$this->getView()->assign('categorys', $categorys);
		$this->getView()->assign('type', $type);
		$this->getView()->assign('menuTypes', $this->_menuTypes);
	}
	
	/**
	 * 新增
	 */
	public function addAction() {
		$type = $this->get('type','admin','trim');
		if($this->_POST['info']){
			$_AdminMenu = new Admin_MenuModel();
			$this->_POST['info']['type'] = $this->_POST['info']['type'] ? strtolower(trim($this->_POST['info']['type'])) : 'school';
			$this->_POST['info']['module'] = $this->_POST['info']['module'] ? ucfirst(trim($this->_POST['info']['module'])) : '';
			$this->_POST['info']['controller'] = $this->_POST['info']['controller'] ? ucfirst(trim($this->_POST['info']['controller'])) : '';
			$this->_POST['info']['action'] = $this->_POST['info']['action'] ? lcfirst(trim($this->_POST['info']['action'])) :  '';
			if($this->_POST['info']['controller']){
				$controllerArr = explode('_',$this->_POST['info']['controller']);
				$controllerArr = array_map('ucfirst',$controllerArr);
				$this->_POST['info']['controller'] = implode('_',$controllerArr);
			}
			$id = $_AdminMenu->insertData($this->_POST['info']);
			if(!$id) show_message('添加失败！');
			show_message('添加成功！',url('admin','menu','index','type='.$this->_POST['info']['type']));
		} else {
			$tree = new Tree();
			$_AdminMenu = new Admin_MenuModel();
			$menus = $_AdminMenu->getAll(array('type'=>$type),'*','sort asc,id desc');
			$array = array();
			foreach($menus as $r) {
				$r['selected'] = $r['id'] == $this->_GET['id'] ? 'selected' : '';
				$array[] = $r;
			}
			$str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
			$tree->init($array);
			$select_categorys = $tree->get_tree(0, $str);
			$this->getView()->assign('select_categorys', $select_categorys);
			$this->getView()->assign('menuTypes', $this->_menuTypes);
			$this->getView()->assign('type', $type);
		}
	}
	
	/**
	 * 修改
	 */
	public function editAction() {
		if($this->_POST['info']){
			$id = $this->post('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_AdminMenu = new Admin_MenuModel();
			$menu = $_AdminMenu->getRow(array('id'=>$id));
			if(!$menu) show_message('模块不存在！');
			$this->_POST['info']['type'] = $this->_POST['info']['type'] ? strtolower(trim($this->_POST['info']['type'])) : 'school';
			$this->_POST['info']['module'] = $this->_POST['info']['module'] ? ucfirst(trim($this->_POST['info']['module'])) : '';
			$this->_POST['info']['controller'] = $this->_POST['info']['controller'] ? ucfirst(trim($this->_POST['info']['controller'])) : '';
			$this->_POST['info']['action'] = $this->_POST['info']['action'] ? lcfirst(trim($this->_POST['info']['action'])) :  '';
			if($this->_POST['info']['controller']){
				$controllerArr = explode('_',$this->_POST['info']['controller']);
				$controllerArr = array_map('ucfirst',$controllerArr);
				$this->_POST['info']['controller'] = implode('_',$controllerArr);
			}
			if(!$_AdminMenu->updateData($this->_POST['info'],array('id'=>$id))) show_message('修改失败！');
			show_message('修改成功！',url('admin','menu','index','type='.$menu['type']));
		} else {
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_AdminMenu = new Admin_MenuModel();
			$menu = $_AdminMenu->getRow(array('id'=>$id));
			extract($menu);
			$tree = new Tree();
			$menus = $_AdminMenu->getAll(array('type'=>$type),'*','sort asc,id desc');
			$array = array();
			foreach($menus as $r) {
				$r['selected'] = $r['id'] == $pid ? 'selected' : '';
				$array[] = $r;
			}
			$str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
			$tree->init($array);
			$select_categorys = $tree->get_tree(0, $str);
			$this->getView()->assign('menu', $menu);
			$this->getView()->assign('select_categorys', $select_categorys);
			$this->getView()->assign('menuTypes', $this->_menuTypes);
			$this->getView()->assign('type', $type);
		}
	}
	
	/**
	 * 删除
	 */
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_AdminMenu = new Admin_MenuModel();
		$menu = $_AdminMenu->getRow(array('id'=>$id));
		if(!$menu) show_message('模块不存在！');
		if(!$_AdminMenu->deleteData("id = $id or pid = $id")) show_message('删除失败！');
		show_message('删除成功！',url('admin','menu','index','type='.$menu['type']));
	}
	
	/**
	 * 排序
	 */
	function sortAction() {
		if(isset($this->_POST['sort'])) {
			$_AdminMenu = new Admin_MenuModel();
			foreach($this->_POST['sort'] as $id => $sort) {
				$_AdminMenu->updateData(array('sort'=>$sort),array('id'=>$id));
			}
			show_message('更新成功！',url('admin','menu'));
		} else {
			show_message('更新失败！');
		}
	}
	
}
