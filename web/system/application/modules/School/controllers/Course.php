<?php
/**
 * 教学范围
 */
class CourseController extends Yaf_Controller_Base_Abstract {
	
	/**
	 * 信息
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_Course = new CourseModel();
		$courseList = $_Course->getList($page,20,array('school'=>$this->school));
		if($courseList['data']){
			$_CourseType = new Course_TypeModel();
			foreach($courseList['data'] as &$courseInfo){
				$courseType = $_CourseType->getRow(array('id'=>$courseInfo['type']));
				$courseInfo['typeName'] = $courseType['name'];
				if($courseType['pid']){
					$_courseType = $_CourseType->getRow(array('id'=>$courseType['pid']));
					//$courseInfo['typeName'] = $_courseType['name'].'|'.$courseInfo['typeName'];
					$courseInfo['typeName'] = $_courseType['name'];
				}
			}
		}
		$this->getView()->assign('courseList', $courseList['data']);
		$this->getView()->assign('pages', $courseList['pages']);
	}
	
	public function addAction(){
		if($this->_POST){
			$type = $this->post('type',0,'intval');
			$title = $this->post('title','','trim');
			$title == "请直接输入，6个字以内" && $title = "";
			if(!$type || !$title) show_message('参数错误！');
			$_Course = new CourseModel();
			$_CourseType = new Course_TypeModel();
			$typeInfo = $_CourseType->getRow(array('id'=>$type),'name');
			if($typeInfo['name'] !="其他" && $_Course->getRow(array('school'=>$this->school,'type'=>$type)))   show_message('分类已添加！');
			$data = array(
				'type'=>$type,
				'title'=>$title,
				'school'=>$this->school,
				'creator'=>$this->uid,
				'create_time'=>time(),
			);
			if(!$_Course->insertData($data))    show_message('分类添加失败！');
			else show_message('分类添加成功！','','add');
		}	
	
	}
	
	public function editAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$type = $this->post('type',0,'intval');
			$title = $this->post('title','','trim');
			if(!$id || !$type || !$title) show_message('参数错误！');
			$_Course = new CourseModel();
			$courseInfo = $_Course->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$courseInfo)  show_message('id不存在！');
			if($type != $courseInfo['type']){
				$_CourseType = new Course_TypeModel();
				$typeInfo = $_CourseType->getRow(array('id'=>$type),'name');
				if($typeInfo['name'] !="其他" && $_Course->getRow(array('school'=>$this->school,'type'=>$type)))   show_message('分类已添加！');
			}
			$data = array(
				'type'=>$type,
				'title'=>$title,
				'operator'=>$this->uid,
			);
			if(!$_Course->updateData($data,array('school'=>$this->school,'id'=>$id)))    show_message('分类修改失败！');
			show_message('分类修改成功！','','edit');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Course = new CourseModel();
			$courseInfo = $_Course->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$courseInfo) show_message('id不存在！');
			$_CourseType = new Course_TypeModel();
			$courseType = $_CourseType->getRow(array('id'=>$courseInfo['type']));
			$ptype = 0;
			if($courseType['pid']){
				$_courseType = $_CourseType->getRow(array('id'=>$courseType['pid']));
				$ptype = $_courseType['id'];
			}
			$this->getView()->assign('ptype', $ptype ? $ptype:$courseType['id']);
			$this->getView()->assign('type', $ptype ? $courseType['id']:0);
			$this->getView()->assign('courseInfo', $courseInfo);
		}	
	
	}
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Course = new CourseModel();
		$courseInfo = $_Course->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$courseInfo) show_message('分类不存在！');
		if(!$_Course->deleteData("id = $id")) show_message('删除失败！');
		show_message('删除成功！',url('school','course'));
	}
}