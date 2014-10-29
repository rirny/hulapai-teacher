<?php
/**
 * 网站地图
 */
class SitemapController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$html_file = CMS_HTML.'/hulapai/html/sitemap/index.html';
		$this->getView()->assign('html_file', $html_file);
	}
}
