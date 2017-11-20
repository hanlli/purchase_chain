<?php


namespace app\common\model;

/**
* 设置模型
*/
class Channel extends Base{

	protected $type = array(
		'id'  => 'integer',
	);

	protected $auto = array('update_time', 'status'=>1);
	protected $insert = array('create_time');
}