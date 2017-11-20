<?php


namespace addons\syslogin\controller;
use app\common\controller\Addons;

class Admin extends Addons{
	
    public function setting(){
		$this->template('admin/login');
    }
}
