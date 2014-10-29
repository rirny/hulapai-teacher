<?php
/**
 * 联系我们
 */
class ConnectController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$html_file = CMS_HTML.'/hulapai/html/connect/index.html';
		$this->getView()->assign('html_file', $html_file);
	}
}
