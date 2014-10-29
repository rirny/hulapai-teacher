<?php
class Html{
	public static function getArea($province=0,$city=0,$area=0){
		$_Area = new AreaModel();
		$province = $_Area->getRow(array('id'=>$province),'title');
		$city = $_Area->getRow(array('id'=>$city),'title');
		$area = $_Area->getRow(array('id'=>$area),'title');
		return $province['title'].'-'.$city['title'].'-'.$area['title'];
	}
	public static function selectArea($province='',$city='',$area='',$province_name = "info[province]",$city_name = "info[city]",$area_name = "info[area]",$default_option='请选择'){
		$_Area = new AreaModel();
		$provinces = $_Area->getAreaByPid(0);
		$province_str = self::select($provinces,$province,$province_name,'province',$default_option);
		if($province > 0){
			$citys = $_Area->getAreaByPid($province);
			$city_str = self::select($citys,$city,$city_name,'city',$default_option);
		}else{
			$city_str = '<select name="'.$city_name.'" id="city">';
			if($default_option) $city_str .= "<option value='' $default_selected>$default_option</option>";
			$city_str .= '</select>';
		}
		if($city > 0){
			$citys = $_Area->getAreaByPid($city);
			$area_str = self::select($citys,$area,$area_name,'area',$default_option);
		}else{
			$area_str = '<select name="'.$area_name.'" id="area">';
			if($default_option) $area_str .= "<option value='' $default_selected>$default_option</option>";
			$area_str .= '</select>';
		}
		$str = "$province_str$city_str$area_str";
		$str .= '<script type="text/javascript">
			$("#province").change(function(){
		  		var province = $(this).val();
		  		$.get("/public/area?pid="+province, function(result){
			    	$("#city").html(result);
			    	$("#area").html("<option value=0>请选择</option>");
			  	});

		  	});
		  	$("#city").change(function(){
		  		var city = $(this).val();
				$.get("/public/area?pid="+city, function(result){
			    	$("#area").html(result);
			  	});
		  	});
        </script>';
		return $str;
	}
	
	public static function selectCourseType($courseType='',$courseType2='',$name = "type",$title = "title",$title_value="",$default_option='请选择'){
		$_CourseType = new Course_TypeModel();
		$courseTypes = $_CourseType->getCourseTypeByPid(0);
		$courseType_str = self::select($courseTypes,$courseType,'courseType','courseType',$default_option);
		$courseType2_str = '';
		if($courseType > 0){
			$courseType2s = $_CourseType->getCourseTypeByPid($courseType);
			$courseType2_str = $courseType2s ? self::select($courseType2s,$courseType2,'courseType2','courseType2',$default_option):'';
		}
		$value = $courseType2 ? $courseType2 : $courseType;
		$str = "<input type='hidden' id='$name' name='$name' value='$value'/>&nbsp;$courseType_str&nbsp;$courseType2_str&nbsp;<input style='display:none;' type='text' name='$title'  class='input-text ufocus' id='$title' value='$title_value' def='请直接输入，6个字以内'>";
		$str .= '<script type="text/javascript">
	  		if($("#courseType2").length > 0){
		  		var id = $("#courseType2").val();
		  		if(id){
				  	var text = $("#courseType2").children("option[value="+id+"]").text();
				  	if(text == "其他"){
			  			$("#'.$title.'").val("'.$title_value.'").show();
			  		}else{
			  			$("#'.$title.'").val(text).hide();
			  		}
		  		}
	  		}else{
	  			var pid = $("#courseType").val();
	  			if(pid){
				  	var text = $("#courseType").children("option[value="+pid+"]").text();
				  	if(text == "其他"){
			  			$("#'.$title.'").val("'.$title_value.'").show();
			  		}else{
			  			$("#'.$title.'").val(text).hide();
			  		}
	  			}
	  		}
			$("#courseType").change(function(){
		  		var pid = $(this).val();
		  		var text = $("#courseType").children("option[value="+pid+"]").text();
		  		if(text == "其他"){
		  			$("#'.$title.'").val("'.$title_value.'").show();
		  		}else{
		  			$("#'.$title.'").val(text).hide();
		  		}
		  		$("#'.$name.'").val(pid);
		  		$.get("/public/courseType?pid="+pid, function(result){
			    	if(result){
			    		if($("#courseType2").length == 0){
			    			$("#courseType").after($("<select id=\'courseType2\' name=\'courseType2\'>"+result+"</select>"));
			    			$("#courseType2").bind("change",function(){
			    				var id = $(this).val();
						  		var text = $("#courseType2").children("option[value="+id+"]").text();
						  		$("#'.$name.'").val(id);
						  		if(text == "其他"){
						  			$("#'.$title.'").val("'.$title_value.'").show();
						  		}else{
						  			$("#'.$title.'").val(text).hide();
						  		}
			    			});
			    		}else{
			    			$("#courseType2").html(result);
			    		}
			    		$("#'.$title.'").hide();
			    		$("#'.$name.'").val(0);
			    		
			    	}else{
			    		if($("#courseType2")){
			    			$("#courseType2").remove();
			    		}
			    	}   	
			  	});
		  	});
		  	$("#courseType2").bind("change",function(){
				var id = $(this).val();
		  		var text = $("#courseType2").children("option[value="+id+"]").text();
		  		$("#'.$name.'").val(id);
		  		if(text == "其他"){
		  			$("#'.$title.'").val("'.$title_value.'").show();
		  		}else{
		  			$("#'.$title.'").val(text).hide();
		  		}
			});
        </script>';
		return $str;
	}
	
