<?php
/**
 * 学生缴费
 */
class Student_MoneyController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('缺少学生id！');
		//学生缴费
		$_Student_Money = new Student_MoneyModel();
		$hadMoney = $_Student_Money->getSum('money',array("student"=>$student,'school'=>$this->school));
		$datas = $_Student_Money->getList($page,5,array('student'=>$student,'school'=>$this->school),'*','','create_time desc');
		$this->getView()->assign('fees',$datas['data']);
		$this->getView()->assign('pages',$datas['pages']);
		$this->getView()->assign('hadMoney',$hadMoney);
	}
	
	public function addAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('缺少学生id！');
		if($this->_POST){
			$create_time = $this->post('create_time', date('Y-m-d H:i:s'), 'isDate','Y-m-d H:i:s');
			if(!$create_time) show_message('缴费时间不能为空且格式必须正确');
			$money = $this->post('money',0,'intval');
			if(!$money) show_message('缴费金额错误！');
			$_Student_Money = new Student_MoneyModel();
			$data = array(
				'student'=>$student,
				'school'=>$this->school,
				'money'=>$money,
				'create_time'=>strtotime($create_time),
			);
			if(!$_Student_Money->insertData($data))    show_message('缴费失败！');
			echo "<script>var d = window.top.art.dialog({id:'fee'}).data.iframe.location.reload();</script>";
			show_message('缴费成功！','','add');
		}	
	}
	
	public function editAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) how_message('缺少学生id！');
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			if(!$id) show_message('id错误！');
			$create_time = $this->post('create_time', date('Y-m-d H:i:s'), 'isDate','Y-m-d H:i:s');
			if(!$create_time) show_message('缴费时间不能为空且格式必须正确');
			$money = $this->post('money',0,'intval');
			if(!$money) show_message('缴费金额错误！');
			$_Student_Money = new Student_MoneyModel();
			$info = $_Student_Money->getRow(array('id'=>$id,'student'=>$student,'school'=>$this->school));
			if(!$info)   show_message('缴费记录不存在！');
			$data = array(
				'money'=>$money,
				'create_time'=>strtotime($create_time),
			);
			if(!$_Student_Money->updateData($data,array('id'=>$id,'student'=>$student,'school'=>$this->school)))    show_message('缴费记录修改失败！');
			echo "<script>var d = window.top.art.dialog({id:'fee'}).data.iframe.location.reload();</script>";
			show_message('缴费记录修改成功！','','edit');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Student_Money = new Student_MoneyModel();
			$info = $_Student_Money->getRow(array('id'=>$id,'student'=>$student,'school'=>$this->school));
			if(!$info)   show_message('缴费记录不存在！');
			$this->getView()->assign('info', $info);
		}	
	}
	
	public function deleteAction(){
		$student = $this->get('student',0,'intval');
		if(!$student) show_message('缺少学生id！');
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('缺少id！');
		$_Student_Money = new Student_MoneyModel();
		$info = $_Student_Money->getRow(array('id'=>$id,'student'=>$student,'school'=>$this->school));
		if(!$info) show_message('缴费记录不存在！');
		if(!$_Student_Money->deleteData(array('id'=>$id))) show_message('删除失败！');
		show_message('删除成功！',url('school','student_fee','index','student='.$student));
	}
}