<?php
/**
 * 问卷
 */
class VoteController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$title = $this->get('title','','trim');
		$title = $title != "问题" ? $title:'';
		$where = array('school'=>0);
		if($title){
			$where["title like '%$title%'"]= NULL;
		}
		$_Vote = new VoteModel();
		$votes = $_Vote->getList($page,20,$where,'*','','id desc');
		$this->getView()->assign('votes', $votes['data']);
		$this->getView()->assign('pages', $votes['pages']);
	}
	
	/**
	 * 添加问卷
	 */
	public function addAction() {
		$multis = array('1'=>'单选','2'=>'多选');
		if($this->_POST){
			$title = $this->post('title','','trim');
			if(!$title) show_message('参数title错误！');
			$multi = $this->post('multi',0,'intval');
			if(!$multi || !in_array($multi,array(1,2))) show_message('参数multi错误！');
			$start_time = $this->post('start_time', '');
			$end_time = $this->post('end_time', '');
			if($start_time >= $end_time) show_message('参数end_time必须大于参数start_time！');
			$option = $this->post('option','');
			if(!$option) show_message('option错误');
			$option = array_map('trim',array_filter($option));
			if(!$option) show_message('option错误');
			
			$_Vote = new VoteModel();
			$data = array(
				'title'=>$title,
				'multi'=>$multi,
				'school'=>0,
				'creator'=>$this->uid,
				'start_time'=>strtotime($start_time),
				'end_time'=>strtotime($end_time),
				'create_time'=>time()
			);
			if(!$_Vote->addVote($data,$option))   show_message('问卷添加失败！');
			show_message('问卷添加成功！','','add');
		}else{
			$this->getView()->assign('multis', $multis);
		}	
	}
	
	public function editAction(){
		$multis = array('1'=>'单选','2'=>'多选');
		$id = $this->_GET['id'] ? $this->get('id',0,'intval'):$this->post('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Vote = new VoteModel();
		$vote = $_Vote->getRow(array('school'=>0,'id'=>$id));
		if(!$vote) show_message('问卷不存在！');
		$_VoteOption = new Vote_OptionModel();
		$options = $_VoteOption->getAll(array('vote'=>$id),'*','sort asc');
		if($this->_POST){
			$title = $this->post('title','','trim');
			if(!$title) show_message('参数title错误！');
			$multi = $this->post('multi',0,'intval');
			if(!$multi || !in_array($multi,array(1,2))) show_message('参数multi错误！');
			$start_time = $this->post('start_time', '');
			$end_time = $this->post('end_time', '');
			if($start_time >= $end_time) show_message('参数end_time必须大于参数start_time！');
			$option = $this->post('option','');
			if(!$option) show_message('option错误');
			$option = array_map('trim',array_filter($option));
			if(!$option) show_message('option错误');
			
			$_Vote = new VoteModel();
			$data = array(
				'title'=>$title,
				'multi'=>$multi,
				'start_time'=>strtotime($start_time),
				'end_time'=>strtotime($end_time)
			);
			if(!$_Vote->updateVote($id,$data,$option))   show_message('问卷修改失败！');
			show_message('问卷修改成功！','','edit');
		}else{
			$this->getView()->assign('vote', $vote);
			$this->getView()->assign('options', $options);
			$this->getView()->assign('multis', $multis);
		}	
	}
	
	
	public function deleteAction(){
		$id = $this->get('id',0,'intval');
		if(!$id) show_message('参数错误！');
		$_Vote = new VoteModel();
		$vote = $_Vote->getRow(array('school'=>0,'id'=>$id));
		if(!$vote) show_message('问卷不存在！');
		if(!$_Vote->deleteVote($id)) show_message('删除失败！');
		show_message('删除成功！',url('admin','vote'));
	}
	
	public function infoAction(){
		$id = $this->get('id',0,'intval');
		$type = $this->get('type',1,'intval');
		if(!$id || !in_array($type,array(1,2))) show_message('参数错误！');
		$_Vote = new VoteModel();
		$vote = $_Vote->getRow(array('school'=>0,'id'=>$id));
		if(!$vote) show_message('问卷不存在！');
		$_VoteOption = new Vote_OptionModel();
		$options = $_VoteOption->getAll(array('vote'=>$id),'*','sort asc');
		if($type == 1){
			$data = array();
			$_VoteRecord = new Vote_RecordModel();
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
			$this->getView()->assign('vote', $vote);
			$this->getView()->assign('data', json_encode($data));
		}elseif($type == 2){
			$page = $this->get('page',1,'intval');
			$option = $this->get('option',0,'intval');
			$where = array('vote'=>$id);
			if($option){
				$where['option'] = $option;
			}
			$_VoteRecord = new Vote_RecordModel();
			$voteRecords = $_VoteRecord->getList($page,20,$where,'*','','id desc');
			$optionDatas = array();
			foreach($options as $key=>$option){
				$optionDatas[$option['id']] = $option['title'];
			}
			$this->getView()->assign('voteRecords', $voteRecords['data']);
			$this->getView()->assign('pages', $voteRecords['pages']);
			$this->getView()->assign('optionDatas', $optionDatas);
		}
	}
}
