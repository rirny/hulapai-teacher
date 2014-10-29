<?php
/**
 * 诚征英才
 */
class HrController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$html_file = CMS_HTML.'/hulapai/html/hr/index.html';
		$this->getView()->assign('html_file', $html_file);
	}
}
