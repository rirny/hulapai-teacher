<?php
/**
 * 学生资源
 */
class Student_ResourceController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$studentName = $this->get('studentName','','trim');
		$sorts = $this->get('sorts','modify_time Desc','trim');
		$studentName = $studentName != "学生名" ? $studentName:'';
		$gender = $this->get('gender',0,'intval');
		$source = $this->get('source',-1,'intval');
		$status = $this->get('status',-1,'intval');
		$_Student_Resource = new Student_ResourceModel();
		$students = $_Student_Resource->getStudentResourceList($page,20,$this->school,$studentName,$gender,$source,$status, $sorts);
		$this->getView()->assign('records', $students['records']);
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('students', $students['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$info = Array();
			$info['name'] = $this->post('name');			
			if(!$info['name']) show_message('学生名不能为空！');
			$info['school'] = $this->school;
			$info['parents'] = json_encode($this->setParent($this->post('parents')));			
			$info['birthday'] = $this->post('birthday');
			$info['source'] = $this->post('source');
			$info['create_time'] = time();
			$info['creator'] = $this->uid;
			
			$info['course'] = json_encode($this->post('course', array()));			
			$info['gender'] = $this->post('gender');			
			$info['desc'] = $this->post('desc');			
			$_Student_Resource = new Student_ResourceModel();
			if(!$_Student_Resource->insertData($info))    show_message('学生资源添加失败！');
			show_message('学生资源添加成功！', './Index');
		}
		$_Course = new CourseModel();
		$schoolCourses = $_Course->getSchoolCourseType($this->school);
		if(!$schoolCourses) show_message('请先设置授课内容！',url('school','course','index'));
		$this->getView()->assign('schoolCourses', $schoolCourses);
	}

	private function setParent($data)
	{
		$result = array();
		foreach($data['name'] as $key => $name)
		{
			$mobile = isset($data['mobile']) ? $data['mobile'][$key] : '';
			$relation = isset($data['relation']) ? $data['relation'][$key] : '';
			$result[$key] = compact('name', 'mobile', 'relation');
		}
		return $result;
	}
	
	public function editAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		if($this->_POST){
			$info = $this->post('info',array());
			$info['name'] = $this->post('name');			
			if(!$info['name']) show_message('学生名不能为空！');
			$info['school'] = $this->school;
			$info['parents'] = json_encode($this->setParent($this->post('parents')));			
			$info['birthday'] = $this->post('birthday');
			$info['source'] = $this->post('source');
			$info['create_time'] = time();
			$info['creator'] = $this->uid;			
			$info['course'] = json_encode($this->post('course', array()));			
			$info['gender'] = $this->post('gender');			
			$info['desc'] = $this->post('desc');
			$_Student_Resource = new Student_ResourceModel();
			if(!$_Student_Resource->updateData($info,array('id'=>$id,'school'=>$this->school)))    show_message('学生资源修改失败！');
			show_message('学生资源修改成功！', './Index');
		}else{
			$_Student_Resource = new Student_ResourceModel();
			$info = $_Student_Resource->getRow(array('id'=>$id,'school'=>$this->school));
			if(!$info)   show_message('学生资源不存在！');
			$info['parents'] = $info['parents'] ? json_decode($info['parents'],true):array();
			$info['course'] = $info['course'] ? json_decode($info['course'],true):array();
			$this->getView()->assign('info', $info);
			$_Course = new CourseModel();
			$schoolCourses = $_Course->getSchoolCourseType($this->school);
			if(!$schoolCourses) show_message('请先设置授课内容！',url('school','course','index'));
			$this->getView()->assign('schoolCourses', $schoolCourses);
		}	
	}
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Student_Resource = new Student_ResourceModel();
		if(!$_Student_Resource->getRow(array('id'=>$id,'school'=>$this->school)))   show_message('学生资源不存在！');
		if(!$_Student_Resource->deleteData(array('id'=>$id,'school'=>$this->school))) show_message('删除失败！');
		show_message('删除成功！',url('school','student_resource'));
	}

	public function doAction()
	{
		$ids = $this->post('id');
		is_array($ids) || $ids = explode(",", $ids);
		if(!$ids) out(0, '参数错误');	
		$action = $this->post('action', 'sign');
		$_Student_Resource = new Student_ResourceModel();
		$resource = $_Student_Resource->getAll('id in (' . implode(",", $ids) . ') And school =' . $this->school);
		if(empty($resource))   show_message('学生资源不存在！');
		$message = '操作成功！';
		if($action == 'delete' && !$_Student_Resource->deleteData('id in (' . implode(",", $ids) .') And school =' . $this->school)) Out(0, '删除失败！');		
		if($action == 'sign')
		{
			$password = md5('000000');
			foreach($resource as $student)
			{
				if($student['status'] == 1) {
					$message = '已转正！';
				}else{
					$res = $_Student_Resource->doSign($student, $this->school, $password);
					$message = '转正成功！';
				}
			}
		}
		Out(1, $message);
	}

}