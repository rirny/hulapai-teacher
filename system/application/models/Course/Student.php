<?php
class Course_StudentModel extends BaseModel{
	public $table = 't_course_student';
	public function __construct() {
    	parent::__construct();
    } 
    
    /**
     * 考勤
     */
    public function attendance($id,$event,array $data){
    	$this->db->trans_begin();
    	$data['attended'] = 1;
    	$this->db->update('t_course_student',$data,array('id'=>$id));
		$this->db->update('t_event',array('attended'=>1),array('id'=>$event));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		   return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
    /**
     * 获取学生课程
     */
    public function getStudentCourseList($page=1,$pagesize=20,$student=0,$school=0,$where='',$time=1){
    	$sql =  " FROM (`t_course_student` AS a)" .
    			" JOIN `t_event` AS b ON `a`.`event` = `b`.`id`" .
    			" where a.student = $student and a.source = 0 and a.status = 0";
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
		$sqlQuery =  "SELECT a.id,a.event,a.course,a.student,a.remark,a.color,a.fee,a.attend,a.leave,a.absence,a.commented as courseCommented,a.attended as courseAttended,b.text,b.pid,b.length,b.start_date,b.end_date,b.teacher,b.commented,b.attended".$sql;
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    /**
     * 获取学生已上课程（已考勤并出勤）
     */
    public function getStudentCoursesByText($student=0,$school=0,$feeTexts=array()){
    	$sql = "SELECT COUNT(1) AS had,SUM(a.attended) AS attended,SUM(a.attend*a.attended) AS attend,SUM(a.leave*a.attended) AS `leave`,SUM(a.absence*a.attended) AS absence,b.text FROM (`t_course_student` AS a)".
			   " LEFT JOIN t_event AS b ON `a`.`event` = `b`.`id`". 
			   " WHERE a.student = $student AND a.source = 0 AND a.status = 0 AND b.is_loop = 0 AND b.rec_type ='' AND b.status=0 AND b.source = 0";
    	if($school) $sql .= " and b.school = $school";
    	$sql .= " and b.end_date <= NOW() group by b.text";
    	$datas = $this->db->query($sql)->result_array();
    	$hadArray = array();
    	if($datas){
	    	foreach($datas as $data){
	    		$hadArray[$data['text']]['text'] = $data['text'];
	    		$hadArray[$data['text']]['had'] = $data['had'];
	    		$hadArray[$data['text']]['attended'] = $data['attended'];
	    		$hadArray[$data['text']]['attend'] = $data['attend'];
	    		$hadArray[$data['text']]['leave'] = $data['leave'];
	    		$hadArray[$data['text']]['absence'] = $data['absence'];
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
	    		$hadArray[$feeText]['attended'] = 0;
	    		$hadArray[$feeText]['attend'] = 0;
	    		$hadArray[$feeText]['leave'] = 0;
	    		$hadArray[$feeText]['absence'] = 0;
	    		$hadArray[$feeText]['fee_setting'] = 1;
	    	}
    	}
    	return array_values($hadArray);
    }
    
    /**
     * 获取学生单个课程
     */
    public function getStudentCourse($student=0,$school=0,$event=0){
    	$sql =  " FROM (`t_course_student` AS a)" .
    			" JOIN `t_event` AS b ON `a`.`event` = `b`.`id`" .
    			" where a.event = $event and a.student = $student";
    	if($school) $sql .= " and b.school = $school";
		$sqlQuery =  "SELECT a.id,a.event,a.course,a.student,a.remark,a.color,a.fee,a.attend,a.leave,a.absence,a.commented as courseCommented,b.text,b.pid,b.length,b.start_date,b.end_date,b.teacher,b.commented,b.attended".$sql;
    	return $this->db->query($sqlQuery)->row_array(1);
    }
    
    /**
     * 获取单个课程学生信息
     */
    public function getCourseStudents($event=0,$str=false){
    	$sql =  " FROM (`t_course_student` AS a)" .
    			" JOIN `t_student` AS b ON `a`.`student` = `b`.`id`" .
    			" where a.event = $event and a.status = 0 and a.source = 0";
		$sqlQuery =  "SELECT b.*".$sql;
    	$students = $this->db->query($sqlQuery)->result_array();
    	if($str){
    		$studentsStr = '';
    		foreach($students as $key=>$student){
    			if($key%3 == 0) $studentsStr .= '&#10;';	
    			$studentsStr .= '&nbsp;&nbsp;'.$student['name'];
    		}
    		
    		return $studentsStr;
    	}else{
    		return $students;
    	}
    }
    
    // 删除关系课程
    public function cut_relation($event, $course, $pid=0, &$push=array()){
        if(empty($event) || empty($course)) return false;
        // 删除关系      
        $this->deleteData(array('id'=>$course['id']));  
        $tm = time();             
        $parents = array(); 
        $_Event = new EventModel();    
        // 已发生的课程     
        if(strtotime($event['start_date']) < $tm){
            // 已发生的复制    
            $data = $event;
            $data['pid'] = $pid;
            $data['source'] = 2;
            $data['grade'] = 0;
            unset($data['id']);
            unset($course['id'], $course['modify_time']);
            if($event['is_loop'] == 1){
                // 处理子课程
                $course['end_date'] != '0000-00-00 00:00:00' && $data['end_date'] = $event['end_date'];                    
                $course['start_date'] != '0000-00-00 00:00:00' && $data['start_date'] = $event['start_date']; 
                if(strtotime($event['end_date']) > $tm)
                {
                    $recent = $_Event->recent($event);
                    $data['end_date'] = $recent['end_date'];
                    $course['end_date'] = '0000-00-00 00:00:00';
                }
                $id = $course['event'] = $_Event->insertData($data); 
                //获取子课程               
                $this->db->select('t_event.id,t_course_student.id as rid')
                	->from('t_course_student')
                	->join('t_event','t_course_student.event = t_event.id')
                	->where('t_event.`status` = 0 AND t_course_student.`status` = 0 AND t_course_student.student = '.$course['student'].' and t_event.pid = '.$event['id']);
				$childs = $this->db->get()->result_array();          
                if($childs){                   
                    foreach($childs as $key=>$item){
                        $E = $_Event->getRow(array('id'=>$item['id']));
                        $R = $this->getRow(array('id'=>$item['rid']));   
                        $this->cut_relation($E, $R, $id, $push); // 切断关系
                    }
                }
            }else{                
                $id = $course['event'] = $_Event->insertData($data);
            }
            if(!$id) return false;            
            $course['source'] = 2; // 断联系标识          
            $rid = $this->insertData($course);            
            if(!$rid) return false;
            array_push($push ,array($event['id'], $id));
        }      
        if(empty($result)) return true;
        return $result;
    }
}