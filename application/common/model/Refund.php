<?php


namespace app\common\model;

/**
* 供应商基类
*/
class Refund extends \think\Model{
  public static function getRefundStatusDict() {
    return ['0'=>'', '1'=>'退货中', '2'=>'已完成', '3'=>'已取消'];
  }

  public static function getRefundingStatus() {
    return '1';
  }

  protected function getRefundStatusAttr($value, $data){
    $refund_status_dict = self::getRefundStatusDict();
    return isset($refund_status_dict[$data['refund_status_id']])?$refund_status_dict[$data['refund_status_id']]:'';
  }

  public function refund_pproduct()
  {
      return $this->hasMany('RefundPproduct');
  }

  public function addRefund($refund, $refund_pproduct) {
    $refund_model = model('Refund');
    $rpp_model = model('RefundPproduct');
    $rp_model = model('RefundProduct');
    $refund_id = $refund_model->insertGetId($refund);
    foreach ($refund_pproduct as $rpp) {
      $rpp['main']['refund_id'] = $refund_id;
      $rpp_id = $rpp_model->insertGetId($rpp['main']);
      foreach ($rpp['child'] as $rp) {
        $rp['refund_pproduct_id'] = $rpp_id;
        $rp_model->insert($rp);
      }
    }
    return $refund_id;
  }
}