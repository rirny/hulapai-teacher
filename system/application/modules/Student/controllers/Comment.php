<?php
/**
 * 点评信息
 */
class CommentController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$where = '';
		$commented = $this->get('commented',-1,'intval');
		
		if($commented == 0){
    		$where .= " and b.commented = 0";
    	}elseif($commented == 1){
    		$where .= " and b.commented = 1";
    	}
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where .= " and b.end_date >= '$start_date 00:00:00'";
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where .= " and b.start_date <= '$end_date 23:59:59'";
		}
		$remark = $this->get('remark','','trim');
		if($remark && $remark != "课程名称") $where .= " and a.remark like '%$remark%'";
		
		$_CourseStudent = new Course_StudentModel();
		$events = $_CourseStudent->getStudentCourseList($page,10,$this->sid,$this->school,$where);
		$this->getView()->assign('pages', $events['pages']);
		$this->getView()->assign('events', $events['data']);
	}
	
	public function replyAction(){
		$pid = $this->post('pid',0,'intval');
		$content = $this->post('content','','trim');
		if(!$pid) exit('参数错误！');
		if(!$content) exit('回复内容不能为空！');
		$attach = array();
		$_Comment = new CommentModel();
		$pInfo = $_Comment->getRow(array('id'=>$pid));
		if(!$pInfo) exit('回复的内容不存在！');
		
		$data = array(
			'creator'=>$this->uid,
			'teacher'=>$pInfo['teacher'],
			'student'=>$this->sid,
			'event'=>$pInfo['event'],
			'pid'=>$pid,
			'reply'=> 1,
			'content'=>$content,
			'school'=>$this->school,
			'attach'=>$attach ? implode(',',$attach): '',
			'character'=>'student',
			'create_time'=>date('Y-m-d H:i:s')
		);
		$commentId = $_Comment->insertData($data);
		if(!$commentId) exit('回复失败！');
		$_Push = new PushModel();
		$_Push->addPush(array(
			'app' => 'comment', 
			'act' => 'reply', 
			'from'=> $this->uid,
			'to' => $pInfo['teacher'],
			'student'=>0,
			'ext'=> $_Comment->getCommentInfo($commentId),
			'type' => '2',
			'message' => '您有新的点评回复！', 
		));
		exit('1');		
	}
}
