<?php  
function DB($params = ''){
	if(!$params) $params = Yaf_Registry::get('config')->db->active_group;
	if (is_string($params) AND strpos($params, '://') === FALSE)
	{
		global $_G; 
		if($_G['DB'][$params] && is_object($_G['DB'][$params])){
			return $_G['DB'][$params];
		}else{
			$dbObject = Yaf_Registry::get('config')->db->$params;
			if(!$dbObject){
				exit('db param error');
			}
			$_params = array(
				'hostname' => $dbObject->hostname,
				'username' => $dbObject->username,
				'password' => $dbObject->password,
				'database' => $dbObject->database,
				'pconnect' => $dbObject->pconnect,
				'db_debug' => $dbObject->db_debug,
				'char_set' => $dbObject->char_set,
				'dbcollat' => $dbObject->dbcollat,
			);
			$_params['dbdriver'] = 'Mysqli';
			$driver = 'DB_Mysqli_Driver';
			$DB = new $driver($_params);
			$_G['DB'][$params] = $DB;
			return $DB;
		}
	}else{
		exit('db param error');
	}
}

function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;
    }
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])) {
    //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array (
                                'nokia',
                                'sony',
                                'ericsson',
                                'mot',
                                'samsung',
                                'htc',
                                'sgh',
                                'lg',
                                'sharp',
                                'sie-',
                                'philips',
                                'panasonic',
                                'alcatel',
                                'lenovo',
                                'iphone',
                                'ipod',
                                'blackberry',
                                'meizu',
                                'android',
                                'netfront',
                                'symbian',
                                'ucweb',
                                'windowsce',
                                'palm',
                                'operamini',
                                'operamobi',
                                'openwave',
                                'nexusone',
                                'cldc',
                                'midp',
                                'wap',
                                'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
	
function writeLog($fileName,$data,$action='default') {

	$year	= date('Y');
	$month	= date('m');
	$day	= date('d');
	$path	= $year . '/' . $month . '/';

	$filename = $fileName.'-'.$year . '-' . $month . '-' . $day . '.log';
	
	if (!is_dir(LOGS_PATH.'/'. $year)) {
		mkdir(LOGS_PATH.'/'. $year);
	}

	$sub_dir = LOGS_PATH .'/'. $path;
	if (!is_dir($sub_dir)) {
		mkdir($sub_dir);
	}
	
	$uri		= $_SERVER['REQUEST_URI'];
	$addIp		= getIp();

	if(is_array($data))$data = implode("\t",$data);

	$data = date('Y-m-d H:i:s')."\t" . $action . "\t".$data."\t".$uri."\t".$addIp."\n";
	file_put_contents($sub_dir . $filename, $data, FILE_APPEND);
}

function upload($type='',$file,$student=0,$school=0){
	$api = Yaf_Registry::get('config')->path->api;
	if(!$api || !$_FILES) return false;
	$data = array(
		'app'=>'upload2',
		'act'=>'index',
		"file"  => $file,
		'type'=>$type,
		'student'=>$student,
		'school'=>$school
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $api);
	curl_setopt($curl, CURLOPT_POST, 1 );
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_USERAGENT,"Mozilla/4.0");
	$result = curl_exec($curl);
	$error = curl_error($curl);
	return $error ? $error : $result;
}
/**
 * 得到IP地址，去掉HTTP_X_FORWARDED_FOR的检测(可以伪造),<br>加入检查IP是否有效
 * 返回:正常ip正常返回，不正常的返回0.0.0.0
 */
function getIp() {
	if (getenv('HTTP_CLIENT_IP')) {
		$ip = getenv('HTTP_CLIENT_IP');
	}
	elseif (getenv('REMOTE_ADDR')) {
		$ip = getenv('REMOTE_ADDR');
	} else {
		$ip = $_SERVER['REMOTE_ADD'];
	}
	if (isValidIp($ip)) {
		return $ip;
	} else {
		return '0.0.0.0';
	}
}


/**
 * 检查IP是否有效
 * 返回:正常ip为true，不正常的返回fasle
 */
