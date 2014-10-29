<?php
/**
 * 下载
 */
class DownloadController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$version = $this->get('version','','trim');
		$source = $this->get('source',1,'intval');
		if($version){
			if(is_numeric($version)){
				$_Version = new VersionModel();
				$info = $_Version->getRow(array('source'=>$source,'version'=>$version));
				if($info && $info['url']){
					$andChar = strpos($info['url'],'?') > 0 ?'&t='.time():"?t=".time(); 
					header("Location:".$info['url'].$andChar); 
					exit;
				}
			}else{
				$path = array(
					'.apk'=>'android',
					'.ipa'=>'ios',
				);
				$extend = mb_substr($version,-4);
				if(in_array($extend,array_keys($path))){
					if(in_array($version,array('latest.apk','hulapai.apk','latest.ipa','hulapai.ipa'))){
						$source = $version == 'latest.apk' || $version == 'hulapai.apk' ? 1 : 2;
						$_Version = new VersionModel();
						$info = $_Version->getRow(array('source'=>$source,'type'=>1),'*','ID desc');
						if($info && $info['url']){
							$andChar = strpos($info['url'],'?') > 0 ?'&t='.time():"?t=".time();
							header("Location:".$info['url'].$andChar);
							exit;
						}
					}else{
						$file = UPLOAD_PATH.'/package/'.$path[$extend].'/'.$version;
						if(file_exists($file)){
							$andChar = '?t='.time(); 
							header("Location:". Yaf_Registry::get('config')->path->package.'/package/'.$path[$extend].'/'.$version.$andChar); 
							exit;
						}
					}
				}
			}
			show_message('文件不存在');
		}else{
			$_Version = new VersionModel();
			$info = $_Version->getRow(array('source'=>$source,'type'=>1),'*','ID desc');
			$this->getView()->assign('info', $info);
		}
	}
}
