<?php
class School_StudentModel extends BaseModel{
	public $table = 't_school_student';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getSchoolStudentByStudentName($school,$studentName="",$status=-1){
		$sql =  "SELECT a.*,a.status,b.name,b.nickname,b.gender FROM (`t_school_student` AS a)" .
    			" JOIN `t_student` AS b ON `a`.`student` = `b`.`id`" .
    			" where a.school = $school";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	$sql .= " and b.name like '%$studentName%'";
    	return $this->db->query($sql)->result_array();
    }
    
    public function getSchoolStudentList($page=1,$pagesize=20,$school=0,$studentName="",$status=-1,$grades=""){
		$sql =  " FROM (`t_school_student` AS a)" .
    			" JOIN `t_student` AS b ON `a`.`student` = `b`.`id`" ;
    	if($grades) $sql .=  " JOIN `t_grade_student` AS c ON `a`.`student` = `c`.`student`";
    	$sql .=  " where a.school = $school";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	if($grades){
    		$sql .= " and c.grade in ($grades)";
    	}
    	if($studentName) $sql .= " and b.name like '%$studentName%'";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery =  "SELECT a.*,a.status,b.name,b.nickname,b.gender".$sql." limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    public function getSchoolStudent($school,$select = true,$simple=0,$avater=0){
		$sql = "SELECT a.student,b.name,LEFT(b.name_en,1) AS name_en,GROUP_CONCAT(c.grade) AS grade FROM t_school_student AS a LEFT JOIN t_student AS b ON a.student=b.id LEFT JOIN t_grade_student AS c ON a.school=c.school AND a.student=c.student WHERE a.school=$school AND a.status=0 GROUP BY a.student ORDER BY LEFT(b.name_en,1)";
		$result =  $this->db->query($sql)->result_array();
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$_result['grade'] = $_result['grade'] ? explode(',',$_result['grade']) : array();
				if($_result['name']){
					if($simple){
						$data[$_result['student']] = $_result['name'];
					}else{
						if($avater) $_result['avaterUrl'] = imageUrl($_result['student'],2,30,false);
						$data[$_result['student']] = $_result;
					}
				}
			}
		}
		return $data;
    }
    
    public function getStudentTotal($page=1,$pagesize=20,$school=0,$studentName='',$course=0,$start_date='',$end_date='',$sorts=''){
		if($studentName){
			$sqlStudent =  "select a.student from `t_school_student` AS a" .
	    			" LEFT JOIN `t_student` AS b ON `a`.`student` = `b`.`id`" .
	    			" where a.school = $school and locate('$studentName',b.name) > 0";
		}else{
			$sqlStudent =  "select a.student from `t_school_student` AS a where a.school = $school";
		}
		$students = $this->db->query($sqlStudent)->result_array();
		if(!$students) return false;
	    $student_ids = implode(',',array_column($students,'student'));
		$total = $this->getCount(array('school'=>$school,"student in ($student_ids)"=>null));
        $offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize?$pagesize:$total);
		$sql = " select b.school,a.student,a.event,b.class_time,a.attended,a.attend,a.leave,a.absence FROM `t_course_student` AS a". 
			   " LEFT JOIN `t_event` AS b  ON `a`.`event` = `b`.`id`".		 
               " WHERE a.student in ($student_ids) and a.source = 0 AND a.status = 0 AND b.school = $school AND b.is_loop = 0 AND b.rec_type ='' AND b.status=0 AND b.source = 0";
		if($course){
			$sql .= " and b.course = $course";
		}
		if($start_date){
			$sql .= " and b.end_date >= '$start_date 00:00:00'";
		}
		if($end_date){
			$sql .= " and b.start_date <= '$end_date 23:59:59'";
		}
		$sqlQuery = "select ss.school,ss.student,GROUP_CONCAT(tmp.event) AS event_ids,SUM(tmp.class_time) AS classes,COUNT(tmp.event) AS event_nums,SUM(tmp.`attend`*tmp.`attended`) AS `attend`,SUM(tmp.`leave`*tmp.`attended`) AS `leave`,SUM(tmp.`absence`*tmp.`attended`) AS `absence` from t_school_student as ss".
					" left join ($sql) as tmp on ss.student = tmp.student".
					" where ss.school = $school and ss.student in ($student_ids) GROUP BY ss.student";
		$sorts && $sqlQuery .=  " order by $sorts";
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    public function getStudentFeeTotal($page=1,$pagesize=20,$school=0,$studentName='',$money_start=0,$money_end=0,$sorts=''){
		if($studentName){
			$sqlStudent =  "select a.student from `t_school_student` AS a" .
	    			" LEFT JOIN `t_student` AS b ON `a`.`student` = `b`.`id`" .
	    			" where a.school = $school and locate('$studentName',b.name) > 0";
		}else{
			$sqlStudent =  "select a.student from `t_school_student` AS a where a.school = $school";
		}
		$students = $this->db->query($sqlStudent)->result_array();
		if(!$students) return false;
	    $student_ids = implode(',',array_column($students,'student'));
		$sql1 = "SELECT a.school,a.student,SUM(f.attend*a.fee) AS used FROM
				(
				SELECT fee,`to` AS school,student,`text` FROM t_student_fee WHERE `type` = 1 AND `to`=$school AND fee > 0 
				) AS a
				LEFT JOIN 
				(
				SELECT c.student,SUM(c.attend*c.attended) AS attend,SUM(c.leave*c.attended) AS `leave`,SUM(c.absence*c.attended) AS absence,SUM(c.attended) AS attended,d.school,d.text FROM t_course_student AS c LEFT JOIN t_event AS d ON c.event = d.id WHERE c.source = 0 AND c.status = 0 AND d.school = $school AND d.is_loop = 0 AND d.rec_type ='' AND d.status=0 AND d.source = 0 GROUP BY d.school,c.student,d.`text`
				) AS f 
				ON f.school= a.school AND f.student=a.student AND f.text = a.text 
				GROUP BY a.school,a.student";
		$sql2 = "SELECT SUM(money) AS money,school,student FROM t_student_money WHERE school=$school GROUP BY school,student";
		$sql = "SELECT m.school,m.student,IFNULL(n.money,0) as money,IFNULL(m.used,0) as used,(IFNULL(n.money,0) - IFNULL(m.used,0)) as balance FROM ($sql1) as m LEFT JOIN ($sql2) as n ON m.school=n.school AND m.student=n.student UNION SELECT n.school,n.student,IFNULL(n.money,0) as money,IFNULL(m.used,0) as used,(IFNULL(n.money,0) - IFNULL(m.used,0)) as balance FROM ($sql1) as m RIGHT JOIN ($sql2) as n ON m.school=n.school AND m.student=n.student";
		$sqlQuery = " from t_school_student as ss".
					" left join ($sql) as tmp on ss.student = tmp.student".
					" where ss.school = $school and ss.student in ($student_ids)";
		if($money_start){
			$sqlQuery .= " and IFNULL(tmp.money,0) >= $money_start";
		}
		if($money_end){
			$sqlQuery .= " and IFNULL(tmp.money,0) <= $money_end";
		}
		$total = $this->db->query("select count(1) as nums ".$sqlQuery)->row_array(1);
		$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sorts && $sqlQuery .=  " order by $sorts";
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query("select ss.student,ss.school,IFNULL(tmp.money,0) as money,IFNULL(tmp.used,0) as used,IFNULL(tmp.balance,0) as balance".$sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    
    public function createStudent($uid,$school,$name,$name_en,$parent_name,$phone){
    	$this->db->trans_begin();
    	$data = array(
    		'name'=>$name,
    		'name_en'=>$name_en,
    		'gender'=>1,
    		'create_time'=>time(),
    		'creator'=>$uid,
    		'source'=>1,
    		'parent_name'=>$parent_name,
    		'phone'=>$phone,
    	);
    	$this->db->insert('t_student',$data);
		$studentId = $this->db->insert_id();
		$data = array(
			'school'=>$school,
			'student'=>$studentId,
			'create_time'=>time(),
			'operator'=>$uid,
			'source'=>2
		);
		$this->db->insert('t_school_student',$data);
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return true;
		}
    }
    public function deleteStudent($school,$student){
    	//机构所有课程
    	$this->db->select('t_course_student.id as courseId,t_event.*')->from('t_course_student')
			->join('t_event','t_course_student.event = t_event.id')
			->where("t_event.school = $school AND t_course_student.student=$student");
		$eventInfos = $this->db->get()->result_array();
    	$this->db->trans_begin();
    	if($eventInfos){
    		$courseIds = implode(',',array_column($eventInfos,'courseId'));	
			$this->db->delete('t_course_student',"id in ($courseIds)");
    	}
    	//删除学生
		$this->db->delete('t_school_student',array('school'=>$school,'student'=>$student));
		//删除学生班级记录
		$this->db->delete('t_grade_student',array('school'=>$school,'student'=>$student));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($eventInfos){
		    	foreach($eventInfos as $eventInfo){
		    		event_push($eventInfo,array(),array($student),2,array(
		    		    		'act'=>'delete',
								'source' => array(
			                        'old'=>array(
			                            'text' => $eventInfo['text'], 'is_loop' => $eventInfo['is_loop'], 'rec_type' => $eventInfo['rec_type'],
			                            'start_date' => $eventInfo['start_date'], 'end_date' => $eventInfo['end_date'],'school' => $eventInfo['school'],
			                   		)
			                   	)
		        	));
		    	}
		    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
		    return true;
		}
    }
    
    
    public function import($account,$password,$name,$name_en,$school,$uid){
    	$now = time();
    	$_User = new UserModel();
    	$_Student = new StudentModel();
    	$_SchoolStudent = new School_StudentModel();	
    	$userInfo = $_User->getRow(array('account'=>$account));
    	$this->db->trans_begin();	
    	$importStatus = 0;
    	if(!$userInfo){
	    	//注册用户
	    	$login_salt = rand(10000,99999);
			$password = md5(md5($password) . $login_salt);
	    	$user = array(
	    		'account'=>$account,
				'password'=>$password,
				'login_salt'=>$login_salt,
				'create_time'=>$now,
				'setting'=>json_encode(array(
					"hulaid" => 0,
	                "friend_verify" => 1,
	                "notice" => array(
	                    "method" => 0,
	                    "types" => "1,2,3,4,5"
					)
				)),
				'last_login_time'=>0,
				'last_login_ip'=>getIp(),
				'source'=>2
	    	);
	    	
			$this->db->insert('t_user',$user);
			$id = $this->db->insert_id();	
			$this->db->update('t_user',array('hulaid'=>'h_' . sprintf("%u", crc32($id))),array('id'=>$id));
			$importStatus += 1;
    	}else{
    		$id = $userInfo['id'];	
    	}
    	$sqlQuery = "SELECT t_student.* FROM t_student LEFT JOIN t_user_student ON t_student.id = t_user_student.student LEFT JOIN t_user ON t_user.id = t_user_student.user WHERE t_user.id=$id AND t_student.name='".$name."'";
    	$studentInfo = $this->db->query($sqlQuery)->row_array(1);
    	if(!$studentInfo){
			//注册学生
			$studentData = array(
				'name'=>$name,
				'name_en'=>$name_en,
				'gender'=>0,
				'creator'=>$id,
				'create_time'=>$now,
				'source'=>2
			);
			$this->db->insert('t_student',$studentData);
			$student = $this->db->insert_id();
			$relationData = array(
				'relation'=>4,
				'user'=>$id,
				'student'=>$student,
				'create_time'=>$now,
			);
			$this->db->insert('t_user_student',$relationData);
			$importStatus += 2;
    	}else{
    		$student = $studentInfo['id'];
    	}
		if(!$_SchoolStudent->getRow(array('school'=>$school,'student'=>$student))){
    		//关联机构
			$data = array(
				'school'=>$school,
				'student'=>$student,
				'create_time'=>$now,
				'operator'=>$uid
			);
			$this->db->insert('t_school_student',$data);
			$importStatus += 4;
    	}
			
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    return $importStatus;
		}
    }
}