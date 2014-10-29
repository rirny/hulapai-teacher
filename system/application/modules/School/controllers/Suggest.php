<?php
/**
 * 意见反馈
 */
class SuggestController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$_Feedback = new FeedbackModel();
		$feedbacks = $_Feedback->getList($page,20,array('type'=>1,'school'=>$this->school,'sorts'=>0),'*','','create_time desc');
		$this->getView()->assign('pages', $feedbacks['pages']);
		$this->getView()->assign('suggests', $feedbacks['data']);
	}
	
	public function deleteAction(){
		$ids = $this->post('id', array());
		if(!$ids || empty($ids)) show_message('参数错误！');
		$_Feedback = new FeedbackModel();
		foreach($ids as $id){
			$suggest = $_Feedback->getRow(array('school'=>$this->school,'id' => $id));
			//反馈不存在！
			if(!$suggest) continue;
			//删除反馈失败
			if(!$_Feedback->deleteData(array('id'=>$id))) continue;
		}
		show_message('删除反馈成功！',url('school','suggest'));
	}
	
	public function commentAction(){
		$page = $this->get('page',1,'intval');
		$_Comment = new CommentModel();
		$datas = $_Comment->getList($page,20,array('school'=>$this->school,'event'=>0,'character !='=>'school'));
		if($datas['data']){
			$_Attach = new AttachModel();
			foreach($datas['data'] as &$data){
				$data['attachInfos'] = array();
				if($data['attach']){
					$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
				}
			}
		}
		
		$this->getView()->assign('data', $datas['data']);
		$this->getView()->assign('pages', $datas['pages']);
	}
}