<?php


namespace app\common\model;

/**
* 供应商基类
*/
class OrderPproduct extends \think\Model{
  protected static $category_dict = [];
  protected static function init()
  {
    $pproduct_model = model('Pproduct');
    self::$category_dict = $pproduct_model->getCategoryDict();
  }

  public function order_product()
  {
      return $this->hasMany('OrderProduct');
  }
  
  protected function getCategoryAttr($value, $data){
    return isset(self::$category_dict[$data['category_id']])?self::$category_dict[$data['category_id']]:'';
  }
}