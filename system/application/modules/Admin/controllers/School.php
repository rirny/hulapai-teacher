<?php
/**
 * 机构
 */
class SchoolController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$pagesize = 20;
		$_School = new SchoolModel();
		$schools = $_School->getList($page,20);
		if($schools['data']){
			$_School_Teacher = new School_TeacherModel();
			$_School_Student = new School_StudentModel();
			foreach($schools['data'] as &$school){
				if($school['pid']){
					$pschool = $_School->getRow(array('id'=>$school['pid']));
					$school['pname']= $pschool ? $pschool['name']: "无";
				}else{
					$school['pname']= '无';
				}
				$school['teacherNums'] = $_School_Teacher->getCount(array('school'=>$school['id']));
				$school['studentNums'] = $_School_Student->getCount(array('school'=>$school['id']));
				
			}
		}
		$this->getView()->assign('pages', $schools['pages']);
		$this->getView()->assign('schools', $schools['data']);
	}
	/**
	 * 添加机构
	 */
	public function addAction(){
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
			if (!$_School->addSchool($this->uid,$this->gid,$data)){
			    show_message('添加失败！');
			}else{  
			    show_message('添加成功！');
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
	
	
	/**
	 * 修改机构
	 */
	public function editAction(){
		if($this->_POST['info']){
			$id = $this->post('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_School = new SchoolModel();
			$school = $_School->getRow(array('id'=>$id));
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
				'operator'=>$this->_POST['uid']
			);
			if(!$_School->updateData($data,array('id'=>$id))) show_message('修改失败！');
			show_message('修改成功！');
		} else {
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_School = new SchoolModel();
			$school = $_School->getRow(array('id'=>$id));
			$schools = $_School->getAll("pid = 0 and id != $id");
			$this->getView()->assign('school', $school);
			$this->getView()->assign('schools', $schools);
			$timestamp = time();
			$this->getView()->assign('timestamp', $timestamp);
			$this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp));
			$this->getView()->assign('id', $id);
		}
	}
	/**
	 * 删除机构
	 */
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_School = new SchoolModel();
		$school = $_School->getRow(array('id'=>$id));
		if(!$school) show_message('机构不存在！');
		if(!$_School->deleteSchool($id)) show_message('删除失败！');
		show_message('删除成功！',url('admin','school'));
	}
	
	public function loginAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) exit("-1");
		$_School = new SchoolModel();
		$school = $_School->getRow(array('id'=>$id));
		if(!$school) exit("-1");
		$this->user['school'] = $id;
		Yaf_Session::getInstance()->set('user',$this->user);
		exit("1");
	}
}