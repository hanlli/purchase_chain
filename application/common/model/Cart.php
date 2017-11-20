<?php


namespace app\common\model;

/**
* 供应商基类
*/
class Cart extends \think\Model{
  protected static $items_type_count = 0;
  protected static $items_count = 0;
  protected static $items_total = 0;

  protected static function init()
  {
    //根据数据库内cart数据，初始化$items_count和$items_total
    self::updateItemsCountAndTotal();
  }

  public function pproduct()
  {
      return $this->hasOne('Pproduct','pproduct_id', 'pproduct_id')->setAlias(['cart'=>'c','pproduct'=>'pp']);
  }
  
  public function cart_product()
  {
      return $this->hasMany('CartProduct');
  }

  public function getItems() {
    $map['country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');
    $items = db('cart')->alias('c')->join('cart_product cp', 'c.cart_id=cp.cart_id')->where($map)->column('product_id,quantity');
    return $items;
  }
  public function getItemsByPproductId($pproduct_id) {
    $map['country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');
    $map['c.pproduct_id'] = $pproduct_id;
    $items = db('cart')->alias('c')->join('cart_product cp', 'c.cart_id=cp.cart_id')->where($map)->column('product_id,quantity');
    return $items;
  }

  public function addItem($data) {
    $this->addItemByIds([$data]);
  }

  public function addItemByIds($product_data) {
    $country_id = session('cur_country_id');
    $customer_id = session('user_auth.uid');
    //获取所有$product_data中的pproduct_id
    $pproduct_ids = array_unique(array_column($product_data, 'pproduct_id'));
    //比对当前cart中的pproduct_id，如果不存在则做cart插入处理，同时返回pproduct_id:cart_id字典
    $map['country_id'] = $country_id;
    $map['customer_id'] = $customer_id;
    $map['pproduct_id'] = ['in', $pproduct_ids];
    $cart_model = db('cart');
    $cart_items = $cart_model->where($map)->column('pproduct_id,cart_id');
    $in_cart_pproduct_ids = array_keys($cart_items);
    $not_in_cart_pproduct_ids = array_diff($pproduct_ids, $in_cart_pproduct_ids);
    
    if(!empty($not_in_cart_pproduct_ids)) {
      $data = [];
      foreach ($not_in_cart_pproduct_ids as $pproduct_id) {
        $data[] = ['customer_id'=>$customer_id,'country_id'=>$country_id,'pproduct_id'=>$pproduct_id];
      }
      $cart_model->insertAll($data);
      $cart_items = $cart_model->where($map)->column('pproduct_id,cart_id');
    }
    //循环插入product_id记录
    $sql_tpl = "INSERT INTO cart_product (cart_id, product_id, quantity, date_added) VALUES ";
    $cart_product_data = [];
    foreach ($product_data as $pd) {
      $cart_product_data[] = implode(',', [$cart_items[$pd['pproduct_id']], $pd['product_id'], $pd['quantity'], 'NOW()']);
    }
    $cart_product_data_str = '('.implode('),(', $cart_product_data).')';
    $sql_tpl .= $cart_product_data_str;
    $sql_tpl .= " ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity), date_added=VALUES(date_added)";

    $cart_model->execute($sql_tpl);

    //强制更新$items_count和$items_total
    self::updateItemsCountAndTotal();
    
    return true;
  }

  public function removeItemByIds($product_data) {
    //清空cart_product内的记录
    $map['country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');
    $map['product_id'] = $product_data;
    $sql_tpl = "DELETE cp FROM `cart`c INNER JOIN `cart_product` cp ON c.cart_id=cp.cart_id WHERE country_id=%COUNTRY_ID% AND customer_id=%CUSTOMER_ID% AND `product_id` IN (%PRODUCT_IDS%)";
    $sql = str_replace(
            ['%COUNTRY_ID%', '%CUSTOMER_ID%', '%PRODUCT_IDS%'],
            [
              $map['country_id'],$map['customer_id'],implode(',', $map['product_id'])
            ], $sql_tpl);
    db('cart')->execute($sql);
    //删除cart中不存在的pproduct_id
    $this->delObsoleteCartId();
  }
  public function delObsoleteCartId() {
    $cart = db('cart');
    $cart_product = db('cart_product');
    $map['country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');
    //遍历所有当前用户的cart_product中的cart_id
    $cart_ids_in_cart_product = db('cart')->alias('c')->join('cart_product cp', 'c.cart_id=cp.cart_id')->where($map)->field('DISTINCT c.cart_id')->select();
    $cart_ids_in_cart_product = array_column($cart_ids_in_cart_product, 'cart_id');
    //找到cart中所有pproduct_id
    $cart_ids_in_cart = db('cart')->where($map)->field('DISTINCT cart_id')->select();
    $cart_ids_in_cart = array_column($cart_ids_in_cart, 'cart_id');
    //找到不存在的pproduct_id差集
    $obsolete_cart_ids = array_diff($cart_ids_in_cart, $cart_ids_in_cart_product);
    //删除cart中对应的pproduct_id
    if(!empty($obsolete_cart_ids)) {
      $map = [];
      $map['cart_id'] = ['in', $obsolete_cart_ids];
      db('cart')->where($map)->delete();  
    }
  }

  public function clearItems() {  
    $map['country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');
    db('cart')->where($map)->delete();  
  }
  /**
   * 强制购物车总商品数量与金额
   * @author jonathan lee
   */
  public static function updateItemsCountAndTotal() {
    self::getItemsTypeCount(true);
    self::getItemsCount(true);
    self::getTotal(true);
  }
  /**
   * 获取购物车总商品类型数量
   * @author jonathan lee
   */
  public static function getItemsTypeCount($force=false) {
    if($force) {
      $map['country_id'] = session('cur_country_id');
      $map['customer_id'] = session('user_auth.uid');
      self::$items_type_count = db('cart')->where($map)->count('pproduct_id');
    }
    return self::$items_type_count;
  }
  /**
   * 获取购物车总商品数量
   * @author jonathan lee
   */
  public static function getItemsCount($force=false) {
    if($force) {
      $map['country_id'] = session('cur_country_id');
      $map['customer_id'] = session('user_auth.uid');
      self::$items_count = db('cart')->alias('c')->join('cart_product cp', 'c.cart_id=cp.cart_id')->where($map)->sum('quantity');
    }
    return self::$items_count;
  }
  /**
   * 获取购物车总金额
   * @author jonathan lee
   */
  public static function getTotal($force=false) {
    if($force) {
      $map['country_id'] = session('cur_country_id');
      $map['customer_id'] = session('user_auth.uid');
      $pproduct_qty = db('cart')->alias('c')->join('cart_product cp', 'c.cart_id=cp.cart_id')->where($map)->group('pproduct_id')->field('pproduct_id, sum(quantity) as sum_qty')->select();
      if(empty($pproduct_qty)) {
        self::$items_total = 0;
        return;
      }
      $pproduct_model = model('Pproduct');
      $pproduct_ids = array_column($pproduct_qty, 'pproduct_id');
      $pproduct_prices = $pproduct_model->getPrices($pproduct_ids);
      $total = 0;
      foreach ($pproduct_qty as $qty_info) {
        $total += $qty_info['sum_qty']*$pproduct_prices[$qty_info['pproduct_id']];
      }
      self::$items_total = $total;
    }
    return self::$items_total;
  }
  
}