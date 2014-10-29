<?php
/**
 * 导入
 */
class ImportController extends Yaf_Controller_Base_Abstract {
	
	private function getAuthCode(){
		if(!$this->user['authCode']){
			$dispatcher = Yaf_Dispatcher::getInstance();
			$dispatcher->autoRender(false);
			$this->getView()->display('import/auth.html');
		}else{
			$_Authcode = new AuthcodeModel();
			$info = $_Authcode->getRow(array('date'=>date('Y-m')));
			if(!$info) show_message('授权码未设置，请联系客服!');
			if($this->user['authCode'] != $info['authCode']){
				$dispatcher = Yaf_Dispatcher::getInstance();
				$dispatcher->autoRender(false);
				$this->getView()->display('import/auth.html');
			}
		}
	}
	
	public function teacherAction() {
		$this->getAuthCode();
		$password = $this->post('password','','trim');
		$import = $this->post('import',0,'intval');
		if($password && $_FILES['upfile']){
			$data = excelRead($_FILES['upfile'],0,2);
			if(!$data) show_message("文件内容为空");
			$_SchoolTeacher = new School_TeacherModel();	
			$numArr = array(
				'1'=>0,//建立账号
				'2'=>0,//建立老师档案
				'4'=>0,//加入机构
			);
			$teacherData = $this->checkImportData($data,$password,'teacher');
			foreach($teacherData as $teacher){
				$result = $_SchoolTeacher->import($teacher['account'],$teacher['password'],$teacher['firstname'],$teacher['firstname_en'],$teacher['lastname'],$teacher['lastname_en'],$teacher['address'],$this->school,$this->uid);
				if($result !== false){
					if($result & 4){
						$numArr[4] +=1;
					}
					if($result & 2){
						$numArr[2] +=1;
					}
					if($result & 1){
						$numArr[1] +=1;
					}
					
				}
			}
			show_message("创建账号".$numArr[1]."个，创建老师档案".$numArr[2]."个，加入机构".$numArr[4]."个");
		}
	}
	
	public function studentAction() {
		$this->getAuthCode();
		$password = $this->post('password','','trim');
		if($password && $_FILES['upfile']){
			$data = excelRead($_FILES['upfile'],0,2);
			if(!$data) show_message("文件内容为空");	
			$_SchoolStudent = new School_StudentModel();	
			$numArr = array(
				'1'=>0,//建立账号
				'2'=>0,//建立学生档案
				'4'=>0,//加入机构
			);
			$studentData = $this->checkImportData($data,$password,'student');
			foreach($studentData as $student){
				$result = $_SchoolStudent->import($student['account'],$student['password'],$student['name'],$student['name_en'],$this->school,$this->uid);
				if($result !== false){
					if($result & 4){
						$numArr[4] +=1;
					}
					if($result & 2){
						$numArr[2] +=1;
					}
					if($result & 1){
						$numArr[1] +=1;
					}
				}
			}
			show_message("创建账号".$numArr[1]."个，创建学生档案".$numArr[2]."个，加入机构".$numArr[4]."个");
		}
	}
	
	private function checkImportData($data = array(),$password='',$type="student"){
		if(!$data || !$password || !in_array($type,array("student","teacher"))) show_message("没有可导入的数据");
		$returnData = array();
		if($type=="teacher"){
			foreach($data as $key=>$teacher){
				$account = trim(preg_replace('/\s+/',"",$teacher[0]));
				$name = trim(preg_replace('/\s+/'," ",$teacher[1]));
				$address = trim(preg_replace('/\s+/',"",$teacher[2]));
				if(!$name || !$account || !checkMobile($account)) show_message("行：".$key."格式有错！");
				$name = explode(' ',$name);
				$firstname = $name[0]; 
				$lastname = $name[1]?$name[1]:'';
				$returnData[] = array(
					'account'=>$account,
					'password'=>$password,
					'firstname'=>$firstname,
					'firstname_en'=>Ustring::topinyin($firstname),
					'lastname'=>$lastname,
					'lastname_en'=>Ustring::topinyin($lastname),
					'address'=>$address,
				);
			}
			if(!$returnData) show_message("没有可导入的老师");
		}elseif($type=="student"){
			foreach($data as $key=>$student){
				$account = trim(preg_replace('/\s+/',"",$student[0]));
				$name = trim(preg_replace('/\s+/'," ",$student[1]));
				if(!$name || !$account  || !checkMobile($account)) show_message("行：".$key."格式有错！");
				$returnData[] = array(
					'account'=>$account,
					'password'=>$password,
					'name'=>$name,
					'name_en'=>Ustring::topinyin($name),
				);
			}
			if(!$returnData) show_message("没有可导入的学生");
		}
		return $returnData;
	}
}
