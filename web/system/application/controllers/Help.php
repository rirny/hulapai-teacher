<?php
/**
 * 帮助中心
 */
class HelpController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$html_file = CMS_HTML.'/hulapai/help/index.html';
		$this->getView()->assign('html_file', $html_file);
	}
}
