<?php
/**
 * 首页
 */
class FeedController extends Yaf_Controller_Base_Abstract {
	
	public function indexAction() {
		$page = $this->get('page',1,'intval');
		$_Feed = new FeedModel();
		$feeds = $_Feed->getList($page,20, array('uid' => $this->uid),'*','','publish_time desc');        
        $this->getView()->assign('feeds', $feeds);
		$this->getView()->assign('pages', $feeds['pages']);
	}

	public function addAction(){
        if($this->_POST){
        	$content = $this->post('content','','trim');
			$attach_ids = $this->post('attach',array());
			$attachs = array();
			if(!$content && !$attach_ids)
			{			
				show_message('内容或图片不能为空！');
			}
			if($attach_ids)
	        {
	            $attach_ids_arr = array();
	            $_Attach = new AttachModel();//load_model('attach');
	            $attach_ids = implode(',',$attach_ids);
	            $att = $_Attach->getAttachs($attach_ids);
	            if($att){
	                $path = Yaf_Registry::get('config')->path->image;
	                foreach($att as $key=>$a)
	                {
	                    $attach_ids_arr[] = $a['attach_id'];
                        $imgInfo = pathinfo($a['save_name']);
                        $imagePath = $path.'/'.$a['save_path'];	
                        $attachs[$key]['attach_id'] = $a['attach_id'];
                        $attachs[$key]['attach_url'] = $a['save_path'].$a['save_name'];
                        $attachs[$key]['attach_small'] = $a['save_path'].$imgInfo['filename'].'_small.'.$imgInfo['extension'];
                        $attachs[$key]['attach_middle'] = $a['save_path'].$imgInfo['filename'].'_small.'.$imgInfo['extension'];
                        $attachs[$key]['domain'] = 'HOST_IMAGE';
	                }
	                $type = 'postimage';
	            }
	            $attach_ids = implode(',',$attach_ids_arr);
	        }
			//发表微博
			if(!$this->postFeed('post',$content,$attach_ids,$attachs)){
				show_message('微博发布失败！');
			}
			show_message('微博发布成功！');
        }else{
	        $timestamp = time();
	        $this->getView()->assign('timestamp', $timestamp);        
	        $this->getView()->assign('token', md5(Yaf_Registry::get('config')->path->apiKey.$timestamp)); 
        }    
	}

	private function postFeed($type='post',$content='',$attach_ids='',$attachs=array(),$is_repost=0,$source_id=0){		
		$nowTime = time();
		$feed = array(
			'uid'=> $this->uid,
			'type'=>$type,
			'is_repost'=>$is_repost,
			'source_id'=>$source_id,
			'source_info'=>serialize(array()),
			'attach_ids'=>json_encode($attach_ids),
			'attachs'=>serialize($attachs),
			'client_ip'=>getIp(),
			'publish_time'=>$nowTime,
			'from'=> 'web',
			'content'=>$content,
		);
		$_Feed = new FeedModel();// load_model('feed');
		//入微博主表
		$feed_id = $_Feed->insertData($feed);
		return $feed_id;
	}
    
    public function deleteAction(){
        $ids = $this->_GET['id'] ? array($this->get('id',0,'intval')):$this->post('id');
		if(!$ids || empty($ids)) show_message('参数错误！');
		$_Feed = new FeedModel();
		foreach($ids as $id){
			$feed = $_Feed->getRow(array('feed_id' => $id));
			//动态不存在！
			if(!$feed) continue;
			//删除动态失败
			if(!$_Feed->deleteData(array('feed_id'=>$id))) continue;
		}
		show_message('删除动态成功！',url('admin','feed','index')); 
	}
}
