<?php
class Course_TeacherModel extends BaseModel{
	public $table = 't_course_teacher';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getTeacherCourseList($page=1,$pagesize=20,$teacher=0,$school=0,$where='',$time=1)
	{
		
	}

    /**
     * 获取老师课程
     */
    public function _getTeacherCourseList($page=1,$pagesize=20,$teacher=0,$school=0,$where='',$time=1){
    	$sql =  " FROM (`t_course_teacher` AS a)" .
    			" JOIN `t_event` AS b ON `a`.`event` = `b`.`id`" .
    			" where a.teacher = $teacher and a.status = 0";
    	if($school) $sql .= " and b.school = $school";
		
    	$sql .= " and b.is_loop = 0 and b.rec_type ='' and b.status=0 and b.source = 0";
    	if($time){
    		$date = date('Y-m-d',strtotime('+1 day'));
    		$sql .= " and b.end_date < '$date'";
    	}
    	if($where) $sql .= "$where";
    	$sql .= " order by b.start_date desc,a.event desc,a.id desc";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize?$pagesize:$total);
		$sqlQuery =  "SELECT a.id,a.event,a.priv,a.teacher,a.course,a.remark,a.color,b.text,b.pid,b.length,b.start_date,b.end_date,b.attend,b.leave,b.absence,b.commented,b.attended".$sql;
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    
     /**
     * 获取老师单节课程
     */
    public function getTeacherCourse($teacher=0,$school=0,$event=0){
    	$sql =  " FROM (`t_course_teacher` AS a)" .
    			" JOIN `t_event` AS b ON `a`.`event` = `b`.`id`" .
    			" where a.event = $event and a.teacher = $teacher";
    	if($school) $sql .= " and b.school = $school";
		$sqlQuery =  "SELECT a.id,a.event,a.priv,a.teacher,a.course,a.remark,a.color,b.text,b.pid,b.length,b.start_date,b.end_date,b.attend,b.leave,b.absence,b.commented,b.attended".$sql;
    	return $this->db->query($sqlQuery)->row_array(1);
    }
    
    /**
     * 获取学生已上课程（已考勤并出勤）
     */
    public function getTeacherCoursesByText($teacher=0,$school=0,$feeTexts=array()){
    	$sql = "SELECT COUNT(1) AS had,SUM(class_time) AS classes,SUM(b.attended) AS attended,GROUP_CONCAT(b.id) as event_ids,b.text FROM (`t_course_teacher` AS a)".
			   " LEFT JOIN t_event AS b ON `a`.`event` = `b`.`id`". 
			   " WHERE a.teacher = $teacher AND a.status = 0 AND b.is_loop = 0 AND b.rec_type ='' AND b.status=0 AND b.source = 0";
    	if($school) $sql .= " and b.school = $school";
    	$sql .= " and b.end_date <= NOW() group by b.text";
    	$datas = $this->db->query($sql)->result_array();
    	$hadArray = array();
    	if($datas){
	    	foreach($datas as $data){
	    		$sqlStudentCourse = "SELECT count(1) as studentNums,SUM(a.attend*a.attended) AS attend,SUM(a.leave*a.attended) AS `leave`,SUM(a.absence*a.attended) AS absence from t_course_student as a LEFT JOIN t_event AS b ON `a`.`event` = `b`.`id` where a.event in (".$data['event_ids'].")";
	    		$attendedInfo = $this->db->query($sqlStudentCourse)->row_array(1);
	    		//获取老师点评id
				$sqlComment = "SELECT count(1) as comments,GROUP_CONCAT(id) as comment_ids from t_comment where event in (".$data['event_ids'].") and creator=$teacher and `character` = 'teacher' and pid=0 and status=0";	
				$commentInfo = $this->db->query($sqlComment)->row_array(1);
				$comments = $commentInfo['comments'];
				$replies = 0;
				if($commentInfo['comment_ids']){
					$sqlComment = "SELECT count(1) as replies from t_comment where pid in (".$commentInfo['comment_ids'].") and `character` != 'teacher'";	
					$replyInfo = $this->db->query($sqlComment)->row_array(1);
					$replyInfo['replies'] && $replies = $replyInfo['replies'];
				}	
	    		$hadArray[$data['text']]['text'] = $data['text'];
	    		$hadArray[$data['text']]['had'] = $data['had'];
	    		$hadArray[$data['text']]['classes'] = $data['classes'];
	    		$hadArray[$data['text']]['attended'] = $data['attended'];
	    		$hadArray[$data['text']]['studentNums'] = $attendedInfo['studentNums'];
	    		$hadArray[$data['text']]['attend'] = $attendedInfo['attend'];
	    		$hadArray[$data['text']]['leave'] = $attendedInfo['leave'];
	    		$hadArray[$data['text']]['absence'] = $attendedInfo['absence'];
	    		$hadArray[$data['text']]['comments'] = $comments;
	    		$hadArray[$data['text']]['replies'] = $replies;
	    		if($feeTexts && in_array($data['text'],$feeTexts)){
	    			$hadArray[$data['text']]['fee_setting'] = 1;
	    		}else{
	    			$hadArray[$data['text']]['fee_setting'] = 0;
	    		}
	    	}
    	}elseif($feeTexts){
    		foreach($feeTexts as $feeText){
	    		$hadArray[$feeText]['text'] = $feeText;
	    		$hadArray[$feeText]['had'] = 0;
	    		$hadArray[$feeText]['classes'] = 0;
	    		$hadArray[$feeText]['attended'] = 0;
	    		$hadArray[$feeText]['studentNums'] = 0;
	    		$hadArray[$feeText]['attend'] = 0;
	    		$hadArray[$feeText]['leave'] = 0;
	    		$hadArray[$feeText]['absence'] = 0;
	    		$hadArray[$feeText]['comments'] = 0;
	    		$hadArray[$feeText]['replies'] = 0;
	    		$hadArray[$feeText]['fee_setting'] = 1;
	    	}
    	}
    	return array_values($hadArray);
    }
    
}