function isValidIp($ip) {
	$isvalid = true;
	if (!preg_match('/^[1-9][0-9]{0,2}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)) { //ip是否数字组成
		$isvalid = false;
	} else
		if (preg_match('/^10\./', $ip) || preg_match('/^172\.(16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31)\./', $ip)) { //ip是否为内网(防止WEB前端为squid时出现) ||  preg_match("/^192\.168\./",$ip)
			$isvalid = false;
		} else { //IP段是否为有效数字
			$ip_arr = explode('.', $ip);
			for ($i = 0; $i < count($ip_arr); $i++) {
				if (intval($ip_arr[$i]) > 255) {
					$isvalid = false;
					break;
				}
			}
			unset ($ip_arr);
		}
	return $isvalid;
}


function  checkMobile($mobilephone){
	if(preg_match("/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}|18[0-9]{1}[0-9]{8}$/",$mobilephone)){    
    //验证通过    
    	return true;    
	}else{    
	    //手机号码格式不对    
	    return false;        
	} 
}
/**
 * 返回经addslashes处理过的字符串或数组
 * @param $string 需要处理的字符串或数组
 * @return mixed
 */
function new_addslashes($string) {
	if(!is_array($string)) return addslashes($string);
	foreach($string as $key => $val) $string[$key] = new_addslashes($val);
	return $string;
}

/**
 * 返回经stripslashes处理过的字符串或数组
 * @param $string 需要处理的字符串或数组
 * @return mixed
 */
function new_stripslashes($string) {
	if(!is_array($string)) return stripslashes($string);
	foreach($string as $key => $val) $string[$key] = new_stripslashes($val);
	return $string;
}


/**
 * 获取UNIX/LINUX 下正在运行的进程数量
 */
function unixCountProcess($bin, $script){
	$countCmd = popen("ps -ef | grep \"$bin $script\" | grep -v grep | wc -l", "r");
	$countProc = fread($countCmd, 512);
    pclose($countCmd);
	return intval($countProc);
}

/**
 * 获取UNIX/LINUX 下正在运行的进程id
 */
function unixProcess($bin, $script){
	$idCmd = popen("ps -ef | grep \"$bin $script\" | grep -v grep | awk '{print $2,$3}'", "r");
    $idProc = fread($idCmd, 512);
    pclose($idCmd);
    $ids = array();
    if($idProc){
        $idArr = explode("\n",trim($idProc));
        foreach($idArr as $id){
                 $_idArr = explode(' ',trim($id));
                $ids[$_idArr[1]] = $_idArr[0];
        }
    }
    return $ids;
}

/**
 * 公式代入
 */
function formula($formula, $params) {
	if (!trim($formula))
		return null;
	extract($params);
	$str = "\$formulaResult = ($formula);";
	eval ($str);
	return $formulaResult;
}

function debug2($str) {
	echo '[' . date('Y-m-d H:i') . ':00] ' . $str . "\n";
}

if(!function_exists('array_column')){ 
    function array_column($input, $columnKey, $indexKey=null){ 
        $columnKeyIsNumber      = (is_numeric($columnKey)) ? true : false; 
        $indexKeyIsNull         = (is_null($indexKey)) ? true : false; 
        $indexKeyIsNumber       = (is_numeric($indexKey)) ? true : false; 
        $result                 = array(); 
        foreach((array)$input as $key=>$row){ 
            if($columnKeyIsNumber){ 
                $tmp            = array_slice($row, $columnKey, 1); 
                $tmp            = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null; 
            }else{ 
                $tmp            = isset($row[$columnKey]) ? $row[$columnKey] : null; 
            } 
            if(!$indexKeyIsNull){ 
                if($indexKeyIsNumber){ 
                    $key        = array_slice($row, $indexKey, 1); 
                    $key        = (is_array($key) && !empty($key)) ? current($key) : null; 
                    $key        = is_null($key) ? 0 : $key; 
                }else{ 
                    $key        = isset($row[$indexKey]) ? $row[$indexKey] : 0; 
                } 
            } 
            $result[$key]       = $tmp; 
        } 
        return $result; 
    } 
} 


function excelRead($file = array(),$sheet=0,$row=1,$column='A'){
	if(!$file['name'] || !in_array(pathinfo($file['name'], PATHINFO_EXTENSION),array('xls','xlsx'))) show_message("文件不存在或格式（xls,xlsx）不对");
	ini_set('memory_limit','-1');
	$filePath = $file['tmp_name'];
	/**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/ 
	$PHPReader = new PHPExcel_Reader_Excel2007(); 
	if(!$PHPReader->canRead($filePath)){ 
		$PHPReader = new PHPExcel_Reader_Excel5(); 
		if(!$PHPReader->canRead($filePath)){ 
			echo 'no Excel'; 
			return; 
		} 
	} 
	$PHPExcel = $PHPReader->load($filePath); 
	/**读取excel文件中的第一个工作表*/ 
	$currentSheet = $PHPExcel->getSheet($sheet); 
	/**取得最大的列号*/ 
	$allColumn = $currentSheet->getHighestColumn(); 
	/**取得一共有多少行*/ 
	$allRow = $currentSheet->getHighestRow(); 
	/**从第一行开始输出*/ 
	$data = array();
	for($currentRow = $row;$currentRow <= $allRow;$currentRow++){ 
		/**从第A列开始输出*/ 
		for($currentColumn=$column;$currentColumn<= $allColumn; $currentColumn++){ 
			$val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65,$currentRow)->getValue();/**ord()将字符转为十进制数*/ 
				/**如果输出汉字有乱码，则需将输出内容用iconv函数进行编码转换，如下将gb2312编码转为utf-8编码输出*/ 
				$data[$currentRow][] = $val;
		} 
		
	} 
	return $data;
}

