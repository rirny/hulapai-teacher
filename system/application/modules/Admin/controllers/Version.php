<?php
/**
 * 版本
 */
class VersionController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction(){
		$page = $this->get('page',1,'intval');
		$source = $this->get('source', 0,'intval');
		if($source && !in_array($source,array(1,2))) show_message('参数错误！');
		$type = $this->get('type', 0,'intval');
		if($type && !in_array($type,array(1,2))) show_message('参数错误！');
		$level = $this->get('level', 0,'intval');
		$where = array();
		if($level == 1){
			$where['level'] = 0;
		}elseif($level == 2){
			$where['level'] = 1;
		}
		if($source){
			$where['source'] = $source;
		}
		if($type){
			$where['type'] = $type;
		}

		$_Version = new VersionModel();
		$versions = $_Version->getList($page,20,$where,'*','','version desc,id desc');
		$this->getView()->assign('versions', $versions['data']);
		$this->getView()->assign('pages', $versions['pages']);
		$this->getView()->assign('sources', array('1'=>'android','2'=>'ios'));
		$this->getView()->assign('types', array('1'=>'安装包','2'=>'补丁'));
		$this->getView()->assign('levels', array('1'=>'可选','2'=>'强制'));
	}
	
	public function addAction(){
		if($this->_POST){
			$version = $this->post('version','','trim');
			$type = $this->post('type',0,'intval');
			$source = $this->post('source',0,'intval');
			$level = $this->post('level',0,'intval');
			$size = $this->post('size','','trim');
			$date = $this->post('date',date('Y-m-d'),'isDate');
			$desc = $this->post('desc','','trim');
			$url = $this->post('url','','trim');
			$fileArr = $_FILES['upfile'];
			$pathto = Yaf_Registry::get('config')->path->packagepath;
			if(!$version || !in_array($type,array(1,2)) || !in_array($source,array(1,2))) show_message('参数错误！');
			$_Version = new VersionModel();
			if($_Version->getRow(array('type'=>$type,'source'=>$source,'version'=>$version)))   show_message('版本已存在！');
			if(!$url){
				if(!$fileArr || !$pathto) show_message('安装包不能为空！');
				$extension = pathinfo($fileArr['name'],PATHINFO_EXTENSION);
				if(!in_array($extension,array('apk','ipa'))) show_message('安装包格式错误！');
				$path = $pathto.($source == 1? "android/":"ios/").$fileArr['name'];
				if(!file_exists($path)){
					if(!move_uploaded_file($fileArr['tmp_name'], $path)) show_message('安装包上传失败！');
				}
				$url = Yaf_Registry::get('config')->path->package.($source == 1? "package/android/":"package/ios/").$fileArr['name'];
			}
			$data = array(
				'source'=>$source,
				'type'=>$type,
				'level'=>$level,
				'size'=>$size,
				'date'=>$date,
				'version'=>$version,
				'url'=>$url,
				'desc'=>$desc,
			);
			if(!$_Version->insertData($data))    show_message('版本添加失败！');
			show_message('版本添加成功！','','add');
		}	
		$this->getView()->assign('sources', array('1'=>'android','2'=>'ios'));
		$this->getView()->assign('types', array('1'=>'安装包','2'=>'补丁'));
		$this->getView()->assign('levels', array('0'=>'可选','1'=>'强制'));
	}
	
	public function editAction(){
		if($this->_POST){
			$id = $this->post('id',0,'intval');
			$version = $this->post('version','','trim');
			$type = $this->post('type',0,'intval');
			$source = $this->post('source',0,'intval');
			$level = $this->post('level',0,'intval');
			$size = $this->post('size','','trim');
			$date = $this->post('date',date('Y-m-d'),'isDate');
			$url = $this->post('url','','trim');
			$desc = $this->post('desc','','trim');
			if(!$id || !$url || !$version || !in_array($type,array(1,2)) || !in_array($source,array(1,2))) show_message('参数错误！');
			$_Version = new VersionModel();
			if(!$_Version->getRow(array('id'=>$id)))   show_message('版本不存在！');
			$data = array(
				'source'=>$source,
				'type'=>$type,
				'level'=>$level,
				'size'=>$size,
				'date'=>$date,
				'version'=>$version,
				'url'=>$url,
				'desc'=>$desc,
			);
			if(!$_Version->updateData($data,array('id'=>$id)))    show_message('版本修改失败！');
			show_message('版本修改成功！','','edit');
		}else{
			$id = $this->get('id',0,'intval');
			if(!$id) show_message('参数错误！');
			$_Version = new VersionModel();
			$version = $_Version->getRow(array('id'=>$id));
			if(!$version)   show_message('版本不存在！');
			$this->getView()->assign('version', $version);
			$this->getView()->assign('sources', array('1'=>'android','2'=>'ios'));
			$this->getView()->assign('types', array('1'=>'安装包','2'=>'补丁'));
			$this->getView()->assign('levels', array('0'=>'可选','1'=>'强制'));
		}	
	}
	
	
	public function deleteAction(){
		$ids = $this->_GET['id'] ? array($this->get('id',0,'intval')):$this->post('id');
		if(!$ids || empty($ids)) show_message('参数错误！');
		$_Version = new VersionModel();
		foreach($ids as $id){
			$version = $_Version->getRow(array('id' => $id));
			//版本不存在！
			if(!$version) continue;
			//删除版本失败
			if(!$_Version->deleteData(array('id'=>$id))) continue;
		}
		show_message('删除版本成功！',url('admin','version'));
	}
}