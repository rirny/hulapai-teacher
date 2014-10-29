<?php
/**
 * 问卷调查
 */
class VoteController extends Yaf_Controller_Base_Abstract {
	public function infoAction() {
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Vote = new VoteModel();
		$vote = $_Vote->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$vote) show_message('问卷不存在！');
		$_VoteOption = new Vote_OptionModel();
		$options = $_VoteOption->getAll(array('vote'=>$id),'id,title','sort asc');
		//已经投过票
		$_VoteRecord = new Vote_RecordModel();
		if($_VoteRecord->getRow(array('vote'=>$id,'user'=>$this->uid,'character'=>'teacher'))){
			$data = array();
			$color = array(
				'#39c','#f00','#000'
			);
			foreach($options as $key=>$option){
				$data[] = array(
					'name'=>$option['title'],
					'data'=>$_VoteRecord->getCount(array('vote'=>$id,'option'=>$option['id'])),
					'color'=>$color[$key%3],
				);;
			}
			$this->getView()->assign('voted', 1);
			$this->getView()->assign('data', json_encode($data));
		}else{
			$this->getView()->assign('voted', 0);
		}
		$this->getView()->assign('vote', $vote);
		$this->getView()->assign('options', $options);
	}
	
	public function doAction(){
		$id = $this->post('id',0,'intval');
		$option = $this->post('option',array());
		if(!$id) show_message('参数错误！');
		if(!$option) show_message('选项错误！');
		$option = array_unique($option);
		$filterFunc = create_function('$v', 'return  is_numeric($v);');
		$optionArray = array_filter($option, $filterFunc);
		$option = implode(',',$optionArray);
		$_Vote = new VoteModel();
		$vote = $_Vote->getRow(array('school'=>$this->school,'id'=>$id));
		if(!$vote) show_message('问卷不存在！');
		if($vote['end_time'] < time() || $vote['start_time'] > time()){
			show_message('问卷已结束或未开始，无法投票！');
		}
		if(count($optionArray) > 1 && $vote['multi'] == 1){
			show_message('只能单选！');
		}
		$_VoteOption = new Vote_OptionModel();
		$optionInfo = $_VoteOption->getAll(array("id in ($option)"=>NULL,'vote'=>$id));
		if(!$optionInfo || count($optionArray) != count($optionInfo)){
			show_message('选项错误！');
		}
		$_VoteRecord = new Vote_RecordModel();
		if($_VoteRecord->getRow(array('vote'=>$id,'user'=>$this->uid,'character'=>'teacher'))){
			show_message('您已经投过票了！');
		}
		$data = array(
			'vote'=>$id,
			'user'=>$this->uid,
			'student'=>0,
			'character'=>'teacher',
			'create_time'=>date('Y-m-d H:i:s'),
			'ip'=>getIp()
		);
		if(!$_Vote->doVote($id,$data,$optionArray)) show_message('投票失败！');	
		show_message('投票成功！');
	}
}
