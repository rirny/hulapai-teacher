<?php
/**
 * 意见反馈
 */
class SuggestController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_Feedback = new FeedbackModel();
		$feedbacks = $_Feedback->getList($page,20,array('type'=>0,'sorts'=>0),'*','','create_time desc');
		$this->getView()->assign('pages', $feedbacks['pages']);
		$this->getView()->assign('suggests', $feedbacks['data']);
	}
	
	public function deleteAction(){
		$ids = $this->post('id', array());
		if(!$ids || empty($ids)) show_message('参数错误！');
		$_Feedback = new FeedbackModel();
		foreach($ids as $id){
			$suggest = $_Feedback->getRow(array('type'=>0,'id' => $id));
			//反馈不存在！
			if(!$suggest) continue;
			//删除反馈失败
			if(!$_Feedback->deleteData(array('id'=>$id))) continue;
		}
		show_message('删除反馈成功！',url('admin','suggest'));
	}
}