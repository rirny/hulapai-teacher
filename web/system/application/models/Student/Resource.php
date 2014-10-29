<?php
class Student_ResourceModel extends BaseModel{
	public $table = 't_student_resource';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getStudentResourceList($page=1,$pagesize=20,$school=0,$studentName="",$gender=0,$source=-1, $status=-1, $sorts=''){
		$sql =  " FROM (`t_student_resource`)" .
    			" where school = $school";
    	if($gender > 0){
    		$sql .= " and gender = $gender";
    	}
    	if($source >= 0){
    		$sql .= " and source = $source";
    	}
    	$status >= 0  && $sql .= " and `status` = $status";
    	if($studentName) $sql .= " and name like '%$studentName%'";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery = "SELECT *".$sql;
		if($sorts) $sqlQuery .= " Order By " . $sorts;
		$sqlQuery .= " limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
		$year = (int)date('Y');
		foreach($data as & $item)
		{
			$item['parents'] = json_decode($item['parents'], true);
			$item['course'] = json_decode($item['course'], true);
			$birthYear = (int)substr($item['birthday'], 0,4);
			$item['age'] = ($birthYear == 0 || $year == $birthYear) ? '-' : $year - $birthYear;			
		}
    	return array(
			'records' => $total,
			'pages'=>$pages,
			'data'=>$data
		);	
    }

	public function doSign($resource, $school, $password){
		if(!$resource || $password == '' || !$school) return false;
		$resource['parents'] = json_decode($resource['parents'], true);				
		$users = Array();		
		foreach($resource['parents'] as & $parent)
		{
			$user = $this->isExistAccount($parent['mobile']);
			$user || $user = $this->createUser($parent['mobile'], $parent['name'], $password);
			$users[] = $user;
			$parent['id'] = $user;
		}	
		// 档案		
		$student = $this->isExistStudentRecord($resource['name'], $users);
		$student || $student = $this->createStudent($resource);		
		if($this->createUserRelation($student, $resource['parents']) && $this->createSchoolRelation($student, $school))
		{

			$this->updateData(array('status' => 1), array('id' => $resource['id']));
			return true;
		}
		return false;
	}

	// 
	private function createStudent($resource)
	{
		$parent = current($resource['parents']);
		$data = array(
			'name' => $resource['name'],
			'gender' => $resource['name'],
			'birthday' => $resource['birthday'],
			'creator' => $parent['id'],
			'create_time' => time()
		);
		$_Student = new StudentModel();
		return $_Student->insertData($data);		
	}

	// 创建用户
	private function createUser($account, $name, $password)
	{
		if($account=='' || $name=='' || $password=='') return false;
		$login_salt = '12345';
		$data = array(
			'account' => $account,
			'nickname'=> $name,
			'login_salt' => $login_salt,
			'nickname_en' => Ustring::topinyin($name),
			'password' => md5($password . $login_salt),
			'create_time' => time()
		);		
		$_User = new UserModel();
		$id = $_User->insertData($data);
		$_User->updateData(array('hulaid'=>'S_' . sprintf("%u", crc32($id))), array('id'=>$id));
		return $id;
	}

	// 是否存在学生档案
	private function isExistStudentRecord($name, $user = array())
	{
		if(!$name || !$user) return false;
		$userStr = implode(",", $user);		
		$res = $this->db->query("select s.id from t_user_student r left join t_student s on s.id=r.student where r.`user` in ({$userStr}) And s.name='{$name}' limit 1")->row_array(1);
		if($res) return $res['id'];
		return false;
	}
	//
	private function isExistAccount($account)
	{		
		$_User = new UserModel();
		$res = $_User->getRow(array('account' => $account));		
		if(!empty($res)) return $res['id'];
		return false;
	}
	private function createUserRelation($student, $parents)
	{
		$tm = time();
		$_User_student = new User_StudentModel();
		foreach($parents as $item)
		{
			$res = $_User_student->getRow(array('user' => $item['id'], 'student' => $student));
			if(!$res)
			{
				if($item['relation'] != 4 && $_User_student->getRow(array('student' => $student, 'relation' => $item['relation'])))
				{
					$item['relation'] = 4; // 已经存在的关系，将关系转成其他
				}
				 $_User_student->insertData(array(
					'user' => $item['id'],
					'student' => $student,
					'relation' => $item['relation'],
					'create_time' => $tm
				));
			}
		}
		return true;
	}
	private function createSchoolRelation($student, $school)
	{
		$tm = time();
		$_School_Student = new School_StudentModel();
		$res = $_School_Student->getRow(array('school' => $school, 'student' => $student));
		if(!$res)
		{
			 $_School_Student->insertData(array(
				'school' => $school,
				'student' => $student,				
				'create_time' => $tm
			));
		}
		return true;
	}
}