function excelExport($fileName='',$headArr=array(),$data=array(),$ex='2007'){
	if(empty($data) || !is_array($data)){
        die("data must be a array");
    }
    if(empty($fileName)){
        exit;
    }
    $sheetName = $fileName;
    $date = date("Y_m_d",time());
    $fileName .= "_{$date}.xlsx";

    //创建新的PHPExcel对象
    $objPHPExcel = new PHPExcel();
    $objProps = $objPHPExcel->getProperties();
	
    //设置表头
    $key = ord("A");
    foreach($headArr as $v){
        $colum = chr($key);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum.'1', $v);
        $key += 1;
    }
    
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach($data as $key => $rows){ //行写入
        $span = ord("A");
        foreach($rows as $keyName=>$value){// 列写入
            $j = chr($span);
            $objActSheet->setCellValue($j.$column, $value);
            $span++;
        }
        $column++;
    }

    $fileName = iconv("utf-8", "gb2312", $fileName);
     //设置活动单指数到第一个表,所以Excel打开这是第一个表
    $objPHPExcel->setActiveSheetIndex(0);
    //重命名表
    $objPHPExcel->getActiveSheet()->setTitle($sheetName);
    if($ex == '2007') { //导出excel2007文档
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition: attachment; filename=\"$fileName\"");
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	} else {  //导出excel2003文档
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment; filename=\"$fileName\"");
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	}
	$objWriter->save('php://output');
  	exit;	
}

/**
 * 输出信息
 */
function show_message($message = '',$url_forward='',$dialog='',$reload=true){
	$view= new Smarty_Adapter(null, Yaf_Registry::get("config")->get("smarty"));
	$view->assign('path', Yaf_Registry::get("config")->path);
	$view->assign('url_forward', $url_forward);
	$view->assign('dialog', $dialog);
	$view->assign('reload', $reload);
	$view->assign('message', $message);
	$view->display('public/message.html');
	exit;
}


/**
 * 分页函数
 *
 * @param $num 信息总数
 * @param $curr_page 当前分页
 * @param $perpage 每页显示数
 * @param $urlrule URL规则
 * @param $array 需要传递的数组，用于增加额外的方法
 * @return 分页
 */
