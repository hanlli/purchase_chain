<?php


namespace app\user\controller;
use app\common\controller\User;

class Index extends User {

	public function index() {
		return $this->fetch();
	}
}
