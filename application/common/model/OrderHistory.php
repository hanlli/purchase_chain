<?php


namespace app\common\model;
use app\common\model\Order;
use app\common\model\Refund;
/**
* 供应商基类
*/
class OrderHistory extends \think\Model{
  public function addOrderHistory($order_id, $order_status_id=null, $before_order_status_id=null, $comment=null, $refund_id=null,  $refund_status_id=null, $before_refund_status_id=null) {
    $user_model = model('User');
    $uid = session('user_auth.uid');
    $user_info = $user_model->getInfo($uid);
    $order_model = model('Order');
    $refund_model = model('Refund');

    $data['order_id'] = $order_id;
    $data['operator_id'] = $user_info['uid'];
    $data['operator_name'] = $user_info['username']; //后面可能会改为nickname
    $data['comment'] = empty($comment)?'':$comment;
    $data['date_added'] = date('Y-m-d H:i:s');

    $order_info = $order_model->where(['order_id'=>$order_id])->find();
    //有refund_id的话表示是填写refund记录，order status保持
    if(!is_null($refund_id)) {
      $refund_info = $refund_model->where(['refund_id'=>$refund_id])->find();
      // $data['before_order_status_id'] = $data['order_status_id'] = $order_info['order_status_id'];
      if($refund_info) {
        //更新refund
        $data['before_refund_status_id'] = is_null($before_refund_status_id)?$refund_info['refund_status_id']:$before_refund_status_id;
        $data['refund_status_id'] = is_null($refund_status_id)?$refund_info['refund_status_id']:$refund_status_id;
        $data['refund_id'] = $refund_id;
      } else {
        //添加refund
      }
    } else {
      //修改订单状态
      $data['before_order_status_id'] = is_null($before_order_status_id)?$order_info['order_status_id']:$before_order_status_id;
        $data['order_status_id'] = is_null($order_status_id)?$order_info['order_status_id']:$order_status_id;
    }
    $this->insert($data);
  }

  public function addNewOrderHistory($order_id) {
    $data['order_id'] = $order_id;
    $data['order_status_id'] = Order::getWaitingForPayStatus();
    $data['comment'] = '创建订单';
    $this->addOrderHistory($order_id, $data['order_status_id'], 0, $data['comment']);
  }

  public function addNewRefundOrderHistory($order_id, $refund_id) {
    $data['order_id'] = $order_id;
    $data['refund_status_id'] = Refund::getRefundingStatus();
    $data['comment'] = '创建退货单';
    $this->addOrderHistory($order_id, null, null, $data['comment'], $refund_id, $data['refund_status_id'], 0); 
  }

  public function getOrderHistory($order_id) {
    $order_histories = db('OrderHistory')->where(['order_id'=>$$order_id])->order('order_history_id desc')->select();
    return $order_histories;
  }

  protected function getOrderStatusAttr($value, $data){
    $order_status_dict = Order::getOrderStatusDict();
    return isset($order_status_dict[$data['order_status_id']])?$order_status_dict[$data['order_status_id']]:'';
  }
  protected function getBeforeOrderStatusAttr($value, $data){
    $order_status_dict = Order::getOrderStatusDict();
    return isset($order_status_dict[$data['before_order_status_id']])?$order_status_dict[$data['before_order_status_id']]:'';
  }
  protected function getRefundStatusAttr($value, $data){
    $refund_status_dict = Refund::getRefundStatusDict();
    return isset($refund_status_dict[$data['refund_status_id']])?$refund_status_dict[$data['refund_status_id']]:'';
  }
  protected function getBeforeRefundStatusAttr($value, $data){
    $refund_status_dict = Refund::getRefundStatusDict();
    return isset($refund_status_dict[$data['before_refund_status_id']])?$refund_status_dict[$data['before_refund_status_id']]:'';
  }

}