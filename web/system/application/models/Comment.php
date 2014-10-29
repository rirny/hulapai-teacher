<?php
class CommentModel extends BaseModel{
	public $table = 't_comment';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function addCourseStudentComment($id,$event,array $data){
    	//是否点评过
    	$_CourseStudent = new Course_StudentModel();
    	$info = $_CourseStudent->getRow(array('id'=>$id));
    	if(!$info) return false;
    	if(!$data['pid'] && $info['commented'] == 1) return false;
    	$this->db->trans_begin();
    	$this->db->update('t_course_student',array('commented'=>1),array('id'=>$id));
		$this->db->insert('t_comment',$data);
		$commentId = $this->db->insert_id();
		$this->db->update('t_event',array('commented'=>1),array('id'=>$event));
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		   return false;
		}else{
		    $this->db->trans_commit();
		    return $commentId;
		}
    }
    
    public function getCommentList($page=1,$pagesize=20,$school=0,$where = '',$sort=''){
    	$sql = " from t_comment as a left join t_event as b on a.event = b.id".
    			" where b.id > 0 and a.school = $school and a.pid=0 and a.character in('teacher','school')";
    	if($where) $sql .=" $where";
    	if($sort) $sql .=" order by $sort";
    	$sqlCount = "SELECT count(1) as nums".$sql;
    	$total = $this->db->query($sqlCount)->row_array(1);
    	$total = $total['nums'];
    	$offset = $pagesize*($page-1);
		$pages = pages($total, $page, $pagesize);
		$sqlQuery =  "select a.id,a.creator,a.student,a.teacher,a.content,a.event,a.create_time,b.text,b.start_date,b.end_date".$sql." limit $offset,$pagesize";
    	$data = $this->db->query($sqlQuery)->result_array();
    	return array(
			'pages'=>$pages,
			'data'=>$data
		);	
    	
    }
    
    public function getCommentInfo($id){
    	$commentInfo = $this->getRow(array('id'=>$id));
    	if($commentInfo){
    		$commentInfo['_id'] = $commentInfo['id'];
    		$_Student = new StudentModel();
    		$_User = new UserModel();
    		$commentInfo['student'] = $_Student->getRow(array('id'=>$commentInfo['student']),'id as _id,name,avatar');
    		$commentInfo['teacher'] = $_User->getRow(array('id'=>$commentInfo['teacher']),'id as _id,firstname,lastname,nickname,avatar');
    	}
    	return $commentInfo;
    }
}