function pages($num, $curr_page, $perpage = 20, $urlrule = '', $array = array(),$setpages = 10) {
	$urlrule = url_par('page={$page}');
	$multipage = '';
	if($num > $perpage) {
		$page = $setpages+1;
		$offset = ceil($setpages/2-1);
		$pages = ceil($num / $perpage);
		$from = $curr_page - $offset;
		$to = $curr_page + $offset;
		$more = 0;
		if($page >= $pages) {
			$from = 2;
			$to = $pages-1;
		} else {
			if($from <= 1) {
				$to = $page-1;
				$from = 2;
			}  elseif($to >= $pages) {
				$from = $pages-($page-2);
				$to = $pages-1;
			}
			$more = 1;
		}
		$multipage .= '<a class="a1">'.$num.'条</a>';
		if($curr_page>0) {
			$multipage .= ' <a href="'.pageurl($urlrule, $curr_page-1, $array).'" class="a1">上一页</a>';
			if($curr_page==1) {
				$multipage .= ' <span>1</span>';
			} elseif($curr_page>6 && $more) {
				$multipage .= ' <a href="'.pageurl($urlrule, 1, $array).'">1</a>..';
			} else {
				$multipage .= ' <a href="'.pageurl($urlrule, 1, $array).'">1</a>';
			}
		}
		for($i = $from; $i <= $to; $i++) {
			if($i != $curr_page) {
				$multipage .= ' <a href="'.pageurl($urlrule, $i, $array).'">'.$i.'</a>';
			} else {
				$multipage .= ' <span>'.$i.'</span>';
			}
		}
		if($curr_page<$pages) {
			if($curr_page<$pages-5 && $more) {
				$multipage .= ' ..<a href="'.pageurl($urlrule, $pages, $array).'">'.$pages.'</a> <a href="'.pageurl($urlrule, $curr_page+1, $array).'" class="a1">下一页</a>';
			} else {
				$multipage .= ' <a href="'.pageurl($urlrule, $pages, $array).'">'.$pages.'</a> <a href="'.pageurl($urlrule, $curr_page+1, $array).'" class="a1">下一页</a>';
			}
		} elseif($curr_page==$pages) {
			$multipage .= ' <span>'.$pages.'</span> <a href="'.pageurl($urlrule, $curr_page, $array).'" class="a1">下一页</a>';
		} else {
			$multipage .= ' <a href="'.pageurl($urlrule, $pages, $array).'">'.$pages.'</a> <a href="'.pageurl($urlrule, $curr_page+1, $array).'" class="a1">下一页</a>';
		}
	}
	return $multipage;
}
/**
 * 返回分页路径
 *
 * @param $urlrule 分页规则
 * @param $page 当前页
 * @param $array 需要传递的数组，用于增加额外的方法
 * @return 完整的URL路径
 */
function pageurl($urlrule, $page, $array = array()) {
	if(strpos($urlrule, '~')) {
		$urlrules = explode('~', $urlrule);
		$urlrule = $page < 2 ? $urlrules[0] : $urlrules[1];
	}
	$findme = array('{$page}');
	$replaceme = array($page);
	if (is_array($array)) foreach ($array as $k=>$v) {
		$findme[] = '{$'.$k.'}';
		$replaceme[] = $v;
	}
	$url = str_replace($findme, $replaceme, $urlrule);
	$url = str_replace(array('http://','//','~'), array('~','/','http://'), $url);
	return $url;
}

/**
 * URL路径解析，pages 函数的辅助函数
 *
 * @param $par 传入需要解析的变量 默认为，page={$page}
 * @param $url URL地址
 * @return URL
 */
function url_par($par, $url = '') {
	if($url == '') $url = get_url();
	$pos = strpos($url, '?');
	if($pos === false) {
		$url .= '?'.$par;
	} else {
		$querystring = substr(strstr($url, '?'), 1);
		parse_str($querystring, $pars);
		$query_array = array();
		foreach($pars as $k=>$v) {
			if($k != 'page') $query_array[$k] = $v;
		}
		$querystring = http_build_query($query_array).'&'.$par;
		$url = substr($url, 0, $pos).'?'.$querystring;
	}
	return $url;
}

