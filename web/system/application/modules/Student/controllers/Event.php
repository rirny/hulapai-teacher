<?php
/**
 * 课程信息
 */
class EventController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$is_view = $this->get('is_view',0,'intval');
		$pagesize = 20;
		if($is_view) $pagesize = 0;
		$where = '';
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where .= " and b.end_date >= '$start_date 00:00:00'";
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where .= " and b.start_date <= '$end_date 23:59:59'";
		}
		$_CourseStudent = new Course_StudentModel();
		$events = $_CourseStudent->getStudentCourseList($page,$pagesize,$this->sid,$this->school,$where,$is_view?0:1);
		if($is_view){
			foreach ($events['data'] as &$item) {            
	            $item['readonly'] = false;
	            $item['commented'] && $item['readonly'] = true; // 已考评            
	        }
	        $this->_xml($events['data']);
		}
		$this->getView()->assign('pageEvents', $events['pages']);
		$this->getView()->assign('events', $events['data']);
	}
	
	private function _xml($result){
        $colorArr = Yaf_Registry::get('config')->color->toArray();
        ob_clean();
		header("Content-type:text/xml");
		echo "<?xml version='1.0' ?>";
		echo "<data>";
		for ($i=0; $i < sizeof($result); $i++)
		echo $this->_item_xml($result[$i],$colorArr);
		echo "</data>";
		exit;
    }
    
    private function _item_xml($item,$colorArr){      
        $str ="<event id='".$item['event']."' >\n";
		$str.="<start_date><![CDATA[".$item['start_date']."]]></start_date>\n";
		$str.="<end_date><![CDATA[".$item['end_date']."]]></end_date>\n";
        $str.="<text><![CDATA[".$item['remark']."]]></text>\n";
        $str.="<color><![CDATA[".$colorArr[intval($item['color'])]."]]></color>\n";
        $str.="<readonly><![CDATA[".($item['readonly'] ? 1 : 0)."]]></readonly>\n";
        $str.="<commented><![CDATA[".($item['commented'] ? 1 : 0)."]]></commented>\n";
        $str.="<rec_type><![CDATA[". $item['rec_type']."]]></rec_type>\n"; // week_1___2#no
        $str.="<event_length><![CDATA[". $item['length'] ."]]></event_length>\n";
        $str.="<event_pid><![CDATA[". $item['pid'] ."]]></event_pid>\n";
        $_CourseStudent = new Course_StudentModel();
        $students = $_CourseStudent->getCourseStudents($item['event'],true);
        $title = "课程名：&#10;&nbsp;&nbsp;".$item['text']."&#10;上课时间：&#10;&nbsp;&nbsp;".mb_substr($item['start_date'],10,6)."-".mb_substr($item['end_date'],10,6)."&#10;老师：&#10;&nbsp;&nbsp;".teacherName($item['teacher'])."&#10;学生：".$students;
        $str.="<title><![CDATA[".$title."]]></title>\n";
		return $str."</event>\n";
    }    
    
    /**
	 * 课程表
	 */
	public function viewAction() {
		
	}
	
	public function infoAction(){
		$page = $this->get('page',1,'intval');
		$id = $this->get('id',0,'intval');
		$pid = $this->get('pid', 0, 'intval');
		$length = $this->get('length', 0, 'intval');
		if(!$id && !$pid && !$length) show_message('参数错误！');
		$_Event = new EventModel();
		if($pid && $length){
            $eventInfo = $_Event->rec_create($pid, $length);          
		}elseif($id){
			$eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $id));
		}else{
			show_message('参数错误！');
		}
		$id = $eventInfo['id'];
		if(!$eventInfo)   show_message('课程不存在！');
		$_CourseStudent = new Course_StudentModel();
		$eventInfo = $_CourseStudent->getStudentCourse($this->sid,$this->school,$id);
		if(!$eventInfo)  show_message("学生没有该课程");
		$this->getView()->assign('event', $eventInfo);
		//获取课程的相关点评
		$_Comment = new CommentModel();
		$_Attach = new AttachModel();
		$teacherComment = $_Comment->getRow(array('event'=>$id,'student'=>$this->sid,"character in ('teacher','school')"=>null),'*','id asc');
		if($teacherComment['attach']){
			$teacherComment['attachInfos'] = $_Attach->getAttachs($teacherComment['attach']);
		}
		$datas = $teacherComment ? $_Comment->getList($page,5,array('event'=>$id,'student'=>$this->sid,'id >'=>$teacherComment['id']),'*','','id asc'):array();
		if($datas['data']){
			foreach($datas['data'] as &$data){
				$data['attachInfos'] = array();
				if($data['attach']){
					$data['attachInfos'] = $_Attach->getAttachs($data['attach']);
				}
			}
		}
		$this->getView()->assign('teacherComment', $teacherComment);
		$this->getView()->assign('comments', $datas['data']);
		$this->getView()->assign('pages', $datas['pages']);
		//获取课程的相关课程
		$others = array();
		if($eventInfo['pid']){
			$others = $_CourseStudent->getStudentCourseList(1,5,$this->sid,$this->school," and b.pid = ".$eventInfo['pid']);
		}
		$this->getView()->assign('others', $others['data']?$others['data']:array());
	}
	
	
	public function infoEditAction(){
		$id = $this->post('id',0,'intval');
		if(!$id) exit("-1");
		$_CourseStudent = new Course_StudentModel();
		$course = $_CourseStudent->getRow(array('id'=>$id));
		if(!$course) exit("-1");
		if(isset($this->_POST['remark'])){
			$remark = $this->post('remark', '', 'trim');
			if(!$remark  || !$_CourseStudent->updateData(array('remark'=>$remark),array('id'=>$id))) exit("-1");
			exit("$remark");
		}
		if(isset($this->_POST['fee'])){
			$fee = $this->post('fee', 0, 'intval');
			if(!$_CourseStudent->updateData(array('fee'=>$fee),array('id'=>$id))) exit("-1");
			exit("$fee");
		}
		if(isset($this->_POST['color'])){
			$color = $this->post('color', 0, 'intval');
			if(!$_CourseStudent->updateData(array('color'=>$color),array('id'=>$id))) exit("-1");
			exit("$color");
		}
		exit("-1");
	}
}
