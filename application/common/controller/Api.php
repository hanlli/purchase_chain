<?php


namespace app\common\controller;

class Api {

	protected $data;

	public function __construct() {
		$this->data = array('code' => 0, 'msg' => '', 'time' => time(), 'data' => '');
	}
}