	public static function selectSchoolType($id='',$name="info[type]",$classid='type',$default_option=''){
		$schoolType = array(
			1=>'私人机构',
			2=>'品牌直营',
			3=>'品牌加盟',
		);
		return self::select($schoolType,$id,$name,$classid,$default_option);
	}
	
	public static function selectStudentResource($id='',$name="studentResource",$classid='studentResource',$default_option='请选择',$default_option_key='-1'){
		$studentResources = array(
			0=>'招生',
			1=>'活动',
			2=>'其他',
		);
		return self::select($studentResources,$id,$name,$classid,$default_option,$default_option_key);
	}
	
	public static function selectAgent($id='',$name="agent",$classid='agent',$default_option='请选择',$default_option_key='-1'){
		$agents = array(
			0=>'网站',
			1=>'手机网页版',
			2=>'android',
			3=>'ios'
		);
		return self::select($agents,$id,$name,$classid,$default_option,$default_option_key);
	}
	
	public static function selectStatus($id='',$name="status",$classid='status',$default_option='请选择',$default_option_key='-1'){
		$genders = array(
			0=>'正常',
			//1=>'删除',
			2=>'冻结'
		);
		return self::select($genders,$id,$name,$classid,$default_option,$default_option_key);
	}
	
	public static function selectAttendances($id='',$name="attendances",$classid='attendances',$default_option='请选择'){
		$attendances = array(
			'-1'=>'未考勤','1'=>'出勤','2'=>'缺勤',3=>'请假'
		);
		return self::select($attendances,$id,$name,$classid,$default_option);
	}
	
	public static function selectAttended($id='',$name="attended",$classid='attended',$default_option='请选择',$default_option_key='-1'){
		$attendeds = array(
			'0'=>'未考勤','1'=>'已考勤'
		);
		return self::select($attendeds,$id,$name,$classid,$default_option,$default_option_key);
	}
	
	public static function selectCommented($id='',$name="commented",$classid='commented',$default_option='请选择',$default_option_key='-1'){
		$commenteds = array(
			'0'=>'未点评','1'=>'已点评'
		);
		return self::select($commenteds,$id,$name,$classid,$default_option,$default_option_key);
	}
	
	public static function selectTarget($id='',$name="target",$classid='target',$default_option='成人'){
		$targets = array(
			1=>'儿童',
			2=>'不限'
		);
		return self::select($targets,$id,$name,$classid,$default_option);
	}
	
	
	public static function selectGender($id='',$name="gender",$classid='gender',$default_option=''){
		$genders = array(
			1=>'男',
			2=>'女'
		);
		return self::select($genders,$id,$name,$classid,$default_option);
	}
	
	public static function selectRelation($id='',$name="relation",$classid='relation',$default_option='请选择'){
		$relations = array(
			1=>'本人',
			2=>'爸爸',
			3=>'妈妈',
			4=>'家长'
		);
		return self::select($relations,$id,$name,$classid,$default_option);
	}
	
