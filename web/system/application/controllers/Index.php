<?php
/**
 * 首页
 */
class IndexController extends Yaf_Controller_Base_Abstract {
	/**
	 * 首页
	 */
	public function indexAction() {
		if(!$this->user){
			$this->redirect('/Login');
		}else{
			$this->redirect('/User');
		}
	}
	
	private $source = '';
		
	public function protocolAction()
	{		
		$catid = 15;
		$cms = new CmsModel();
		$result = $cms->getContent($catid);		
		$this->getView()->assign('result', $result[0]);	
		$this->getSource();
		$this->getView()->assign('source',  $this->source);		
	}

	public function aboutAction()
	{		
		$catid = 10;
		$cms = new CmsModel();		
		$result = $cms->getContent($catid);		
		$this->getView()->assign('result', $result[0]);
		$this->getSource();
		$this->getView()->assign('source',  $this->source);		
	}

	public function jobAction()
	{
		$catid = 11;
		$cms = new CmsModel();
		$result = $cms->getContent($catid);		
		$this->getView()->assign('result', $result[0]);
		$this->getSource();
		$this->getView()->assign('source', $this->source);		
	}
	
	public function connectAction()
	{
		$catid = 12;
		$cms = new CmsModel();
		$result = $cms->getContent($catid);		
		$this->getView()->assign('result', $result[0]);
		$this->getSource();
		$this->getView()->assign('source', $this->source);		
	}

	public function guideAction()
	{
		$catid = 16;		
		$cms = new CmsModel();
		$result = $cms->catalogList($catid);			
		$this->getView()->assign('result', $result);
		$this->getSource();
		$this->getView()->assign('source', $this->source);		
	}

	private function getSource()
	{
		$user_agent = $_SERVER['HTTP_USER_AGENT'];		
		if(strpos(strtolower($user_agent),"android") !== false)
		{
			$this->source = 'android';
		}else if(strpos(strtolower($user_agent),"iphone") !== false)
		{
			$this->source = 'ios';
		}
	}
}
