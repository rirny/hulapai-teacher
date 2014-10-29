<?php
/**
 * 班级学生
 */
class Grade_StudentController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Grade = new GradeModel();
		$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$grade)  show_message('班级不存在！');
		//取得班级学生
		$_GradeStudent = new Grade_StudentModel();
		$students = $_GradeStudent->getList($page,20,array('school'=>$this->school,'grade'=>$id));
		if($students['data']){
			$_Student = new StudentModel();
			foreach($students['data'] as &$student){
				$studentInfo = $_Student->getRow(array('id'=>$student['student']),'name');
				$student['name'] = $studentInfo['name'];
			}
		}
		$this->getView()->assign('id', $id);
		$this->getView()->assign('pages', $students['pages']);
		$this->getView()->assign('school', $this->school);
		$this->getView()->assign('students', $students['data']);
	}
	
	public function addAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$students = $this->post('student');		
			if(!$id || !$students) show_message('参数错误！');
			$_Grade = new GradeModel();
			$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$grade)  show_message('班级不存在！');
			$_GradeStudent = new Grade_StudentModel();
			$data = array(
				'school'=>$this->school,
				'grade'=>$id,
				'creator'=>$this->uid,
				'create_time'=>time()
			);
			foreach($students as $student){
				if(!$_GradeStudent->getRow(array('school'=>$this->school,'grade'=>$id,'student'=>$student))){
					$data['student'] = $student;
					$_GradeStudent->insertData($data);
				}
			}
			show_message('学生添加成功！','','add');
		}else{
			$id = $this->get('id',0,'intval');
			$_Grade = new GradeModel();
			$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
			if(!$grade)  show_message('班级不存在！');
			//获取机构下的学生
			$_SchoolStudent= new School_StudentModel();
			$students = $_SchoolStudent->getAll(array('school'=>$this->school));
			//取得班级学生
			$_GradeStudent = new Grade_StudentModel();
			$grade_students = $_GradeStudent->getAll(array('school'=>$this->school,'grade'=>$id),'student');
			$grade_studentIds = array();
			if($grade_students){
				foreach($grade_students as &$grade_student){
					$grade_studentIds[] = $grade_student['student'];
				}
			}
			if($students){
				$_Student = new StudentModel();
				foreach($students as &$student){
					$studentInfo = $_Student->getRow(array('id'=>$student['student']),'name');
					$student['name'] = $studentInfo['name'];
					$student['in'] = 0;
					if(in_array($student['student'],$grade_studentIds)) $student['in'] = 1;
				}
			}
			
			$this->getView()->assign('id', $id);
			$this->getView()->assign('students', $students);
		}
	}
	
	public function deleteAction(){
		$id = 0;
		$students = array();
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$students = $this->post('student');
		}elseif($this->_GET){
			$id = $this->get('id',0,'intval');
			$student = $this->get('student','0','intval');
			$students = array($student);
		}
		if(!$id || !$students) show_message('参数错误！');
		$_Grade = new GradeModel();
		$grade = $_Grade->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$grade) show_message('班级不存在！');
		$students = implode(',',$students);
		$_GradeStudent = new Grade_StudentModel();
		if(!$_GradeStudent->deleteData("school = $this->school and `grade` = $id and student in ($students)")) show_message('删除失败！');
		show_message('删除成功！',url('school','grade_student','index','id='.$id));
	}
}