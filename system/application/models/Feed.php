<?php
class FeedModel extends BaseModel{
	public $table = 'ts_feed';
	public function __construct() {
    	parent::__construct();
    }

	public function getFeed($feed_id){
		$feeds = $this->getFeedsByFeedIds($feed_id);
		return $feeds[0];
	}

	public function getFeedsByFeedIds($feedIds = ''){
		$sqlSourceIds = "SELECT GROUP_CONCAT(source_id) as source_ids FROM $this->_table where feed_id in ($feedIds) and source_id > 0";
		$result = DB()->query($sqlSourceIds);
		$sourceIds = $result['source_ids'] ? $result['source_ids'] : 0;
		$sourceIds = array_unique(explode(',',$sourceIds));
		$sourceIds = implode(',',$sourceIds);
		$sql = "SELECT a.*,b.comment_count as source_comment_count,b.repost_count as source_repost_count,b.collect_count as source_collect_count,b.digg_count as source_digg_count,c.firstname,c.lastname,c.teacher,c.nickname,c.gender,c.avatar,c.hulaid FROM $this->_table AS a left join (select feed_id,comment_count,repost_count,collect_count,digg_count from $this->_table where feed_id in ($sourceIds)) as b on a.source_id = b.feed_id left join $this->_table_user as c on a.uid = c.id where a.feed_id in ($feedIds) ORDER BY a.feed_id DESC";
		$result = DB()->query($sql)->result_array();
		if($result){
			foreach($result as &$_result){
				$_result['attachs'] = unserialize($_result['attachs']);
				$_result['source_info'] = unserialize($_result['source_info']);
				if($_result['source_info']){
					$_result['source_info']['comment_count'] = $_result['source_comment_count'];
					$_result['source_info']['repost_count'] = $_result['source_repost_count'];
					$_result['source_info']['collect_count'] = $_result['source_collect_count'];
					$_result['source_info']['digg_count'] = $_result['source_digg_count'];
				}
				$_result['user'] = array(
					'_id'=>$_result['uid'],
					'nickname'=>$_result['nickname'],
                    'firstname' => $_result['firstname'],
                    'lastname' => $_result['lastname'],
                    'teacher' => $_result['teacher'],
					'gender'=>$_result['gender'],
					'avatar'=>$_result['avatar'],
					'hulaid'=>$_result['hulaid']
				);
				$_result['publish_time'] = date('Y-m-d H:i:s',$_result['publish_time']);
				unset($_result['uid']);
				unset($_result['nickname']);
				unset($_result['gender']);
				unset($_result['avatar']);
				unset($_result['hulaid'], $_result['firstname'],$_result['lastname']);
				unset($_result['source_comment_count']);
				unset($_result['source_repost_count']);
				unset($_result['source_repost_count']);
				unset($_result['source_collect_count']);
				unset($_result['client_ip']);
				unset($_result['source_id']);
			}
		}
		return $result;
	}
	
	// 
	public function data_increment($key, $where, $step)
	{
		$this->db->update($this->table, array($key . " = " . $key . "+" .$step => Null), $where);
	}
    
    public function data_insert(array $data)
    {
		if(!$data) return false;
		$result = $this->db->insert('ts_feed_user_data',$data);
		if($result){
			return  $this->db->insert_id();
		}
		return false;
	}
    
    public function getUserFollowingUidStr($uid,$offset=0,$pagesize=20){
		$tmpSql = "select fid from ts_feed_user_follow where uid='$uid'";
		if($pagesize){
			$tmpSql .= " LIMIT $offset,$pagesize";
		}
		$sql = "SELECT GROUP_CONCAT(t.fid) as uids FROM ($tmpSql) t";
		$result = $this->db->query($sql)->result_array();
		return $result['uids'] ? $result['uids'] : '';
	}
    
    public function test()
    {
          $query = $this->db->query('select * from `t_user` limit 10'); 
          print_r($query->result_array());
    }
}