	public static function selectClassTime($id='',$name="class_time",$classid='class_time',$default_option='请选择'){
		$classTimes = array(
			1=>'1',
			2=>'2',
			3=>'3',
		);
		return self::select($classTimes,$id,$name,$classid,$default_option);
	}
	
	
	public static function selectEventRecType($id='',$name="rec_type",$classid='rec_type',$num=1,$week='',$default_option='一次性课程'){
		$recType = array(
			'day_1'=>'每天',
			'week_1'=>'每周',
			'week_2'=>'每两周',
			//'month_1'=>'每月',
		);
		$str = self::select($recType,$id,$name,$classid,$default_option).'&nbsp;';
		$str .= '<div id="week" style="height: 40px;position: relative;line-height: 40px;">'.self::checkboxWeek($week, 'week[]').'</div>';
		//$str .= self::selectWeek('', 'week','week','星期').'&nbsp;';
		$str .= '<input type="text" name="num"  class="input-text" id="num" style="width:30px;" value="'.$num.'">';
		$str .= '<script type="text/javascript">
			var rec_type = "'.$id.'";
			if(!rec_type || rec_type == "day_1"){
				$("#week").hide();
				if(rec_type) $("#num").attr("disabled",false);
		  		else $("#num").attr("disabled",true);
			}
			$("#'.$classid.'").change(function(){
		  		var rec_type = $(this).val();
		  		if(!rec_type || rec_type == "day_1"){
		  			$("#week").hide();
		  			if(rec_type) $("#num").attr("disabled",false);
		  			else $("#num").attr("disabled",true);
		  		}else{
		  			$("#week").show();
		  			$("#num").attr("disabled",false);
		  		}
		  	});
        </script>';
		return $str;
	}
	
	public static function selectHour($id='',$name="hour",$classid='hour',$default_option='请选择'){
		$i = 7;
		$hourArr = array();
		while($i<24){
			$j = $i < 10 ? "0".$i :$i;
			$hourArr[$j] = $j;
			$i++;
		}
		return self::select($hourArr,$id,$name,$classid,$default_option);
	}
	
	public static function selectMinute($id='',$name="minute",$classid='minute',$default_option=''){
		$i = 0;
		$minuteArr = array();
		while($i<60){
			$j = $i < 10 ? "0".$i :$i;
			$minuteArr[$j] = $j;
			$i = $i + 5;
		}
		return self::select($minuteArr,$id,$name,$classid,$default_option);
	}
	
	/**
	 * 下拉选择框
	 */
	public static function select($array = array(), $id = '', $name = '', $classid = '' ,$default_option = '请选择',$default_option_key='') {
		$string = '<select name="'.$name.'" id="'.$classid.'" class="'.$classid.'">';
		$default_selected = ((empty($id)) && $default_option) ? 'selected' : '';
		if($default_option) $string .= "<option value='$default_option_key' $default_selected>$default_option</option>";
		if(!is_array($array) || count($array)== 0) return false;
		$ids = array();
		if(isset($id) && $id !== '') $ids = explode(',', $id);
		foreach($array as $key=>$value) {
			$selected = !empty($ids) && in_array($key, $ids) ? 'selected' : '';
			$string .= '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
		}
		$string .= '</select>';
		return $string;
	}
	
	
	public static function selectWeek($id = '', $name = 'week',$classid='week', $defaultvalue = '请选择'){
		$week = array(
			'1'=>'周一',
			'2'=>'周二',
			'3'=>'周三',
			'4'=>'周四',
			'5'=>'周五',
			'6'=>'周六',
			'0'=>'周日',
		);
		return self::select($week,$id,$name,$classid,$defaultvalue);
	}
	
	public static function checkboxWeek($id = '', $name = 'week', $defaultvalue = '', $width = 0, $field = 'week'){
		$week = array(
			'1'=>'周一',
			'2'=>'周二',
			'3'=>'周三',
			'4'=>'周四',
			'5'=>'周五',
			'6'=>'周六',
			'0'=>'周日',
		);
		return self::checkbox($week,$id,$name,$defaultvalue,$width,$field);
	}
		
	/**
	 * 复选框
	 * 
	 * @param $array 选项 二维数组
	 * @param $id 默认选中值，多个用 '逗号'分割
	 * @param $str 属性
	 * @param $defaultvalue 是否增加默认值 默认值为 -99
	 * @param $width 宽度
	 */
	public static function checkbox($array = array(), $id = '', $name = '', $defaultvalue = '', $width = 0, $field = '') {
		$string = '';
		$id = trim($id);
		if($id != '') $id = strpos($id, ',') ? explode(',', $id) : array($id);
		if($defaultvalue) $string .= '<input type="hidden" name="'.$name.'" value="-99">';
		$i = 1;
		foreach($array as $key=>$value) {
			$key = trim($key);
			$checked = ($id && in_array($key, $id)) ? 'checked' : '';
			if($width) $string .= '<label class="ib" style="width:'.$width.'px">';
			$string .= '<input type="checkbox" name="'.$name.'" id="'.$field.'_'.$i.'" '.$checked.' value="'.htmlspecialchars($key).'"> '.htmlspecialchars($value).'&nbsp;&nbsp;';
			if($width) $string .= '</label>';
			$string .= '&nbsp;';
			$i++;
		}
		return $string;
	}
	
