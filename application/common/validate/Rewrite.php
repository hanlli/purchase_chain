<?php


namespace app\common\validate;

/**
* 设置模型
*/
class Rewrite extends Base{

	protected $rule = array(
		'rule'   => 'require|unique:Rewrite',
		'url'   => 'require'
	);
	protected $message = array(
		'rule.require'   => '规则必须！',
		'rule.unique'   => '规则已存在！',
		'url.require'   => '路由地址标题必须！',
	);

}