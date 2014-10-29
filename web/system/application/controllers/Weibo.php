<?php
/**
 * 官方微博
 */
class WeiboController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {		
		$page = $this->get('page',1,'intval');	
		$_Feed = new FeedModel();
		$feeds = $_Feed->getList($page,20,array('uid'=>2),'*','','publish_time desc');		
		foreach($feeds['data'] as &$item)
		{
			$item['attachs'] = unserialize($item['attachs']);
		}
		$this->getView()->assign('logo', imageUrl(2, 1,50, true));		
		$this->getView()->assign('pages', $feeds['pages']);		
		$this->getView()->assign('feeds', $feeds['data']);
		
	}

}
