<?php


namespace app\admin\controller;
use app\common\controller\Admin;
// use app\common\Model\Cart as MCart;

class Cart extends Admin {
  public static $cart_input_name = 'pd';
  protected $warning = null;
  protected $cart_error_data = []; //用于指示哪个商品加入购物车失败

  /**
   * 购物车列表页
   * @author jonathan lee
   */
  public function index() {
    $map = [];
    //获取列表数据
    $map['c.country_id'] = session('cur_country_id');
    $map['customer_id'] = session('user_auth.uid');

    $order = '';
    $cart = model('Cart');
    $list = $cart->with('pproduct,cart_product.product')->where($map)->order($order)->paginate(20);
    // 记录当前列表页的cookie
    Cookie('__forward__', $_SERVER['REQUEST_URI']);
    $data = array(
      'list' => $list,
      'page' => $list->render(),
    );
    $data['items_type_count'] = $cart->getItemsTypeCount();
    $data['items_count'] = $cart->getItemsCount();
    $data['items_total'] = $cart->getTotal();

    $data['cart_input_name'] = self::$cart_input_name;

    $this->assign($data);
    $this->setMeta('进货单');
    return $this->fetch();
  }

  /**
   * 添加购物车接口
   * @author jonathan lee
   */
  public function add() {
    //因为上传
    $products = input('post.');
    $is_valid = $this->validateCart($products);
    if(!$is_valid) {
      $this->error($this->warning, '', $this->cart_error_data);
    }
    //调用cart model添加商品库存
    $to_add_items = $products[self::$cart_input_name];
    $product_model = model('Product');
    $product_ids = array_keys($to_add_items);
    $items_stock_info = $product_model->getStockInfoByIds($product_ids);
    foreach ($to_add_items as $product_id => $qty) {
      $items_stock_info[$product_id]['quantity'] = $qty;
    }
    
    $cart = model('Cart');
    $ret = $cart->addItemByIds($items_stock_info);
    if($ret) {
      $items_count = $cart->getItemsCount();
      $items_total = $cart->getTotal();
      $this->success('成功添加购物车', '', ['items_count'=>$items_count,'items_total'=>$items_total]);
    } else {
      $this->error('添加购物车失败');
    }
  }
  /**
   * 为将要加入购物车的商品做数量和存在性验证
   * @author jonathan lee
   */
  protected function validateCart($products) {
    //判断是否有需要加入购物车的商品
    if(empty($products)||!isset($products[self::$cart_input_name])) {
      $this->warning = '请输入需要添加的商品';
      return false;
    }
    //检查上传数据，并做商品库存与当前购物车商品库存检测
    $to_add_items = $products[self::$cart_input_name];
 
    $product_model = model('Product');
    $product_ids = array_keys($to_add_items);
    $items_stock = $product_model->getStockByIds($product_ids);

    $cart = model('Cart');
    $cart_items = $cart->getItems();

    foreach ($to_add_items as $product_id => $qty) {
      if(!array_key_exists($product_id, $items_stock)) {
        $items_stock[$product_id] = 0;
      }
      if(!array_key_exists($product_id, $cart_items)) {
        $cart_items[$product_id] = 0;
      }
      if($qty > ($items_stock[$product_id]-$cart_items[$product_id])) {
        $this->cart_error_data[$product_id] = ['product_id'=>$product_id,'max_qty'=>($items_stock[$product_id]-$cart_items[$product_id])];
      }
    }
    if(!empty($this->cart_error_data)) {
      $this->warning = '商品库存不足，请检查';
      return false;  
    }
    return true;
  }
  /**
   * 删除购物车接口
   * @author jonathan lee
   */
  public function del() {
    //获取需要删除的商品ID
    $products = input('post.');
    if(empty($products)||!isset($products['item_id'])) {
      $this->error('请选择需要删除的商品');
    }
    
    $cart = model('Cart');
    $cart->removeItemByIds($products['item_id']);
    
    $this->success('删除成功');
  }
}