function url($module='Index',$controller='Index',$action='index',$query=''){
	//$url = DOMAIN ? '/'.ucfirst($controller).'/'.strtolower($action) : '/'.ucfirst($module).'/'.ucfirst($controller).'/'.strtolower($action);
	$url = '/'.ucfirst($module).'/'.ucfirst($controller).'/'.strtolower($action);
	if($query){
		$url .= '?'.$query;
	}
	return $url;
}

/**
 * 获取当前页面完整URL地址
 */
function get_url() {
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
	$path_info = isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.safe_replace($_SERVER['QUERY_STRING']) : $path_info);
	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}


/**
 * 安全过滤函数
 *
 * @param $string
 * @return string
 */
function safe_replace($string) {
	$string = str_replace('%20','',$string);
	$string = str_replace('%27','',$string);
	$string = str_replace('%2527','',$string);
	$string = str_replace('*','',$string);
	$string = str_replace('"','&quot;',$string);
	$string = str_replace("'",'',$string);
	$string = str_replace('"','',$string);
	$string = str_replace(';','',$string);
	$string = str_replace('<','&lt;',$string);
	$string = str_replace('>','&gt;',$string);
	$string = str_replace("{",'',$string);
	$string = str_replace('}','',$string);
	$string = str_replace('\\','',$string);
	return $string;
}


function datetime($format='', $time='')
{
	$format || $format = 'Y-m-d H:i:s';
	$time || $time = time();
	return date($format, $time);
}

// 是否为日期
function isDate($str,$format="Y-m-d"){ 
	$strArr = explode("-",$str); 
	if(empty($strArr)){
		return false;
	}
	foreach($strArr as $val){	
		if(strlen($val) < 2){
			$val = "0".$val;
		}
		$newArr[] = $val;
	}
	$str = implode("-",$newArr);  
	$unixTime = strtotime($str);  
	$checkDate = date($format,$unixTime);  
	if($checkDate == $str)  
		return $checkDate;  
	else  
		return false;
} 


/**
 * 获取api配置
 */
function apiConfig($name='',$key=''){
	if(!$name) return false;
	$configFile = API_PATH.'/global/conf.php';
	if(file_exists($configFile)){
		require $configFile;
		if(!$config[$name]) return false;
		return $key ? $config[$name][$key]:$config[$name];
	}
	return false;
}

/**
 * 短信
 */
function SMS(){
	$config = apiConfig('sms');
	if(!$config) return false;
	return new Sms($config);		
}
/**
 * 0 附件  1 用户头像 2学生头像 3 机构logo
 */
function imageUrl($url='',$type=0,$size=0,$returnimg=true){
	if(!$url) return false;
	$static = array(30 => 'tiny', 50=>'small', 100=> 'middle', '200'=>'big');
	$_size=$size;
	isset($static[$size]) || $_size=200;
	$pathArr = Yaf_Registry::get('config')->path->toArray();
	$MD = md5($url);
	$path = substr($MD,0,2) . "/" . substr($MD,2,2) . "/" .substr($MD,4,2). "/original";
	$path .= $size >0 ? "_200_200" : '';
	$img = '';
	if($type == 0){
		$img =  $url ? $pathArr['image'].$url : "";
	}elseif($type == 1){
		$img = $pathArr['avatar'].'avatar/'.$path.'.jpg';
		@getimagesize($img) || $img = $pathArr['images'] . "hulapai/noavatar/" . $static[$_size]. ".jpg";
		$img = $img."?t=".time();
	}elseif($type == 2){
		$img = $pathArr['avatar'].'student_avatar/'.$path.'.jpg';
		@getimagesize($img) || $img = $pathArr['images'] . "hulapai/noavatar/" . $static[$_size]. ".jpg";
		$img = $img."?t=".time();
	}elseif($type == 3){
		$img = $pathArr['avatar'].'school/'.$path.'.jpg';
		@getimagesize($img) || $img = $pathArr['images'] . "hulapai/noavatar/" . $static[$_size]. ".jpg";
		$img = $img."?t=".time();
	}
	if($returnimg) return $img ? "<img src='$img' width='$size' height='$size'/>" : "";
	else return $img;
}