	/**
	 * 单选框
	 * 
	 * @param $array 选项 二维数组
	 * @param $id 默认选中值
	 * @param $str 属性
	 */
	public static function radio($array = array(), $id = 0, $name = '', $width = 0, $field = '') {
		$string = '';
		foreach($array as $key=>$value) {
			$checked = trim($id)==trim($key) ? 'checked' : '';
			if($width) $string .= '<label class="ib" style="width:'.$width.'px">';
			$string .= '<input type="radio" '.$name.' id="'.$field.'_'.htmlspecialchars($key).'" '.$checked.' value="'.$key.'"> '.$value;
			if($width) $string .= '</label>';
		}
		return $string;
	}
	
	/**
	 * 日期时间控件
	 * 
	 * @param $name 控件name，id
	 * @param $value 选中值
	 * @param $isdatetime 是否显示时间
	 * @param $loadjs 是否重复加载js，防止页面程序加载不规则导致的控件无法显示
	 * @param $showweek 是否显示周，使用，true | false
	 * @param isreadonly 是否可手动修改，true | false
	 */
	public static function date($name, $value = '', $default_value='',$isdatetime = 0, $loadjs = 0, $showweek = 'true', $timesystem = 1,$isreadonly=false,$min=0) {
		if($value == '0000-00-00 00:00:00') $value = '';
		if(!$value && $default_value) $value = $default_value;
		$id = preg_match("/\[(.*)\]/", $name, $m) ? $m[1] : $name;
		if($isdatetime) {
			$size = 21;
			$format = '%Y-%m-%d %H:%M:%S';
			if($timesystem){
				$showsTime = 'true';
			} else {
				$showsTime = '12';
			}
			
		} else {
			$size = 10;
			$format = '%Y-%m-%d';
			$showsTime = 'false';
		}
		$str = '';
		if($loadjs || !defined('CALENDAR_INIT')) {
			define('CALENDAR_INIT', 1);
			$js_path = Yaf_Registry::get('config')->path->js;
			$str .= '<link rel="stylesheet" type="text/css" href="'.$js_path.'calendar/jscal2.css"/>
			<link rel="stylesheet" type="text/css" href="'.$js_path.'calendar/border-radius.css"/>
			<link rel="stylesheet" type="text/css" href="'.$js_path.'calendar/win2k.css"/>
			<script type="text/javascript" src="'.$js_path.'calendar/calendar.js"></script>
			<script type="text/javascript" src="'.$js_path.'calendar/lang/en.js"></script>';
		}
		$readonly = $isreadonly ? "readonly":"";
		$str .= '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" size="'.$size.'" class="date" '.$readonly.'>&nbsp;';
		//$images_path = Yaf_Registry::get('config')->path->images;
		//$str .= '<button id="f_btn_'.$id.'" class="date" style="filter:none;background: url('.$images_path.'admin_img/input_date.png) no-repeat;border: none;height: 18px;padding: 5px 0;*padding: 0;width: 18px;"></button>';
		$str .= '<input type="hidden" name="bak_'.$name.'" id="'.$id.'_bak" value="">&nbsp;';
		$min = !$min ? "19500101":"20130101";
		$max = date('Ymd',strtotime(date('Ymd'))+3600*24*365);
		$str .= '<script type="text/javascript">
			var cal =Calendar.setup({
			weekNumbers: '.$showweek.',
		    dateFormat: "'.$format.'",
		    inputField : "'.$id.'",
		    trigger    : "'.$id.'",
		    showTime: '.$showsTime.',
		    minuteStep: 1,
		    min: '.$min.',
            max: '.$max.', 
		    onSelect   : function() {
	            var date = this.selection.get();
	            date = Calendar.intToDate(date);
	            date = Calendar.printDate(date, "'.$format.'");
	            $("#'.$id.'").focus();
	            $("#bak_'.$id.'").val(date);
		        this.hide();
		    }
			});
			
			//cal.manageFields("f_btn_'.$id.'", "'.$id.'", "%Y-%m-%d");  
        </script>';
		return $str;
	}
	
	
	public static function eventColor($color='',$name='color'){
		$colorArr = Yaf_Registry::get('config')->color->toArray();
		if(!$color) $color = $colorArr[0];
		$str = "<input type='hidden' name='$name' id='$name' value='$color'/>";
		$str .= '<style type="text/css">
			.event_color{color: #FFFFFF;float:left;margin:0 2px;border: 1px solid black;font-size: 10px;font-weight: bold;height: 18px;line-height: 18px;text-align: center;width: 18px;}
		 </style>';
		foreach($colorArr as $key=>$_color){
		 	$v = $color == $key ? "√" : "";
		 	$str .= "<li class='event_color' bk='$key' style='background:$_color'>".$v."</li>";
		}
		$str .= '<script type="text/javascript">
			$(".event_color").click(function(){
		  		$(".event_color").html("");
		  		var background = $(this).attr("bk");
		  		$("#'.$name.'").val(background);
		  		$(this).html("√");
		  	});
        </script>';
		return $str;
	}
	
	public static function selectTeacher($school=0,$event=0,$priv=0,$teachers=array()){
		$str = '<div id="selectTeacherArea">';
		if($teachers){
			foreach($teachers as $teacher){
				$str .= '<div class="select_teacher_op" id="teacher_op_'.$teacher['teacher'].'" onclick="$(this).remove()">';
				$str .= $teacher['userInfo']['firstname'].$teacher['userInfo']['lastname'];
				$str .= '<input type="hidden" name="teacher_op['.$teacher['teacher'].']" value="'.$teacher['priv'].'"/>';
				$str .= '</div>';
			}
		}else{
			$str .= '<div class="select_teacher_op_none">';
			$str .= '无老师';
			$str .= '</div>';
		}
		$str .= '</div><input onclick="teacher()" name="selectTeacher" num="0" type="button" id="selectTeacher" value="选择老师" class="button" style="width:100px;height:35px;">';
		if($priv){
			$str .= '<script type="text/javascript">
				function teacher(){
					var numDom = document.getElementById("selectTeacher").attributes["num"];
					num = parseInt(numDom.nodeValue);
					numDom.nodeValue = num+1;
					window.top.art.dialog({
						id:"teacher",
						iframe:"/public/selectTeacher?school='.$school.'&event='.$event.'&priv='.$priv.'&num="+num, 
						title:"选择老师", 
						width:window.top.document.body.offsetWidth-100, 
						height:window.top.document.body.offsetHeight-150,
						lock:true
					}, function(){
						var d = window.top.art.dialog({
							id:"teacher"
						}).data.iframe;
						var form = d.document.getElementById("dosubmit");
						form.click();
						return false;
					}, function(){
						window.top.art.dialog({id:"teacher"}).close();
					});
				}
	        </script>';
		}else{
			$str .= '<script type="text/javascript">
				function teacher(){
					var numDom = document.getElementById("selectTeacher").attributes["num"];
					num = parseInt(numDom.nodeValue);
					numDom.nodeValue = num+1;
					window.top.art.dialog({
						id:"teacher",
						iframe:"/public/selectTeacher?school='.$school.'&event='.$event.'&priv='.$priv.'&num="+num, 
						title:"选择老师", 
						width:window.top.document.body.offsetWidth-100, 
						height:window.top.document.body.offsetHeight-150,
						lock:true
					}, function(){
						var d = window.top.art.dialog({
							id:"teacher"
						}).data.iframe;
						var form = d.document.getElementById("dosubmit");
						form.click();
						return false;
					}, function(){
						window.top.art.dialog({id:"teacher"}).close();
					});
				}
	        </script>';
		}
		return $str;
	}
	
	public static function selectStudent($school=0,$event=0,$students=array()){
		$str = '<div id="selectStudentArea">';
		if($students){
			foreach($students as $student){
				$str .= '<div class="select_student_op" id="student_op_'.$student['student'].'" onclick="$(this).remove()">';
				$str .= $student['userInfo']['name'];
				$str .= '<input type="hidden" name="student_op[]" value="'.$student['student'].'"/>';
				$str .= '</div>';
			}
		}else{
			$str .= '<div class="select_student_op_none">';
			$str .= '无学生';
			$str .= '</div>';
		}
		$str .= '</div><input onclick="student()" name="selectStudent" num="0" type="button" id="selectStudent" value="选择学生" class="button" style="width:100px;height:35px;">';
		$str .= '<script type="text/javascript">
			function student(){
				var numDom = document.getElementById("selectStudent").attributes["num"];
				num = parseInt(numDom.nodeValue);
				numDom.nodeValue = num+1;
				window.top.art.dialog({
					id:"student",
					iframe:"/public/selectStudent?school='.$school.'&event='.$event.'&num="+num, 
					title:"选择学生", 
					width:window.top.document.body.offsetWidth-100, 
					height:window.top.document.body.offsetHeight-150,
					lock:true
				}, function(){
					var d = window.top.art.dialog({
						id:"student"
					}).data.iframe;
					var form = d.document.getElementById("dosubmit");
					form.click();
					return false;
				}, function(){
					window.top.art.dialog({id:"student"}).close();
				});
			}
        </script>';
		return $str;
	}
}