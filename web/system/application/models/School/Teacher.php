<?php
class School_TeacherModel extends BaseModel{
	public $table = 't_school_teacher';
	public function __construct() {
    	parent::__construct();
    } 
    
     public function getSchoolTeacherByTeacherName($school,$teacherName="",$status=-1){
		$sql =  "SELECT a.*,b.firstname,b.lastname,b.gender,b.account FROM (`t_school_teacher` AS a)" .
    			" JOIN `t_user` AS b ON `a`.`teacher` = `b`.`id`" .
    			" where a.school = $school";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	$sql .= " and (b.firstname like '%$teacherName%' or b.lastname like '%$teacherName%')";
    	return $this->db->query($sql)->result_array();
    }
    
    public function getSchoolTeacherList($page=1,$pagesize=20,$school=0,$teacherName="",$status=-1){
		$sql =  " FROM (`t_school_teacher` AS a)" .
    			" JOIN `t_user` AS b ON `a`.`teacher` = `b`.`id`" .
    			" where a.school = $school";
    	if($status >= 0){
    		$sql .= " and a.status = $status";
    	}
    	if($teacherName) $sql .= " and (b.firstname like '%$teacherName%' or b.lastname like '%$teacherName%')";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery =  "SELECT a.*,b.firstname,b.lastname,b.gender,b.account".$sql." limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    }
    
    public function getSchoolTeacher($school,$select = true,$simple =0,$avater=0){
		$sql = "SELECT a.teacher,b.firstname,b.lastname,LEFT(b.firstname_en,1) AS name_en,b.account,b.hulaid,GROUP_CONCAT(c.group) AS `group` FROM t_school_teacher AS a LEFT JOIN t_user AS b ON a.teacher=b.id LEFT JOIN t_school_group_teacher AS c ON a.school=c.school AND a.teacher=c.teacher WHERE a.school=$school AND a.status=0 GROUP BY a.teacher order by LEFT(b.firstname,1)";
		$result =  $this->db->query($sql)->result_array();
		if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$_result['group'] = $_result['group'] ? explode(',',$_result['group']) : array();
				$teacherName = ($_result['firstname'] || $_result['lastname']) ? $_result['firstname'].$_result['lastname'] : $_result['nickname'];
				if($teacherName){
					if($simple){
						$data[$_result['teacher']] = $teacherName;
					}else{
						if($avater) $_result['avaterUrl'] = imageUrl($_result['teacher'],1,30,false);
						$data[$_result['teacher']] = $_result;
					}
				}
			}
		}
		return $data;
    }
    
    public function getTeacherTotal($page=1,$pagesize=20,$school=0,$teacherName='',$course=0,$start_date='',$end_date='',$sorts=''){
		if($teacherName){
			$sqlTeacher =  "select a.teacher from `t_school_teacher` AS a" .
	    			" LEFT JOIN `t_user` AS b ON `a`.`teacher` = `b`.`id`" .
	    			" where a.school = $school and (locate('$teacherName',b.firstname) > 0 or locate('$teacherName',b.lastname) > 0)";
		}else{
			$sqlTeacher =  "select a.teacher from `t_school_teacher` AS a where a.school = $school";
		}
		$teachers = $this->db->query($sqlTeacher)->result_array();
		if(!$teachers) return false;
	    $teacher_ids = implode(',',array_column($teachers,'teacher'));
		$total = $this->getCount(array('school'=>$school,"teacher in ($teacher_ids)"=>null));
        $offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize?$pagesize:$total);
		$sql = " select b.school,a.teacher,a.event,b.class_time FROM `t_course_teacher` AS a". 
			   " LEFT JOIN `t_event` AS b  ON `a`.`event` = `b`.`id`".		 
               " WHERE a.teacher in ($teacher_ids) AND a.status = 0 AND b.school = $school AND b.is_loop = 0 AND b.rec_type ='' AND b.status=0 AND b.source = 0";
		if($course){
			$sql .= " and b.course = $course";
		}
		if($start_date){
			$sql .= " and b.end_date >= '$start_date 00:00:00'";
		}
		if($end_date){
			$sql .= " and b.start_date <= '$end_date 23:59:59'";
		}
		$sqlQuery = "select st.school,st.teacher,GROUP_CONCAT(tmp.event) AS event_ids,SUM(tmp.class_time) AS classes,COUNT(tmp.event) AS event_nums".
					" from t_school_teacher as st left join ($sql) as tmp on st.teacher = tmp.teacher  where st.school = $school and st.teacher in ($teacher_ids) GROUP BY st.teacher";
		$sorts && $sqlQuery .=  " order by $sorts";
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
		
		
		$teacher_ids = '';
		if($teacherName){
			$sqlteacher =  "select GROUP_CONCAT(a.teacher) as teacher_ids from `t_school_teacher` AS a" .
	    			" LEFT JOIN `t_user` AS b ON `a`.`teacher` = `b`.`id`" .
	    			" where a.school = $school and (locate('$teacherName',b.firstname) > 0 or locate('$teacherName',b.lastname) > 0)";
	    	$teachers = $this->db->query($sqlteacher)->row_array(1);
	    	$teacher_ids = $teachers['teacher_ids'];
		}
		$sql =" FROM `t_school_teacher` AS a".
			  " LEFT JOIN `t_course_teacher` AS b  ON a.teacher = b.teacher".
			  " LEFT JOIN `t_event` AS c  ON c.id = b.event AND c.school = a.school".
			  " WHERE a.school = $school";
		if($teacherName){
			if($teacher_ids){
				$sql .=" AND a.teacher in ($teacher_ids)";
			}else{
				return false;
			}
		}
		$sql .=" AND ((b.event IS NULL AND c.id IS NULL) OR (c.is_loop = 0 AND c.`status` = 0 AND c.rec_type = '' AND c.source = 0";
		if($course){
			$sql .= " and c.course = $course";
		}
		if($start_date){
			$sql .= " and c.end_date >= '$start_date 00:00:00'";
		}
		if($end_date){
			$sql .= " and c.start_date <= '$end_date 23:59:59'";
		}
		$sql .= ")) GROUP BY a.teacher";
		$sqlCount = "select count(1) as nums from (SELECT a.teacher $sql) AS t";
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize?$pagesize:$total);
		$sqlQuery =  "SELECT a.school,a.teacher,GROUP_CONCAT(c.id) as event_ids,sum(c.class_time) as classes,count(c.id) as event_nums".$sql;
		$sorts && $sqlQuery .=  " order by $sorts";
		$pagesize && $sqlQuery .=  " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);
    }
    
    public function deleteTeacher($school,$teacher){
		//机构所有课程
    	$this->db->select('t_course_teacher.id as courseId,t_event.*')->from('t_course_teacher')
			->join('t_event','t_course_teacher.event = t_event.id')
			->where("t_event.school = $school AND t_course_teacher.teacher=$teacher");
		$eventInfos = $this->db->get()->result_array();
    	$this->db->trans_begin();
    	if($eventInfos){
    		$courseIds = implode(',',array_column($eventInfos,'courseId'));	
			$this->db->delete('t_course_teacher',"id in ($courseIds)");
    	}
    	$this->db->trans_begin();
    	
		$this->db->delete('t_school_teacher',array('school'=>$school,'teacher'=>$teacher));
		$this->db->delete('t_school_group_teacher',array('school'=>$school,'teacher'=>$teacher));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($eventInfos){
		    	foreach($eventInfos as $eventInfo){
		    		event_push($eventInfo,array($teacher),array(),2,array(
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
    
    
    
    public function import($account,$password,$firstname,$firstname_en,$lastname,$lastname_en,$address,$school,$uid){
    	$now = time();
    	$_User = new UserModel();
    	$_Teacher = new TeacherModel();
    	$_SchoolTeacher = new School_TeacherModel();	
    	$userInfo = $_User->getRow(array('account'=>$account));
    	$this->db->trans_begin();	
    	$importStatus = 0;
    	if(!$userInfo){
	    	//注册用户
	    	$login_salt = rand(10000,99999);
			$password = md5(md5($password) . $login_salt);
	    	$user = array(
	    		'account'=>$account,
	    		'firstname'=>$firstname,
	    		'firstname_en'=>$firstname_en,
	    		'lastname'=>$lastname,
	    		'lastname_en'=>$lastname_en,
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
				'teacher'=>1,
				'source'=>2
	    	);
			$this->db->insert('t_user',$user);
			$id = $this->db->insert_id();	
			$this->db->update('t_user',array('hulaid'=>'h_' . sprintf("%u", crc32($id))),array('id'=>$id));
			$importStatus += 1;
    	}else{
	    	$id = $userInfo['id'];
	    	$this->db->update('t_user',array(
	    		'firstname'=>$firstname,
	    		'firstname_en'=>$firstname_en,
	    		'lastname'=>$lastname,
	    		'lastname_en'=>$lastname_en,
				'teacher'=>1,
	    	),array('id'=>$id));	
    	}
    	if(!$_Teacher->getRow(array('user'=>$id))){
    		//注册老师
			$teacherData = array(
				'user'=>$id,
				'create_time'=>$now,
				'address'=>$address,
				'source'=>2
			);
			$this->db->insert('t_teacher',$teacherData);
			$this->db->update('t_user',array('teacher'=>1),array('id'=>$id));
			$importStatus += 2;
    	}
    	if(!$_SchoolTeacher->getRow(array('school'=>$school,'teacher'=>$id))){
    		//关联机构
			$data = array(
				'school'=>$school,
				'teacher'=>$id,
				'create_time'=>$now,
				'operator'=>$uid
			);
			$this->db->insert('t_school_teacher',$data);
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