<?php


namespace app\admin\controller;
use app\common\controller\Admin;

class Refund extends Admin {
  protected $warning = null;
  protected $refund_error_data = []; //用于指示哪个商品加入订单失败

  public function index(){
    //判断用户角色决定展示简单版还是完整版的订单列表
    $order_id = input('order_id');
    $order_model = model('Order');
    $opp_model = model('OrderPproduct');
    //普通订单
    $map['order_id'] = $order_id;
    $order = 'order_pproduct_id desc';
    $list = $opp_model->with('order_product')->where($map)->order($order)->paginate(30);
    $data = array(
      'order_id' => $order_id,
      'order_info' => $order_model->find($order_id),
      'list' => $list,
      'page' => $list->render(),
    ); 
    //根据退货单表获取已经退货的商品数量
    $map = [];
    $map['order_id'] = $order_id;
    $refund_model = model('Refund');
    $refunds = $refund_model->where($map)->select();
    
    $this->assign($data);
    $this->assign('meta_title','创建退货单');
    return $this->fetch();
  }

  /**
   * 添加新建订单接口
   * @author jonathan lee
   */
  public function add() {
    //因为上传
    $products = input('post.');
    $to_add_items = $products['refund_quantity'];

    $is_valid = $this->validateRefundProducts($to_add_items);
    if(!$is_valid) {
      $this->error($this->warning, '', $this->refund_error_data);
    }
    //调用order model新建订单
    //根据$to_add_items拼装refund / refund_pproduct 和 refund_product数据结构
    $op_model = model('OrderProduct');
    $opp_model = model('OrderPproduct');
    $order_model = model('Order');

    $order_product_ids = array_keys($to_add_items);
    $map['order_product_id'] = ['in', $order_product_ids];
    $refund_product = $op_model->where($map)->column('order_product_id, order_pproduct_id, product_id,sku,size', 'order_product_id');
    foreach ($refund_product as $order_product_id => $rp) {
      $refund_product[$order_product_id]['quantity'] = $to_add_items[$rp['order_product_id']];
    }

    $refund_pproduct = [];
    foreach ($refund_product as $rp) {
      $tmp = $rp;
      $id = $tmp['order_pproduct_id'];
      if (!isset($refund_pproduct[$id])) {
         $opp_data = $opp_model->where(['order_pproduct_id'=>$id])->find()->getData();
         $refund_pproduct[$id]['main'] = $opp_data;
         $refund_pproduct[$id]['child'] = [];
      }
      $refund_pproduct[$id]['child'][] = $tmp;
    }
    
    $refund = [];
    $order_id = 0;
    $total_num = 0;
    $total = 0;
    $refund_status_id = 1; //退货中
    $date_added = $date_modified = date('Y-m-d H:i:s');
    foreach ($refund_pproduct as $rpp) {
      $order_id = $rpp['main']['order_id'];
      foreach ($rpp['child'] as $rp) {
        $total_num += $rp['quantity'];
        $total += $rp['quantity']*$rpp['main']['price'];
      }
    }
    $refund = ['order_id'=>$order_id, 'comment'=>'', 'total_num'=>$total_num, 'total'=>$total, 'refund_status_id'=>$refund_status_id, 'date_added'=>$date_added, 'date_modified'=>$date_modified];

    $refund_model = model('Refund');
    $refund_id = $refund_model->addRefund($refund, $refund_pproduct);

    //添加订单操作记录
    $oh_model = model('OrderHistory');
    $oh_model->addNewRefundOrderHistory($order_id, $refund_id);

    if($refund_id) {
      $this->success('成功添加订单', '', []);
    } else {
      $this->error('添加订单失败');
    }
    return;
  }

  /**
   * 为将要加入订单的商品做数量和存在性验证
   * @author jonathan lee
   */
  protected function validateRefundProducts($products) {
    //判断退货单商品数量是否<=订单数量+已有退货单内商品数量
    $order_product_ids = array_keys($products);
    $map['order_product_id'] = ['in', $order_product_ids];
    $order_products = db('OrderProduct')->where($map)->column('order_product_id, quantity');
    
    $refunded_products = [];
    $refunded = db('RefundProduct')->field('order_product_id, SUM(quantity) as quantity')->group('order_product_id')->where($map)->select();
    foreach ($refunded as $rf) {
      $refunded_products[$rf['order_product_id']] = $rf['quantity'];
    }
    
    foreach ($products as $order_product_id => $quantity) {
      $rfed_qty = isset($refunded_products[$order_product_id])?$refunded_products[$order_product_id]:0;
      if($quantity+$rfed_qty>$order_products[$order_product_id]) {
        $this->refund_error_data[$order_product_id] = ['order_product_id'=>$order_product_id,'max_qty'=>($order_products[$order_product_id]-$rfed_qty)];
      }
    }

    if(!empty($this->refund_error_data)) {
      $this->warning = '退货数量超出可退货数，请检查';
      return false;  
    }

    return true;
  }
}