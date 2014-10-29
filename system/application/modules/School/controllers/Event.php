<?php
/**
 * 课程
 */
class EventController extends Yaf_Controller_Base_Abstract {
	public function indexAction() {	
		$page = $this->get('page',1,'intval');
		$is_view = $this->get('is_view',0,'intval');
		$where = array('school'=>$this->school,'pid'=>0,'status'=>0,'source'=>0);
		$export = $this->get('export','','trim');
		$pageSize = $export ? 9999: 20;
        $_Event = new EventModel();
        $event = $this->get('event',0,'intval');
		if($event){
			$where['pid'] = $event;
			$where['rec_type !='] = 'none';
			$pageSize = 10;
			$this->getView()->assign('childEvent', 1);
            $parents = $_Event->getRow(array('id' => $event));            
            $this->getView()->assign('parents', json_encode($parents));
		}else{
			$course = $this->get('course',0,'intval');
			if($course) $where['course'] = $course;
			$text = $this->get('text','','trim');
			if($text && $text != "课程名称") $where['text like'] = "%$text%";
		}
		
		if($is_view){
			unset($where['pid']);
			$where['rec_type ='] = '';
		}
		$start_date = $this->get('start_date','','isDate');
		if($start_date){
			$where['end_date >='] = $start_date.' 00:00:00';
		}
		$end_date = $this->get('end_date','','isDate');
		if($end_date){
			$where['start_date <='] = $end_date.' 23:59:59';
		}
		$teacher = $this->get('teacher',0,'intval');
		if($teacher){
			$_CourseTeacher = new Course_TeacherModel();
			$eventIds = $_CourseTeacher->getAll(array('teacher'=>$teacher),'event');
			$eventIdArr = array();
			if($eventIds){
				foreach($eventIds as $eventId){
					$eventIdArr[] = $eventId['event'];
				}
			}
			if(!$eventIdArr) $where["id"] = 0;
			else $where["id in (".implode(',',$eventIdArr).")"] = NULL;
			
		}
		$sorts = $this->get('sorts','start_date desc,id desc','trim');
		if($is_view){
			$events = $_Event->getAll($where,'*','',$sorts);
			foreach ($events as &$item) {            
	            $item['readonly'] = false;
	            $item['commented'] && $item['readonly'] = true; // 已考评            
	        }
	        $this->_xml($events);
		}else{
			$_Course = new CourseModel();
			$_CourseTeacher = new Course_TeacherModel();
			$events = $_Event->getList($page,$pageSize,$where,'*','',$sorts);
			if($events['data']){
				$_where = $where;
				$_where['is_loop'] = 0;
				unset($_where['text like']);
				$data = array();
				foreach($events['data'] as &$event){
					$courseInfo = $_Course->getRow(array('id'=>$event['course']),'title');
					$event['courseName'] = $courseInfo['title'];
					if($event['is_loop']){
						$_where['pid'] = $event['id'];
						$_where['rec_type'] = '';
						//总课时
						$event['classes'] = $_Event->getCount($_where);
					}else{
						$event['classes'] = 1;
					}
					if($export){
						$data[] = array(
							'courseName'=>$event['courseName'],
							'text'=>$event['text'],
							'eventDate'=>date('Y-m-d',strtotime($event['start_date'])).($event['is_loop'] ? "至".date('Y-m-d',strtotime($event['end_date'])):""),
							'eventHour'=>date('H:i',strtotime($event['start_date']))."至".date('H:i',strtotime($event['end_date'])),
							'teacher'=>teacherName($event['teacher']),
							'classes'=>$event['classes'],
						);
					}
					//获取老师
					$event['teacher'] = array();
					$teachers = $_CourseTeacher->getAll(array('event'=>$event['id']),'teacher');
					if($teachers){
						foreach($teachers as $teacher){
							$event['teacher'][] = $teacher['teacher'];
						}
					}
				}
				if($export){
					$filename = '课程列表';
					$headerArr = array('授课内容', '课程名称', '上课日期','上课时间','上课老师','课次总数');
					excelExport($filename,$headerArr,$data);
				}
			}
			//获取机构课程分类
			$schoolCourses = $_Course->getSchoolCourseType($this->school);
			//获取教师
			$_SchoolTeacher = new School_TeacherModel();
			$teachers = $_SchoolTeacher->getSchoolTeacher($this->school,true,1);
			
			$this->getView()->assign('schoolCourses', $schoolCourses);
			$this->getView()->assign('teachers', $teachers);
			$this->getView()->assign('events', $events['data']);
			$this->getView()->assign('pages', $events['pages']);
			$this->getView()->assign('sorts', $sorts);
		}
	}
	 // 分发   
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
        $str ="<event id='".$item['id']."' >\n";
		$str.="<start_date><![CDATA[".$item['start_date']."]]></start_date>\n";
		$str.="<end_date><![CDATA[".$item['end_date']."]]></end_date>\n";
        $str.="<text><![CDATA[".$item['text']."]]></text>\n";
        $str.="<color><![CDATA[".$colorArr[intval($item['color'])]."]]></color>\n";
        $str.="<readonly><![CDATA[".($item['readonly'] ? 1 : 0)."]]></readonly>\n";
        $str.="<commented><![CDATA[".($item['commented'] ? 1 : 0)."]]></commented>\n";
        $str.="<rec_type><![CDATA[". $item['rec_type']."]]></rec_type>\n"; // week_1___2#no
        $str.="<event_length><![CDATA[". $item['length'] ."]]></event_length>\n";
        $str.="<event_pid><![CDATA[". $item['pid'] ."]]></event_pid>\n";
        $_CourseStudent = new Course_StudentModel();
        $students = $_CourseStudent->getCourseStudents($item['id'],true);
        $title = "课程名：&#10;&nbsp;&nbsp;".$item['text']."&#10;上课时间：&#10;&nbsp;&nbsp;".mb_substr($item['start_date'],10,6)."-".mb_substr($item['end_date'],10,6)."&#10;老师：&#10;&nbsp;&nbsp;".teacherName($item['teacher'])."&#10;学生：".$students;
        $str.="<title><![CDATA[".$title."]]></title>\n";
		return $str."</event>\n";
    }    
    
	/**
	 * 增加课程
	 */
	public function addAction() {
		if($this->_POST){
			$data = $this->checkEventPost('add');
			$_Event = new EventModel();
			$eventId = $_Event->createEvent($data['eventData'],$data['teachers'],$data['students'],$data['push']);
			if(!$eventId) show_message('开课失败!');
			$script = '<script language="javascript" type="text/javascript" src="'.$this->path->js.'jquery.min.js"></script>';
			$script .= '<script type="text/javascript">';
			$script .= 'var tipEnd = "<button class=\"button aui_state_highlight\" onclick=\"window.top.art.dialog({id:\'tipaddevent\'}).close();window.top.document.getElementById(\'rightMain\').contentWindow.location.href=\'/\school/\event/\add\'\">继续开课</button>&nbsp;&nbsp;<button class=\"button aui_state_highlight\" onclick=\"window.top.art.dialog({id:\'tipaddevent\'}).close();window.top.document.getElementById(\'rightMain\').contentWindow.location.href=\'/\school/\event/\index\'\">返回课程列表</button>";';
			$script .= 'function showTip(tip){window.top.art.dialog({id:"tipaddevent",content:tip, title:"开课成功", lock:true});}';
			
			if($data['eventData']['lock'] == 1){
				$script .= 'showTip("正在生成课程，已生成0节，请稍候。。。");';
				$script .= 'var intervalId;';
				$script .= 'function checkEventLock(){$.post("/public/checkEventLock","event='.$eventId.'",function(data){
							if(data == "fail"){clearInterval(intervalId);}
							else if(data == "success"){clearInterval(intervalId);window.top.art.dialog({id:"tipaddevent"}).content(tipEnd);}
							else{window.top.art.dialog({id:"tipaddevent"}).content("正在生成课程，已生成"+data+"节，请稍候。。。");}
						});}';
				$script .= 'checkEventLock(); intervalId = setInterval("checkEventLock()", 1000);';
			}else{
				$script .= 'showTip(tipEnd);';
			}
			$script .= '</script>';
			echo $script;
			exit;
		}
		$this->_baseEventInfo();
	}
	
	
	
	/**
	 * 修改课程
	 */
	public function editAction() {
		if($this->_POST){
			$data = $this->checkEventPost('edit');
			$_Event = new EventModel();
			if(!$_Event->updateEvent($data['id'],$data['eventData'],$data['teachers'],$data['students'],$data['clear'],$data['whole'],$data['old'],$data['push'],$data['needPush'])) show_message('修改课程失败!');
			show_message('修改课程成功!',$this->post('refer',url('school','event','index'),'trim'));
		}else{
			$id = $this->get('id', 0, 'intval');
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
			if($eventInfo['rec_type']){
				$rec = Repeat::resolve($eventInfo['start_date'], $eventInfo['end_date'], $eventInfo['rec_type'], $eventInfo['length']);		
				$eventInfo['num'] = count($rec);
				$rec_type = explode('_',$eventInfo['rec_type']);
				$eventInfo['rec_type'] = $rec_type[0].'_'.$rec_type[1];
				$other = explode('#',$rec_type[4]);
				$eventInfo['week'] = $other[0];
			}else{
				$eventInfo['num'] = 1;
			}
			$this->getView()->assign('eventInfo', $eventInfo);
			$this->getView()->assign('wholes', array('0'=>'之后循环课程','1'=>'整个循环课程'));
			$this->_baseEventInfo($id);
		}
	}
	
	/**
	 * 批量删除子课程
	 */
	public function peditAction() {
		$_Event = new EventModel();
		if($this->_POST){
			$id = $this->post('id', '', 'trim');
			if(!$id) show_message('参数错误！');
			$_Event = new EventModel();
			if(strpos($id, '#')){
				list($pid, $length) = explode("#", $id);        
				$eventInfo = $_Event->rec_create($pid,$length);            
			}else{
				$eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $id));
			}
			if(!$eventInfo)   show_message('课程不存在！');
			//循环课程锁定
			if($eventInfo['is_loop'] && $eventInfo['lock']) show_message('该课程正在处理中，请稍后！');
			
			//课程分类
			$course = $this->post('course', 0, 'intval');
			if(!$course) show_message('课程分类不能为空！');
			//课程标题
			$text = $this->post('text', '', 'trim');
			if(!$text) show_message('标题不能为空');
			//课时数
			$class_time = $this->post('class_time', 1.0, 'floatval');
			//上课时间
			$start_hour = $this->post('start_hour', 0, 'intval');
			$start_minute = $this->post('start_minute', 0, 'intval');
			$end_hour = $this->post('end_hour', 0, 'intval');
			$end_minute = $this->post('end_minute', 0, 'intval');
			$length = intval(strtotime(date('Y-m-d')." $end_hour:$end_minute") - strtotime(date('Y-m-d')." $start_hour:$start_minute"));
			if($length < 1800) show_message('课程时间不能小于30分钟！');
			//颜色
			$color = $this->post('color', 1, 'intval');		
			//描述
			$description = $this->post('description', '', 'trim');
			// 学生验证
			$students = $this->post('student_op', array());
			if($students)
			{
				$_SchoolStudent = new School_StudentModel();
				$_Student = new StudentModel();
	            foreach($students as $student)
	            {	
	                if(!$_SchoolStudent->getRow(array('school' => $this->school, 'student' => $student))) show_message('不是机构学生!@'.$student);
	                if(!$_Student->getRow(array('id' => $student))) show_message('没有此学生!@'.$student);
	            }
			}
			if(!$students) show_message('学生设置错误!');
			// 老师验证
			$teachers = $this->post('teacher_op', array());
			$_teacher = 0;
			if($teachers)
			{
				$_SchoolTeacher = new School_TeacherModel();
				$_Teacher = new TeacherModel();
	            foreach($teachers as $teacher=>$priv)
	            {	
	                if($teacher && $priv & 1 ) $_teacher = $teacher;
	                if(!$priv) show_message('老师权限未设置!@'.$teacher);
	                if(!$_SchoolTeacher->getRow(array('school' => $this->school, 'teacher' => $teacher))) show_message('不是机构老师!@'.$teacher);
	                if(!$_Teacher->getRow(array('user' => $teacher))) show_message('没有此老师!@'.$teacher);
	            }
			}
			if(!$teachers) show_message('老师设置错误!');
			if(!$_teacher) show_message('老师没有上课权限!');
			$clear = array();
			$whole = 0;
			$eventData = array(
				'course'=>$course,
				'text'=>$text,
				'teacher'=>$teacher,
				'color'=>$color,
				'description'=>$description,
				'class_time'=>$class_time
			);
			$ids = $this->post('ids', array());
			foreach($ids as $id){
				$needPush = 0;
				$push = 1;
				$_eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $id));
				$old = $_eventInfo;
				//课程不存在或已删除！
				if(!$_eventInfo || $_eventInfo['status'] == 1 || ($_eventInfo['pid'] && $_eventInfo['rec_type'] == 'none')) continue;
				//循环课程不能批量修改
				if($_eventInfo['is_loop']) continue;
				$_eventInfo = array_merge($_eventInfo,$eventData);
				//日期
				$_start_date = date('Y-m-d', strtotime($_eventInfo['start_date']));
				$_end_date = date('Y-m-d', strtotime($_eventInfo['end_date']));
				$_eventInfo['start_date'] = date('Y-m-d H:i:s', strtotime($_start_date." $start_hour:$start_minute"));
				$_eventInfo['end_date'] = date('Y-m-d H:i:s', strtotime($_end_date." $end_hour:$end_minute"));
				$_eventInfo['length'] = strtotime($_start_date." $start_hour:$start_minute");
				//修改了日期
				if($old['start_date'] != $_eventInfo['start_date'] || $old['end_date'] != $_eventInfo['end_date']){
					//修改了日期，push=2
					$needPush = 1;
					$push = 2;
				}
				//修改课程失败
				if(!$_Event->updateEvent($id,$_eventInfo,$teachers,$students,array(),0,$old,$push,$needPush)) continue;
				
			}
			show_message('修改课程成功!',url('school','event','index'));
		}else{
			$ids = $this->get('id', array());
			$pid = $this->get('pid', 0, 'intval');
			if(!$ids || empty($ids) || !$pid) show_message('参数错误！');
			$eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $pid));
			if(!$eventInfo) show_message('课程不存在！');
			$this->getView()->assign('eventInfo', $eventInfo);
			$this->getView()->assign('ids', $ids);
			$this->_baseEventInfo($pid);
		}
	}
	
	private function _baseEventInfo($event=0){
		//获取机构课程分类
		$_Course = new CourseModel();
		$schoolCourses = $_Course->getSchoolCourseType($this->school);
		if(!$schoolCourses) show_message('请先设置授课内容！',url('school','course','index'));
		$this->getView()->assign('schoolCourses', $schoolCourses);
		$this->getView()->assign('event', $event);
		$this->getView()->assign('school', $this->school);
		//获取选择的老师
		$_CourseTeacher = new Course_TeacherModel();
		$_User = new UserModel();
		$courseTeachers =  $event ? $_CourseTeacher->getAll(array('event'=>$event),'id,teacher,priv'):array();
		if($courseTeachers){
			foreach($courseTeachers as &$courseTeacher){
				$userInfo = $_User->getRow(array('id'=>$courseTeacher['teacher']),'firstname,lastname,account');
				$courseTeacher['userInfo'] = $userInfo;
			}
		}
		$this->getView()->assign('courseTeachers', $courseTeachers);
		//获取选择的学生
		$_CourseStudent = new Course_StudentModel();
		$courseStudents = $event ? $_CourseStudent->getAll(array('event'=>$event),'student'):array();
		$_Student = new StudentModel();
		if($courseStudents){
			foreach($courseStudents as &$courseStudent){
				$userInfo = $_Student->getRow(array('id'=>$courseStudent['student']),'name');
				$courseStudent['userInfo'] = $userInfo;
			}
		}
		$this->getView()->assign('courseStudents', $courseStudents);
	}
	
	private function _childEventInfo($eventInfo,$page=1,$pagesize=10){
		if(!$eventInfo['is_loop']) return false;
		$_Event = new EventModel();	
		$hasChild = array();
		$childInfos = $_Event->getAll(array('school'=>$this->school,'pid' => $eventInfo['id']));
		if($childInfos){
			foreach($childInfos as $childInfo){
				$hasChild[$childInfo['length']] = $childInfo;
			}
		}
		
		$rec = Repeat::resolve($eventInfo['start_date'], $eventInfo['end_date'], $eventInfo['rec_type'], $eventInfo['length']);		
		$total = count($rec);        
		$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$data = array();
		if($total){
			$childKeys = array_keys($hasChild);
			if($total > $pagesize){
				$rec = array_chunk($rec,$pagesize);
				$rec = $rec[$page-1];
			}
			foreach($rec as $val){
				
				if(in_array($val['length'],$childKeys)){
					$data[$val['length']] = $hasChild[$val['length']];
				}else{
					$data[$val['length']] = array_merge($eventInfo, $val, array(
			            'id' => $eventInfo['id'] . "#" . $val['length'],
			            'pid' => $eventInfo['id'],
			            'lock' => 0,
			            'rec_type' => '',
			            'is_loop' => 0
			        ));
				}
			}
		}
		$this->getView()->assign('childData', $data);
		$this->getView()->assign('childDataPages', $pages);
	}
	
	private function checkEventPost($type='add'){
		$id = 0;
		$needPush = 2;
		$push = 2;
		$whole = 0;
		$eventInfo = array();
		if($type=='edit'){
			$needPush = 0;
			$push = 1;
			$whole = $this->post('whole', 0, 'intval');
			if(!in_array($whole,array(0,1))) show_message('参数错误！');
			$id = $this->post('id', '', 'trim');
			if(!$id) show_message('参数错误！');
			$_Event = new EventModel();
			if(strpos($id, '#')){
				list($pid, $length) = explode("#", $id);        
				$eventInfo = $_Event->rec_create($pid,$length);            
			}else{
				$eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $id));
			}
			if(!$eventInfo)   show_message('课程不存在！');
			//循环课程锁定
			if($eventInfo['is_loop'] && $eventInfo['lock']) show_message('该课程正在处理中，请稍后！');
		}
		//课程分类
		$course = $this->post('course', 0, 'intval');
		if(!$course) show_message('课程分类不能为空！');
		//课程标题
		$text = $this->post('text', '', 'trim');
		if(!$text) show_message('标题不能为空');
		//课时数
		$class_time = $this->post('class_time', 1.0, 'floatval');
		//日期
		$_start_date = $this->post('start_date', date('Y-m-d'), 'isDate');
		if(!$_start_date) show_message('上课日期不能为空且格式必须正确');
		$_start_date = date('Y-m-d', strtotime($_start_date));
		//上课时间
		$start_hour = $this->post('start_hour', 0, 'intval');
		$start_minute = $this->post('start_minute', 0, 'intval');
		$end_hour = $this->post('end_hour', 0, 'intval');
		$end_minute = $this->post('end_minute', 0, 'intval');
		$length = intval(strtotime(date('Y-m-d')." $end_hour:$end_minute") - strtotime(date('Y-m-d')." $start_hour:$start_minute"));
		if($length < 1800) show_message('课程时间不能小于30分钟！');
		$start_date = date('Y-m-d H:i:s', strtotime($_start_date." $start_hour:$start_minute"));
		//重复设置
		$rec_type = $this->post('rec_type', '', 'trim');
		$clear = array();
		// 循环课程
		if($rec_type){
			if($type=='edit'){
				//是循环课程子课程
				if($eventInfo['pid']) show_message('子课程无法修改循环属性'); 
				//禁止循环课程子课程修改为循环课
				if(strpos($this->post('id'), '#')) show_message('禁止的操作!');  
			}
            $num =  $this->post('num', 1,'intval');
            if($num < 1 || $num > 100) show_message('课次必须在1-100之间!'); 
            if($rec_type == "day_1"){
            	$rec_type = "day_1___#";
            }elseif($rec_type == "week_1" || $rec_type == "week_2" || $rec_type == "month_1"){
            	/*
            	$week =  $this->post('week', date('w'),'intval');
            	$weekArr = array(
					'1'=>'周一',
					'2'=>'周二',
					'3'=>'周三',
					'4'=>'周四',
					'5'=>'周五',
					'6'=>'周六',
					'0'=>'周日',
				);
	            if($week != date('w',strtotime($start_date))) show_message('日期'.$start_date.'和'.$weekArr[$week].'不是同一天');
	            */
	            $week =  $this->post('week');
	            if(!$week) show_message('请选择星期!');
	            $week = implode(',',$week);
            	$rec_type = $rec_type.'___'.$week.'#'.$num;            	
            }else{
            	show_message('重复设置错误!');
            }
            $start_end_date = Repeat::start_end_date($start_date,$num,$rec_type,$length);
            if(!$start_end_date || count($start_end_date) < 2) show_message('重复设置错误，无法生成课程!');
            $start_date = $start_end_date[0];
            $end_date = $start_end_date[1];
			$lock = 1;
			$is_loop = 1;
			//修改了循环属性，push=2
			if($type=='edit'){
				$needPush = 1;
				$push = 2;
				$clear = $_Event->rec_clear($eventInfo['id'], $whole);
			}
		//一次性课程
		}else{
			$end_date = date('Y-m-d H:i:s', strtotime($_start_date." $end_hour:$end_minute"));
			$lock = 0;
			$is_loop = 0;
			$length = 0;
			if($type=='edit'){
				//是循环课程子课程
				if(!$eventInfo['pid']){
					//循环改为非循环
					if($eventInfo['rec_type']){
						$clear = $_Event->rec_clear($eventInfo['id'], $whole);
						//修改了循环属性，push=2
						$needPush = 1;
						$push = 2;	
					}
				}else{
					$lock = 0;
					$is_loop = 0;
					$length = $eventInfo['length'];
				}
			}
		}	
		//班级
		$grade = $this->post('grade', 0, 'intval');
		if($type=='edit'){
			//修改了日期
			if($start_date != $eventInfo['start_date'] || $end_date != $eventInfo['end_date']){
				//修改了日期，push=2
				$needPush = 1;
				$push = 2;
			}
		}
		
		//颜色
		$color = $this->post('color', 1, 'intval');		
		//描述
		$description = $this->post('description', '', 'trim');
		// 学生验证
		$students = $this->post('student_op', array());
		if($students)
		{
			$_SchoolStudent = new School_StudentModel();
			$_Student = new StudentModel();
            foreach($students as $student)
            {	
                if(!$_SchoolStudent->getRow(array('school' => $this->school, 'student' => $student))) show_message('不是机构学生!@'.$student);
                if(!$_Student->getRow(array('id' => $student))) show_message('没有此学生!@'.$student);
            }
		}
		if(!$students) show_message('学生设置错误!');
		// 老师验证
		$teachers = $this->post('teacher_op', array());
		$_teacher = 0;
		if($teachers)
		{
			$_SchoolTeacher = new School_TeacherModel();
			$_Teacher = new TeacherModel();
            foreach($teachers as $teacher=>$priv)
            {	
                if($teacher && $priv & 1 ) $_teacher = $teacher;
                if(!$priv) show_message('老师权限未设置!@'.$teacher);
                if(!$_SchoolTeacher->getRow(array('school' => $this->school, 'teacher' => $teacher))) show_message('不是机构老师!@'.$teacher);
                if(!$_Teacher->getRow(array('user' => $teacher))) show_message('没有此老师!@'.$teacher);
            }
		}
		if(!$teachers) show_message('老师设置错误!');
		if(!$_teacher) show_message('老师没有上课权限!');
		$eventData = array(
			'course'=>$course,
			'text'=>$text,
			'start_date'=>$start_date,
			'end_date'=>$end_date,
			'rec_type'=>$rec_type,
			'length'=>$length,
			'grade'=>$grade,
			'school'=>$this->school,
			'teacher'=>$teacher,
			'color'=>$color,
			'description'=>$description,
			'creator'=>$this->uid,
			'create_time'=>time(),
			'class_time'=>$class_time,
			'is_loop'=>$is_loop,
			'lock'=>$lock
		);
		//有班级了,不能修改班级
		if($type == "edit"){
			if($eventInfo['grade']){
				$eventData['grade'] = $eventInfo['grade'];
			}
		}
		return array(
			'eventData'=>$eventData,
			'teachers'=>$teachers,
			'students'=>$students,
			'push'=>$push,
			'needPush'=>$needPush,
			'id'=>$id,
			'old'=>$eventInfo,
			'whole'=>$whole,
			'clear'=>$clear
		);
	}
	/**
	 * 删除课程
	 */
	public function deleteAction() {
		$whole = $this->get('whole', 0, 'intval');
		if(!in_array($whole,array(0,1))) show_message('参数错误！');
		$id = $this->get('id', 0, 'intval');
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
		if(!$eventInfo || $eventInfo['status'] == 1 || ($eventInfo['pid'] && $eventInfo['rec_type'] == 'none'))   show_message('课程不存在或已删除！');
		//循环课程锁定
		if($eventInfo['is_loop'] && $eventInfo['lock']) show_message('该课程正在处理中，请稍后！');
		$id = $eventInfo['id'];
		$clear = array();
		if($eventInfo['is_loop']){
			$clear = $_Event->rec_clear($eventInfo['id'], $whole);
		}
		if(!$_Event->deleteEvent($id,$eventInfo,$clear,$whole,2)) show_message('删除课程失败！');
		show_message('删除课程成功！',url('school','event'));
	}
	
	/**
	 * 批量删除子课程
	 */
	public function pdeleteAction() {
		$ids = $this->post('id', array());
		$param = $this->post('param', '','trim');
		if(!$ids || empty($ids)) show_message('参数错误！');
		$_Event = new EventModel();
		foreach($ids as $id){
			$eventInfo = $_Event->getRow(array('school'=>$this->school,'id' => $id));
			//课程不存在或已删除！
			if(!$eventInfo || $eventInfo['status'] == 1 || ($eventInfo['pid'] && $eventInfo['rec_type'] == 'none')) continue;
			//循环课程不能批量删除
			if($eventInfo['is_loop']) continue;
			//删除课程失败
			if(!$_Event->deleteEvent($id,$eventInfo,array(),0,2)) continue;
		}
		show_message('删除课程成功！',url('school','event','index',$param));
	}
	
	/**
	 * 课程表
	 */
	public function viewAction() {
		$_Course = new CourseModel();
		//获取机构课程分类
		$schoolCourses = $_Course->getSchoolCourseType($this->school);
		//获取教师
		$_SchoolTeacher = new School_TeacherModel();
		$teachers = $_SchoolTeacher->getSchoolTeacher($this->school,true,1);
		$text = $this->get('text','','trim');
		if($text == "课程名称") $text = '';
		$this->getView()->assign('text', $text);
		$this->getView()->assign('course', $this->get('course',0,'intval'));
		$this->getView()->assign('teacher', $this->get('teacher',0,'intval'));
		$this->getView()->assign('schoolCourses', $schoolCourses);
		$this->getView()->assign('teachers', $teachers);
	}
}
