<?php


namespace app\common\model;

/**
* 供应商基类
*/
class CartProduct extends \think\Model{


  protected static function init()
  {
  }

  public function product()
  {
      return $this->hasOne('Product','product_id','product_id');
  }
}