function schoolName($school,$key="name"){
	$_School = new SchoolModel();
	$schoolInfo = $_School->getRow(array('id'=>$school),$key);
	is_array($key) && $key = $key[0];
	return $schoolInfo[$key];
}

function userName($uid,$key="nickname"){
	$_User = new UserModel();
	$userInfo = $_User->getRow(array('id'=>$uid),$key);
	is_array($key) && $key = $key[0];
	return $userInfo[$key];
}

function teacherName($teacher,$key=array("CONCAT(firstname,lastname)")){
	$_User = new UserModel();
	$teacherInfo = $_User->getRow(array('id'=>$teacher),$key);
	is_array($key) && $key = $key[0];
	return $teacherInfo[$key];
}

function studentName($student,$key="name"){
	$_Student = new StudentModel();
	$studentInfo = $_Student->getRow(array('id'=>$student),$key);
	is_array($key) && $key = $key[0];
	return $studentInfo[$key];
}

function relation($uid,$student){
	$_UserStudent = new User_StudentModel();
	$relationInfo = $_UserStudent->getRow(array('user'=>$uid,'student'=>$student),'relation');
	if($relationInfo['relation'] == 1) return "(本人)";
	elseif($relationInfo['relation'] == 2) return "(爸爸)";
	elseif($relationInfo['relation'] == 3) return "(妈妈)";
	elseif($relationInfo['relation'] == 4) return "(家长)";
	else return "";
}

function applyContent($type=0,$from=0,$student=0){
	$message = "";
	if(in_array($type,array(1,2,3,4,5,6,7,8)) && $from){
		switch($type){
			case 1:
				$from = userName($from);
				$message = "家长".$from."找您作为他/她的老师";
				break;
			case 2:
				$from = teacherName($from);
				$message = "老师".$from."找到您";
				break;
			case 3:
				$from = schoolName($from);
				$message = "机构".$from."邀请您成为该机构的老师";
				break;
			case 4:
				$from = teacherName($from);
				$message = "老师".$from."请求加入机构";
				break;
			case 5:
				$from = userName($from);
				$message = $from."申请成为您的好友";
				break;
			case 6:
				if($student){
					$student = studentName($student);
					$message = "学生".$student."申请加入机构";
				}
				break;
			case 7:
				$from = schoolName($from);
				$message = "机构".$from."邀请您成为该机构的学生";
				break;
			case 8:
				if($student){
					$from = userName($from);
					$student = studentName($student);
					$message = $from."授权".$student."给您";
				}
				break;
		}
	}
	return $message;
}

/**
 * 课程推送
 */
function event_push($eventInfo,$teachers,$students,$type=0,$data=array(), $whole=0){
	$hash = md5($eventInfo['id']).rand(10000,99999);
	$logsData = array(
		'hash'=>$hash,
		'app'=>'event',
		'act'=>'add',
		'character'=>'teacher',
		'creator'=>$eventInfo['creator'],
		'target'=>array(),
		'ext'=>array(),
		'source'=>array(
			'event' => $eventInfo['id'],
			'is_loop' => $eventInfo['is_loop'],
			'whole' => $whole,
			'school'=> $eventInfo['school'],
		),
		'data' => array(),
		'type'=>$type,
	);
	if($data['source']){
		$logsData['source'] = array_merge($logsData['source'],$data['source']);
		unset($data['source']);
	}
	$logsData = array_merge($logsData,$data);
	$_Logs = new LogsModel();
	if($teachers){
		$_Logs->addLog(array_merge($logsData,array('character'=>'teacher','target'=>$teachers)));
	}
	if($students){
		$_Logs->addLog(array_merge($logsData,array('character'=>'student','target'=>$students)));
	}
}

function out($state=0, $message='', $result=array())
{
	die(json_encode(array(
		'state' => $state,
		'message' => $message,
		'result' => $result
	)));
}