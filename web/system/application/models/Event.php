<?php
class EventModel extends BaseModel{
	public $table = 't_event';
	public function __construct() {
    	parent::__construct();
    } 
    
    public function getSchoolEvent($school,$select=true){
    	$result = $this->getAll(array('school'=>$school,'pid'=>0,'source'=>0),'id,text');
    	if(!$select) return $result;
		$data = array();
		if($result){
			foreach($result as $_result){
				$data[$_result['id']] = $_result['text'];
			}
		}
		return $data;
    }
    
    private function teacher_student($teacherIds=array(),$studentIds=array(),$type=1,$ext=0){
    	if(!$teacherIds || !$studentIds) return false;
    	$teacherIds = array_unique(array_filter($teacherIds));
    	$studentIds = array_unique(array_filter($studentIds));
    	if(!$teacherIds || !$studentIds) return false;
    	$nowTime = time();
    	$_Teacher_Grade = new Teacher_StudentModel();
    	foreach($teacherIds as $teacherId){
    		foreach($studentIds as $studentId){
    			if(!$_Teacher_Grade->getRow(array('teacher'=>$teacherId,
				'student'=>$studentId,
				'type'=>$type,
				'ext'=>$ext))){
	    			$_Teacher_Grade->insertData(array(
	    				'teacher'=>$teacherId,
	    				'student'=>$studentId,
	    				'type'=>$type,
	    				'ext'=>$ext,
	    				'create_time'=>$nowTime
	    			));
				}
    		}
    	}
    }
    /**
     * 新建课程
     */
    public function createEvent(array $eventData,array $teachers,array $students,$pushType = 0,$push=true){
    	$eventKeys = array('course','text','start_date','end_date','rec_type','length','grade','school','color','description','creator','create_time','class_time','is_loop','lock');
		if(array_diff($eventKeys, array_keys($eventData)) || empty($teachers) || empty($students)) return false;
		$this->db->trans_begin();
		//插入课程
		$this->db->insert('t_event',$eventData);
		$eventId = $this->db->insert_id();
		$eventData['id'] = $eventId;
		//插入老师
		$_eventTeacher = array(
			'event'=>$eventId,
			'remark'=>$eventData['text'],
			'color'=>$eventData['color'],
		);
		$teacherIds = array();
		foreach($teachers as $teacher=>$priv){
			$teacherIds[] = $teacher;
			$_eventTeacher['teacher'] = $teacher;
			$_eventTeacher['priv'] = $priv;
			$this->db->insert('t_course_teacher',$_eventTeacher);
		}
		//插入学生
		$_eventStudent = array(
			'event'=>$eventId,
			'start_date'=>$eventData['start_date'],
			'end_date'=>$eventData['end_date'],
			'color'=>$eventData['color'],
			'remark'=>$eventData['text'],
			'fee'=>100,
		);
		$students = array_unique($students);
		$studentIds = array();
        foreach($students as $student){
            $studentIds[] = $student;
            $_eventStudent['student'] = $student;
            $this->db->insert('t_course_student',$_eventStudent);
            // 创建班级关系
            if($eventData['grade']){
                $this->db->insert('t_event_grade',array(
                    'event' => $eventId,
                    'grade' => $eventData['grade'],
                    'student' => $student,
                    'teacher' =>  $eventData['teacher']                      
                ));
            }
        }
        $this->teacher_student($teacherIds,$studentIds,1,$eventData['school']);
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($push){
	        	$this->push($eventData,$teacherIds,$studentIds,$pushType);
	        }
		    return $eventId;
		}
    }
    
    /**
     * 生成子课程
     */
    public function rec_create($pid, $length,$push = false){
        if(!$pid || $length < 0) return false;
        //父课程不存在
        $parent = $this->getRow(array('id' => $pid));
        if(!$parent) return false;
        //获取父课程的老师，学生
        $_CourseTeacher = new Course_TeacherModel();
        $teachers = $_CourseTeacher->getAll(array('event' => $parent['id']),'teacher,priv');
        $_CourseStudent = new Course_StudentModel();
        $students = $_CourseStudent->getAll(array('event' => $parent['id']),'student,start_date,end_date');
        $_EventGrade = new Event_GradeModel();
        //子课程未生成,则生成
        $child = $this->getRow(array('pid' => $pid, 'length' => $length));   
        if(!$child){ 
            $child = $this->virtual($parent, $length);
            if(!$child) return false;
            unset($child['id']);
            $this->db->trans_begin();
            $this->db->insert('t_event',$child);
            $id = $this->db->insert_id();
            $child['id'] = $id;
        }else{
        	$this->db->trans_begin();
        }
        //检测子课程老师
        $teacherIds = array();
        if($teachers){
        	//老师模型
			$_eventTeacher = array(
				'event'=>$child['id'],
				'remark'=>$child['text'],
				'color'=>$child['color'],
			);
        	foreach ($teachers as $teacher){
	            $teacherIds[] = $teacher['teacher'];
	            //子课程老师信息
	            $tmp = $_CourseTeacher->getRow(array('event' => $child['id'], 'teacher' => $teacher['teacher']));
	            //子课程老师已生成
	            if($tmp) continue;
	            //子课程未生成,则生成
	            $_eventTeacher['teacher'] = $teacher['teacher'];
				$_eventTeacher['priv'] = $teacher['priv'];
	            $this->db->insert('t_course_teacher',$_eventTeacher);
	        }
        }
      	//检测子课程学生
      	$studentIds = array();
      	if($students){
	        $_eventStudent = array(
	        	'event'=>$child['id'],
				'start_date'=>$child['start_date'],
				'end_date'=>$child['end_date'],
				'color'=>$child['color'],
				'remark'=>$child['text'],
				'fee'=>100,
	        );
	        foreach ($students as $student){            
	            $studentIds[] = $student['student'];
	            //子课程学生信息
	            $tmp = $_CourseStudent->getRow(array('event' => $child['id'], 'student' => $student['student'])); 
	            //子课程学生已生成
	            if($tmp) continue;
	            //子课程未生成,则生成
	            //父课程还没开始
	            if($student['start_date'] != '0000-00-00 00:00:00' && strtotime($student['start_date']) > $length) continue;
	            //父课程已经结束（开始时间结束变化过的不再生成！）
				if($student['end_date'] != '0000-00-00 00:00:00' && strtotime($student['end_date']) < ($length + $parent['length'])) continue; 
				$_eventStudent['student'] = $student['student'];
	            $this->db->insert('t_course_student',$_eventStudent);
	            // 创建班级关系
	            if($child['grade']){
	                if(!$_EventGrade->getRow(array('event' => $child['id'], 'student' => $student['student'],'grade'=>$child['grade']))){
		                $this->db->insert('t_event_grade',array(
		                    'event' => $child['id'],
		                    'grade' => $child['grade'],
		                    'student' => $student['student'],
		                    'teacher' =>  $child['teacher']                      
		                ));
	                }
	            }         
	        }
      	}
      	$this->teacher_student($teacherIds,$studentIds,1,$parent['school']);
      	if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($push){
	        	$this->push($child,$teacherIds,$studentIds);
	        }
	        return $child;
      	}
    }
    /**
     * 课程修改
     */
    public function updateEvent($id,array $eventData,array $teachers,array $students,array $clear,$whole = 0,array $old,$pushType = 0,$push=true){
    	$eventKeys = array('course','text','start_date','end_date','rec_type','length','grade','school','color','description','creator','create_time','class_time','is_loop','lock');
		if(!$id || array_diff($eventKeys, array_keys($eventData)) || empty($teachers) || empty($students) || empty($old)) return false;
        $_EventGrade = new Event_GradeModel();
        if($old['is_loop'] && $whole == 0){
        	$compare = $this->compare($id,array_keys($teachers),array_values($students),true);
        }else{
        	$compare = $this->compare($id,array_keys($teachers),array_values($students));
        }
		$this->db->trans_begin();
    	$this->db->update('t_event',$eventData,array('id'=>$id));
    	$eventData['id'] = $id;
    	$pushData = array();
    	//清理
    	if($clear['event']){
    		$eventIdStr = implode(',',$clear['event']);
    		//删除子课程
            $this->db->delete('t_event',"id in ($eventIdStr)");
    	}
    	if($clear['teacher_course']){
    		$idStr = implode(',',$clear['teacher_course']);
    		//删除子课程
            $this->db->delete('t_course_teacher',"id in ($idStr)");
    	}  
    	if($clear['student_course']){
    		$idStr = implode(',',$clear['student_course']);
    		//删除子课程
            $this->db->delete('t_course_student',"id in ($idStr)");
    	}    
    	$_eventTeacher = array(
			'event'=>$eventData['id'],
			'remark'=>$eventData['text'],
			'color'=>$eventData['color'],
		); 
		if($compare['teacher']['new']){
            foreach($compare['teacher']['new'] as $teacher){                        
                //生成学生-课程关系 
                $_eventTeacher['teacher'] = $teacher;
                $_eventTeacher['priv'] = $teachers[$teacher];
                $this->db->insert('t_course_teacher',$_eventTeacher);  
                $this->db->delete('t_delete_logs',array('app'=>'event','to'=>$teacher,'student'=>0,'ext'=>$_eventTeacher['event']));                     
            } 
            $pushData[] = array($compare['teacher']['new'],array(),2,array());
		}
		if($compare['teacher']['lost']){
			$teacherIdStr = implode(',',$compare['teacher']['lost']);
            //删除子课程老师
            $this->db->delete('t_course_teacher',"event = $id and teacher in ($teacherIdStr)");                         
	        $pushData[] = array($compare['teacher']['lost'],array(),2,array(
        		'act' => 'delete',
        		'source' => array(
                        'old'=>array(
                            'text' => $old['text'], 'is_loop' => $old['is_loop'], 'rec_type' => $old['rec_type'],
                            'start_date' => $old['start_date'], 'end_date' => $old['end_date'],'school' => $old['school'],
                   		)
        		)
        	));
		}
		if($compare['teacher']['keep']){
			foreach($compare['teacher']['keep'] as $teacher){                        
                $this->db->update('t_course_teacher',array('priv'=>$teachers[$teacher]),array('event'=>$id,'teacher'=>$teacher));                     
            } 
            if($push){
				$pushData[] = array($compare['teacher']['keep'],array(),$pushType,array(
	        		'act' => 'update',
	        		'source' => array(
	                        'old'=>array(
	                            'text' => $old['text'], 'is_loop' => $old['is_loop'], 'rec_type' => $old['rec_type'],
	                            'start_date' => $old['start_date'], 'end_date' => $old['end_date'],'school' => $old['school'],
	                   		)
	        		)
	        	));
            }
		}  
		$_eventStudent = array(
        	'event'=>$eventData['id'],
			'start_date'=>$eventData['start_date'],
			'end_date'=>$eventData['end_date'],
			'color'=>$eventData['color'],
			'remark'=>$eventData['text'],
			'fee'=>100,
        );
        //最近课程
        $recentEventInfo = $this->recent($eventData,'right');
		if($compare['student']['new']){
			if($whole || $eventData['is_loop'] == 0){
                foreach($compare['student']['new'] as $student){                        
                    //生成学生-课程关系 
                    $_eventStudent['student'] = $student;
                    $this->db->insert('t_course_student',$_eventStudent);
                    $this->db->delete('t_delete_logs',array('app'=>'event','student'=>$student,'ext'=>$_eventStudent['event']));                        
                }  
            }else{
                if($recentEventInfo){
                    foreach($compare['student']['new'] as $student){                        
                        $_eventStudent['student'] = $student;
                        //生成学生-课程关系 
                        $this->db->insert('t_course_student',array_merge($_eventStudent, array('start_date' => $recentEventInfo['start_date']))); 
                        $this->db->delete('t_delete_logs',array('app'=>'event','student'=>$student,'ext'=>$_eventStudent['event']));                    
                    }
                }
            }
            // 创建班级关系
            if($eventData['grade']){
                foreach($compare['student']['new'] as $student){ 
                	if(!$_EventGrade->getRow(array('event' => $eventData['id'], 'student' => $student,'grade'=>$eventData['grade']))){
	                	$this->db->insert('t_event_grade',array(
		                    'event' => $eventData['id'],
		                    'grade' => $eventData['grade'],
		                    'student' => $student,
		                    'teacher' =>  $eventData['teacher']                      
		                ));
                	}
                }
            }
            $pushData[] = array(array(),$compare['student']['new'],2,array());          
		}
		 //最近课程
        $recentEventInfo = $this->recent($eventData,'left');
		if($compare['student']['lost']){
			$studentIdStr = implode(',',$compare['student']['lost']);
			if($whole || $eventData['is_loop'] == 0){
                //删除子课程
                $this->db->delete('t_course_student',"event = $id and student in ($studentIdStr)");                      
            }else{
                if($recentEventInfo){
                    //修改生成学生-课程关系
                    $this->db->update('t_course_student',array('end_date' => $recentEventInfo['end_date']),"event = $id and student in ($studentIdStr)");    
                }
            }
            // 删除班级关系
            if($eventData['grade']){
            	$this->db->delete('t_event_grade',"event = $id and student in ($studentIdStr) and grade = ".$eventData['grade']);
            }   
            $pushData[] = array(array(),$compare['student']['lost'],2,array(
        		'act' => 'delete',
        		'source' => array(
                        'old'=>array(
                            'text' => $old['text'], 'is_loop' => $old['is_loop'], 'rec_type' => $old['rec_type'],
                            'start_date' => $old['start_date'], 'end_date' => $old['end_date'],'school' => $old['school'],
                   		)
        		)
        	));   
		}
		if($compare['student']['keep']){
			$studentIdStr = implode(',',$compare['student']['keep']);
	        if($eventData['is_loop'] && $whole){                    
	           	//更新记录 
	            $this->db->update('t_course_student',array('start_date' => '', 'end_date' => ''),"event = $id and student in ($studentIdStr)"); 
	        }
	        if($push){
		        $pushData[] = array(array(),$compare['student']['keep'],$pushType,array(
	        		'act' => 'update',
	        		'source' => array(
	                        'old'=>array(
	                            'text' => $old['text'], 'is_loop' => $old['is_loop'], 'rec_type' => $old['rec_type'],
	                            'start_date' => $old['start_date'], 'end_date' => $old['end_date'],'school' => $old['school'],
	                   		)
	        		)
	        	)); 
	        }
		} 
		$this->teacher_student(array_keys($teachers),array_values($students),1,$eventData['school']);
		if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($pushData){
		    	foreach($pushData as $_pushData){
		    		$this->push($eventData,$_pushData[0],$_pushData[1],$_pushData[2],$_pushData[3], $whole);
		    	}
		    }
		   return true;
      	}  
    }
    
    /**
     * 删除课程
     */
    public function deleteEvent($id,array $eventData,array $clear,$whole = 0,$pushType = 0,$push=true){
    	if(!$id || empty($eventData) || $id != $eventData['id']) return false;
    	 //获取课程的老师，学生
        $_CourseTeacher = new Course_TeacherModel();
        $teachers = $_CourseTeacher->getAll(array('event' => $id),'teacher,priv');
        $teacherIds = array();
        if($teachers){
            foreach($teachers as $teacher){
            	$teacherIds[] = $teacher['teacher'];
            }
        }
        $this->db->trans_begin();
        //清理
    	if($clear['event']){
    		$eventIdStr = implode(',',$clear['event']);
    		//删除子课程
            $this->db->delete('t_event',"id in ($eventIdStr)");
    	}
    	if($clear['teacher_course']){
    		$idStr = implode(',',$clear['teacher_course']);
    		//删除子课程
            $this->db->delete('t_course_teacher',"id in ($idStr)");
    	}  
    	if($clear['student_course']){
    		$idStr = implode(',',$clear['student_course']);
    		//删除子课程
            $this->db->delete('t_course_student',"id in ($idStr)");
    	}    
        $teacherIdsStr = implode(',',$teacherIds);
        $_CourseStudent = new Course_StudentModel();
        //已结束的学员不取
        if($eventData['is_loop'] && $whole == 0){
			// 结束时间必须大于循环课程的时间
			$this->db->select('t_course_student.student,t_course_student.start_date,t_course_student.end_date')->from('t_course_student')
				->join('t_event','t_course_student.event = t_event.id')
				->where("t_event.status = 0 and t_event.id = $id and t_event.is_loop = 1 and (t_course_student.end_date='0000-00-00 00:00:00' or t_course_student.end_date >= t_event.start_date)");
			$students = $this->db->get()->result_array();
		}else{
			$students = $_CourseStudent->getAll(array('event' => $id),'student,start_date,end_date');
		}
        $studentIds = array();
        if($students){
            foreach($students as $student){
            	$studentIds[] = $student['student'];
            }
        }
        $studentIdsStr = implode(',',$studentIds);
        if($eventData['is_loop'] && $whole == 0){
            //最近课程
        	$recent = $this->recent($eventData,'right');
            if(!empty($recent['end_date']) && $recent['end_date'] < $eventData['end_date']){
                $this->db->update('t_event',array('end_date' => $recent['end_date']),array('id'=>$id));
                if($studentIdsStr){
                	$this->db->update('t_course_student',array('end_date' => $recent['end_date']),"event = $id and end_date > '".$recent['end_date']."' and student in ($studentIdsStr)");
                }
            }
        }else{               
            //子课程
            if($eventData['pid']){                   
                $this->db->update('t_event',array('rec_type' => 'none'),array('id'=>$id));
            }else {                    
                $this->db->delete('t_event',array('id'=>$id));                  
                if($studentIdsStr){
                	$this->db->delete('t_course_student',"event = $id and student in ($studentIdsStr)");
                } 
                if($teacherIdsStr){
                	$this->db->delete('t_course_teacher',"event = $id and teacher in ($teacherIdsStr)");
                }                     
            }     
        }
        if ($this->db->trans_status() === FALSE){
		    $this->db->trans_rollback();
		    return false;
		}else{
		    $this->db->trans_commit();
		    if($push){
	        	$this->push($eventData,$teacherIds,$studentIds,$pushType,array(
	        		'act' => 'delete',
	        		'source' => array(
	                        'old'=>array(
	                            'text' => $eventData['text'], 'is_loop' => $eventData['is_loop'], 'rec_type' => $eventData['rec_type'],
	                            'start_date' => $eventData['start_date'], 'end_date' => $eventData['end_date'],'school' => $eventData['school'],
	                   		)
	        		)
	        	), $whole);
	        }
		   return true;
      	}  
    }
    
    /**
     * 取循环课程新近课程
     */ 
    public function recent($event, $forward='left'){       
        if(empty($event['is_loop'])) return array();
        // 取此循环的第一节课
		$first = $this->getRow(array('pid' => $event['id']),'`length`', '`length` asc');
		$start = strtotime($event['start_date']);
		if($first && $first['length'] < $start){
			$time = date('H:i', $start);
			$date = date('Y-m-d', $first['length']);
			$event['start_date'] = $date . " " . $time;
		}				
        $rec = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);				
        $tm = strtotime(date('Y-m-d'));
        // 取最后一节已上课程
        if($forward == 'left'){
            $result = array();
            while(list($key, $val) = each($rec))
            {
                if($val['length'] <= $tm){
                    $result = $val;
                }                   
            }
            return $result;
        // 取第一个未上课程			
        }else{
            while(list($key, $val) = each($rec))
            {
                if($val['length'] >= $tm)
				{
					$result = $val;
					
					return $val;
				}
            }			
        }
        return array();
    }
    /**
     * 循环课程清理  
     */
	public function rec_clear($pid, $whole=0)
	{         
		if(!$pid || !is_numeric($pid)) return false;      
		$_CourseTeacher = new Course_TeacherModel();
        $_CourseStudent = new Course_StudentModel();	
		$where = array('pid' => $pid, 'status' => 0);
        $tm = date('Y-m-d H:i:s');
		$whole || $where['start_date >'] = $tm;        
		$removes = $this->getAll($where,'id');    
		$data = array();  
		if($removes){
            $removeEventIds = array();
            foreach($removes as $remove){
            	$data['event'][] = $remove['id'];
            }
            $removeEventIds = implode(',',$data['event']);
            $removeTeacherCourses =  $_CourseTeacher->getAll("event in ($removeEventIds)", 'id');
            if($removeTeacherCourses){
	            foreach($removeTeacherCourses as $removeTeacherCourse){
	            	$data['teacher_course'][] = $removeTeacherCourse['id'];
	            }
            }
            $removeStudentCourses =  $_CourseStudent->getAll("event in ($removeEventIds)", 'id');
            if($removeStudentCourses){
	            foreach($removeStudentCourses as $removeStudentCourse){
	            	$data['student_course'][] = $removeStudentCourse['id'];
	            }
            }
		}
		return $data;
	}
	
    /**
     * 获取一个子课程
     */
    public function get_child($event, $length)
    {
        if(empty($event['is_loop']) || $length < 1) return false;
        $rec = Repeat::resolve($event['start_date'], $event['end_date'], $event['rec_type'], $event['length']);				
        while(list($key, $val) = each($rec))
        {            
            if($val['length'] == $length)  return $val;            
        }
        return false;
    }
    
    /**
     * 取虚拟课程信息
     */
    public function virtual($parent, $length)
    {
        if(!$parent || $length < 0) return false;
        $child = $this->get_child($parent, $length);       
        if(!$child) return false;
        $child = array_merge($parent, $child, array(
            'id' => $parent['id'] . "#" . $length,
            'pid' => $parent['id'],
            'lock' => 0,
            'rec_type' => '',
            'is_loop' => 0
        ));
        return $child;
    }
    
    /**
     * 比较
     */
	public function compare($id, array $teachers,array $students, $current=false){
		if(!$teachers && !$students) return array();
		$data = array();		
		//获取课程的老师，学生
        $_CourseTeacher = new Course_TeacherModel();
        $oldTeachers = $_CourseTeacher->getAll(array('event' => $id),'teacher,priv');
        $_CourseStudent = new Course_StudentModel();
        //已结束的学员不取
        if($current){
			// 结束时间必须大于循环课程的时间
			$this->db->select('t_course_student.student,t_course_student.start_date,t_course_student.end_date')->from('t_course_student')
				->join('t_event','t_course_student.event = t_event.id')
				->where("t_event.status = 0 and t_event.id = $id and t_event.is_loop = 1 and (t_course_student.end_date='0000-00-00 00:00:00' or t_course_student.end_date >= t_event.start_date)");
			$oldStudents = $this->db->get()->result_array();
		}else{
			$oldStudents = $_CourseStudent->getAll(array('event' => $id),'student,start_date,end_date');
		}
		if(!$oldTeachers && !$oldStudents) return $data;
		$oldTeacherIds = array();
		foreach($oldTeachers as $oldTeacher){
			$oldTeacherIds[] = $oldTeacher['teacher'];
		}
		$oldStudentIds = array();
		foreach($oldStudents as $oldStudent){
			$oldStudentIds[] = $oldStudent['student'];
		}
		
		$data['teacher']['new'] = array_diff($teachers, $oldTeacherIds);
		$data['teacher']['lost'] = array_diff($oldTeacherIds, $teachers);
		$data['teacher']['keep'] = array_intersect($oldTeacherIds, $teachers);
		$data['student']['new'] = array_diff($students, $oldStudentIds);
		$data['student']['lost'] = array_diff($oldStudentIds, $students);
		$data['student']['keep'] = array_intersect($oldStudentIds, $students);
		return $data;
	}
	
    /**
     * 推送
     */
    public function push($eventInfo,$teachers,$students,$type=0,$data=array(), $whole=0){
		event_push($eventInfo,$teachers,$students,$type,$data, $whole);
    }
}