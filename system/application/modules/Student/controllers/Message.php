<?php
/**
 * 短消息
 */
class MessageController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$type = $this->get('type',1,'intval');
		$where = array('school'=>$this->school,'character'=>'student','student'=>$this->sid);
		if($type == 1){
			$where['type <='] = $type;
		}elseif($type == 2){
			$where['type'] = $type;
		}
		$_Message = new MessageModel();
		$datas = $_Message->getList($page,10,$where,'*','','create_time desc');
		if($datas['data']){
			$_Vote = new VoteModel();
			foreach($datas['data'] as &$message){
				$message['source'] = json_decode($message['source'],true);
				$message['attachs'] = json_decode($message['attachs'],true);
				if($type == 2 && $message['source']['id']){
					$message['source'] = $_Vote->getRow(array('id'=>$message['source']['id']));
				}
			}
			
		}
		$this->getView()->assign('pages', $datas['pages']);
		$this->getView()->assign('messages', $datas['data']);
		$this->getView()->assign('c', 'message');
		$this->getView()->assign('type', $type);
	}
}
