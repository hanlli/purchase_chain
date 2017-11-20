<?php


namespace app\common\model;

/**
* 供应商基类
*/
class RefundPproduct extends \think\Model{
  protected static $category_dict = [];
  protected static function init()
  {
    $pproduct_model = model('Pproduct');
    self::$category_dict = $pproduct_model->getCategoryDict();
  }

  public function refund_product()
  {
      return $this->hasMany('RefundProduct');
  }
  
  protected function getCategoryAttr($value, $data){
    return isset(self::$category_dict[$data['category_id']])?self::$category_dict[$data['category_id']]:'';
  }
}