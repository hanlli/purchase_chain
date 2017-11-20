<?php


namespace app\common\model;

/**
* 供应商基类
*/
class Order extends \think\Model{
  protected static function init()
  {
  }

  public function pproduct()
  {
      return $this->hasOne('Pproduct','pproduct_id', 'pproduct_id')->setAlias(['order'=>'c','pproduct'=>'pp']);
  }
  
  public function order_pproduct()
  {
      return $this->hasMany('OrderPproduct');
  }

  public function refund()
  {
      return $this->hasMany('Refund');
  }

  //注意订单状态只能增不能减
  public static function getOrderStatusDict() {
    return ['0'=>'', '1'=>'待付款', '2'=>'待发货', '3'=>'已发货', '4'=>'已到港', '5'=>'已完成', '6'=>'已取消'];
  }

  public static function getWaitingForPayStatus() {
    return '1';
  }

  public function addOrder($product_data) {
    $user_model = model('User');
    $pproduct_model = model('Pproduct');
    $order_model = model('Order');
    $opp_model = model('OrderPproduct');
    $op_model = model('OrderProduct');
    //组装客户和订单信息
    $uid = session('user_auth.uid');
    $user_info = $user_model->getInfo($uid);
    $data['customer_id'] = $user_info['uid'];
    $data['customer_name'] = $user_info['username']; //后面可能会改为nickname
    $data['customer_telephone'] = $user_info['mobile']?$user_info['mobile']:'';

    $data['currency_id'] = 1; //暂时写死为1，后期根据当前session('cur_country_id')对应的currency填写
    $data['currency_code'] = 'EUR'; //暂时写死为EUR，后期根据当前session('cur_country_id')对应的currency填写
    $data['currency_value'] = 1; //暂时写死为1，后期根据当前session('cur_country_id')对应的currency填写

    //总订单total
    $pproduct_ids = array_unique(array_column($product_data, 'pproduct_id'));
    $pproduct_prices = $pproduct_model->getPrices($pproduct_ids);
    $total_num = 0;
    $total = 0;
    foreach ($product_data as $qty_info) {
      $total += $qty_info['quantity']*$pproduct_prices[$qty_info['pproduct_id']];
      $total_num += $qty_info['quantity'];
    }
    $data['total'] = $total;
    $data['total_num'] = $total_num;
    
    $data['order_status_id'] = 1; //暂时先hardcode
    $data['ip'] = \think\Request::instance()->ip();
    $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $data['date_added'] = date('Y-m-d H:i:s');
    $data['date_modified'] = date('Y-m-d H:i:s');
    $data['expected_time'] = date('Y-m-d H:i:s');
    $data['vendor_detail'] = $data['comment'] = '';
    $order_id = $order_model->insertGetId($data);
    //组装订单母商品信息
    //先组织母子商品数组
    $order_pproduct = [];
    foreach ($product_data as $prd_id => $prd_data) {
      if(!array_key_exists($prd_data['pproduct_id'], $order_pproduct)) {
        $order_pproduct[$prd_data['pproduct_id']] = [];
      }
      $order_pproduct[$prd_data['pproduct_id']]['child'][] = $prd_data;
    }
    foreach ($order_pproduct as $pproduct_id => $child) {
      $tmp = $pproduct_model->with('pproduct_attr')->where(['pproduct.pproduct_id'=>$pproduct_id])->find();
      $main_keys = ['pproduct_id', 'name', 'spu', 'code', 'image', 'category_id', 'vendor_code', 'cost', 'price', 'msrp', 'discount'];
      foreach ($main_keys as $mk) {
        $tmp_opp[$mk] = $tmp[$mk];  
      }
      $tmp_opp['color'] = $tmp->pproduct_attr['color'];
      $tmp_opp['order_id'] = $order_id;
      $order_pproduct[$pproduct_id]['main'] = $tmp_opp;
    }
    //然后插入order_pproduct和order_product
    foreach ($order_pproduct as $pproduct_id => $opp) {
      $opp_id = $opp_model->insertGetId($opp['main']);
      $child_data = $opp['child'];
      foreach ($child_data as $key=>&$cd) {
        $cd['order_pproduct_id'] = $opp_id;
      }
      $op_model->insertAll($child_data);
    }
    return $order_id;
  }

  protected function getOrderStatusAttr($value, $data){
    $order_status_dict = self::getOrderStatusDict();
    return isset($order_status_dict[$data['order_status_id']])?$order_status_dict[$data['order_status_id']]:'';
  }
  
}