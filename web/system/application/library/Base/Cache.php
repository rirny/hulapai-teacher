<?php
global $_G;
// 设置
$_G['cache']['yaf_setting'] = array(
    'dbhandle' => 'dbCfg',
    'sql' => 'select * from setting',
    'index' => 'type,name',      // 索引列,多个以','分割
    'value' => 'value',   // 取得值的列,如果要取得多个可不设置该项值
    'json'=> true,//是否生成json文件
    'jsonRemoveFields'=>'description',//json 需要排除的字段
);