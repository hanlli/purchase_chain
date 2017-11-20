<?php


namespace app\admin\controller;
use app\common\controller\Admin;

class Form extends Admin {

	//自定义表单
	public function index(){
		$this->assign('meta_title','自定义表单');
		return $this->fetch();
	}
}