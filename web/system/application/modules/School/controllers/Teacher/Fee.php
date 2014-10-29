<?php
/**
 * 老师薪酬
 */
class Teacher_FeeController extends Yaf_Controller_Base_Abstract {
	/**
	 * 列表
	 */
	public function indexAction(){
		
	}
	
	public function addAction(){
		$teacher = $this->get('teacher',0,'intval');
		$text = $this->get('text','','trim');
		if(!$teacher) show_message('缺少老师id！');
		if($this->_POST){
			$text = $this->post('text','','trim');
			$total = $this->post('total',0,'intval');
			$fee = $this->post('fee',0,'intval');
			if(!$text || !$total || !$fee) show_message('参数错误！');
			$_Teacher_Fee = new Teacher_FeeModel();
			if($_Teacher_Fee->getRow(array('type'=>1,'to'=>$this->school,'teacher'=>$teacher,'text'=>$text)))   show_message('配置已存在！');
			$data = array(
				'teacher'=>$teacher,
				'type'=>1,
				'to'=>$this->school,
				'text'=>$text,
				'total'=>$total,
				'fee'=>$fee,
				'create_time'=>time(),
			);
			if(!$_Teacher_Fee->insertData($data))    show_message('配置添加失败！');
			show_message('配置添加成功！','','addTotalCfg');
		}else{
			$total = 0;
			if($text){
				$_Event = new EventModel();
				$total = $_Event->getCount(array("text"=>$text));
			}
			$this->getView()->assign('total', $total);
		}		
	}
	
	public function editAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('缺少老师id！');
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$text = $this->post('text','','trim');
			$total = $this->post('total',0,'intval');
			$fee = $this->post('fee',0,'intval');
			if(!$text || !$id || !$total || !$fee) show_message('参数错误！');
			$_Teacher_Fee = new Teacher_FeeModel();
			$info = $_Teacher_Fee->getRow(array('id'=>$id,'type'=>1,'teacher'=>$teacher,'to'=>$this->school));
			if(!$info)   show_message('配置不存在！');
			$data = array(
				'text'=>$text,
				'total'=>$total,
				'fee'=>$fee
			);
			if(!$_Teacher_Fee->updateData($data,array('id'=>$id,'type'=>1,'teacher'=>$teacher,'to'=>$this->school)))    show_message('配置修改失败！');
			show_message('配置修改成功！','','editTotalCfg');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Teacher_Fee = new Teacher_FeeModel();
			$info = $_Teacher_Fee->getRow(array('id'=>$id,'type'=>1,'teacher'=>$teacher,'to'=>$this->school));
			if(!$info)   show_message('配置不存在！');
			$this->getView()->assign('info', $info);
		}	
	}
	
	public function deleteAction(){
		$teacher = $this->get('teacher',0,'intval');
		if(!$teacher) show_message('缺少老师id！');
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('缺少id！');
		$_Teacher_Fee = new Teacher_FeeModel();
		$info = $_Teacher_Fee->getRow(array('id'=>$id,'type'=>1,'teacher'=>$teacher,'to'=>$this->school));
		if(!$info) show_message('配置不存在！');
		if(!$_Teacher_Fee->deleteData(array('id'=>$id))) show_message('重置失败！');
		show_message('重置成功！',url('school','teacher','info','teacher='.$teacher.'&act=5'));
	}
}