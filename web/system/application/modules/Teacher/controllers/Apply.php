<?php
/**
 * 申请
 */
class ApplyController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$_Apply = new ApplyModel();
		$datas = $_Apply->getList($page,10,array('to'=>$this->tid,'status'=>0,'type in (1,3)'=>null),'*','','create_time desc');
		$this->getView()->assign('pages', $datas['pages']);
		$this->getView()->assign('applys', $datas['data']);
		$this->getView()->assign('c', 'apply');
		$dispatcher = Yaf_Dispatcher::getInstance();
		$dispatcher->autoRender(false);
		$this->getView()->display('message/index.html');
	}
	
	public function doAction(){
		$id = $this->post('id',0,'intval');
		$status = $this->post('status',2,'intval');
		if(!$id || !in_array($status,array(1,2))) exit('参数错误');
		$_Apply = new ApplyModel();
		$applyInfo = $_Apply->getRow(array('id' => $id, 'to' => $this->tid, 'status' => 0));
		if(!$applyInfo) exit('无此记录');
		switch($applyInfo['type']){
			case 1: // 学生+老师
				break;
			case 2: // 老师+学生
				break;						
			case 3: // 机构+老师	
				if($status == 1){
					$_SchoolTeacher = new School_TeacherModel();
					if($_SchoolTeacher->getRow(array('school'=>$applyInfo['from'], 'teacher'=>$this->tid))) exit('已经是机构老师了');
					$data = array(
						'school'=>$applyInfo['from'],
						'teacher'=>$this->tid,
						'create_time'=>time(),
						'operator'=>$this->uid,
						'source'=>0,
					);
					if(!$_SchoolTeacher->insertData($data)) exit('加入机构失败');
				}
				$_Apply->deleteData(array('id' => $id));
				break;
			case 4: // 老师+机构
				break;
			case 5: // 好友申请	
				break;
			case 6: // 学生+机构 验证码						
				break;
			case 7: // 机构+学生
				break;
			case 8: // 学生授权
				break;
		}
		
		exit('1');
	}
}
