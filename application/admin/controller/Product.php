<?php


namespace app\admin\controller;
use app\common\controller\Admin;
use app\admin\controller\Cart;
// use app\common\Model\Cart as MCart;

class Product extends Admin {

  /**
   * 商品列表列表
   * @author jonathan lee
   */
  public function index() {
    $map = [];
    //根据get获取的参数拼装map参数
    $filters = input('get.');
    $selected_filters = [];
    //品牌、分类、类型、季节
    $in_keys = ['brand'=>'brand_id', 'cat'=>'category_id', 'pcat'=>'parent_category_id', 'gt'=>'goods_type_id', 'ss'=>'season_id'];
    foreach ($in_keys as $rk => $mk) {
      if(!empty($filters[$rk])) {
        $map[$mk] = ['in', explode(',',$filters[$rk])];
        $selected_filters[$mk] = $filters[$rk];
      }  
    }
    //款号
    if(!empty($filters['code'])) {
      $map['code'] = ['like', '%'.$filters['code'].'%'];
      $selected_filters['code'] = $filters['code'];
    }  
    //价格范围
    if(!empty($filters['start_price'])) {
      $map['price'] = ['>=', (float)$filters['start_price']];
      $selected_filters['start_price'] = $filters['start_price'];
    } 
    if(!empty($filters['end_price'])) {
      $map['price'] = ['<=', (float)$filters['end_price']];
      $selected_filters['end_price'] = $filters['end_price'];
    }
    if(!empty($filters['end_price'])&&!empty($filters['start_price'])) {
      $map['price'] = ['between', [(float)$filters['start_price'], (float)$filters['end_price']]];
    }
    //折扣
    if(!empty($filters['dsct'])) {
      $map['discount'] = [];
      $dscts = explode(',',$filters['dsct']);
      $selected_filters['discount'] = $filters['dsct'];
      foreach ($dscts as $dsct) {
        $pos = strpos($dsct, '_');
        if($pos!==false) {
          list($down_dsct, $up_dsct) = explode("_", $dsct, 2);
          $map['discount'][] = ['between', [(float)$down_dsct, (float)$up_dsct]];
        }
      }
      if(!empty($map['discount'])) {
        $map['discount'][] = 'OR';
      }
    }
    //排序
    list($sort_key, $order_key) = ['pproduct_id', 'desc'];
    $valid_sort_keys = ['pproduct_id', 'price', 'date_added'];
    $valid_order_keys = ['desc', 'asc'];
    if(!empty($filters['sort'])) {
      $selected_filters['sort'] = $filters['sort'];
      $pos = strpos($filters['sort'], '-');
      if($pos!==false) {
        list($sort_key, $order_key) = explode("-", $filters['sort'], 2);
      }
    }
    if(in_array($sort_key, $valid_sort_keys)&&in_array($order_key, $valid_order_keys)) {        
      $order = $sort_key . " " . $order_key;
    }

    //获取列表数据
    $map['stock'] = ['>', '0'];
    $map['country_id'] = session('cur_country_id'); //使用session记录country_id用于切换
    $pproduct = model('Pproduct');
    $with_query = 'product';
    if(!empty($filters['all'])){
        $selected_filters['all'] = $filters['all'];
    }
    if(!empty($filters['rec'])){
        $with_query = 'product,recommended_pproduct';
        $selected_filters['rec'] = $filters['rec'];
    }
    $list = $pproduct->with($with_query)->where($map)->order($order)->paginate(10,false,['query'=>$filters]);



    // 记录当前列表页的cookie
    Cookie('__forward__', $_SERVER['REQUEST_URI']);
    $data = array(
      'list' => $list,
      'page' => $list->render(),
    );
       $data['brands'] = $pproduct->getBrandDict();
    if(!empty($filters['hot'])){
        $data['brands'] = $pproduct->getHotBrandDict();
        $selected_filters['hot'] = $filters['hot'];
    }

    $data['categories'] = $pproduct->getCategoryDict();
    $data['parent_categories'] = $pproduct->getParentCategoryDict();
    $data['seasons'] = $pproduct->getSeasonDict();
    $data['goods_types'] = $pproduct->getGoodsTypeDict();
    $data['brands_idx'] = $pproduct->getBrandIdxDict();
    //Liqn hardcode discount ranges
    $data['discounts'] = ['0_3'=>'3折以下','3_5'=>'3-5折','5_7'=>'5-7折', '7_10'=>'7折以上'];

    $data['cart_input_name'] = Cart::$cart_input_name;
    $cart_model = model('Cart');
    $data['items_count'] = $cart_model->getItemsCount();
    $data['items_total'] = $cart_model->getTotal();

    //购物车已经加入的商品表
    $data['cart_items'] = $cart_model->getItems();

    //当前被设置的过滤项目
    $data['selected_filters'] = $selected_filters;

    $this->assign($data);
    $this->setMeta('商品列表');
    return $this->fetch();

  }
  /**
   * 商品详情，用于快速加入购物车
   * @author jonathan lee
   */
  public function getDetail() {
    $post = input('post.');
    $pproduct_id = $post['pproduct_id'];
    $pproduct = model('Pproduct');
    $cur_pproduct = $pproduct->get($pproduct_id,['pproduct_attr','pproduct_image']);

    //获取 规格 数量
    $map['stock'] = ['>', '0'];
    $pproduct_size_num = $pproduct->with('product')->where($map)->find($pproduct_id);
    //购物车 的数量
    $cart_model =model('Cart');
    $cart_items = $cart_model->getItemsByPproductId($pproduct_id);
    
    $pproduct_info = $cur_pproduct->getData();
    $attrs = ['brand','category','season','goods_type'];
    foreach ($attrs as $attr) {
      $pproduct_info[$attr] = $cur_pproduct[$attr];  
    }
    $pproduct_info['product'] = $pproduct_size_num['product'];
    $pproduct_info['cart_items'] = $cart_items;
    $this->success('', '', $pproduct_info);
  }

  public function test() {
    $pp_id = '58037';
    $pp = db('pproduct');
    $v_p_id = $pp->where(['pproduct_id'=>$pp_id])->value('vendor_product_id');
    var_dump($v_p_id);
    $ap = db('atelier_product');
    $gds = $ap->where(['id'=>$v_p_id])->find();
    $list_data = unserialize($gds['list_data']);
    var_dump($list_data);

    $brand = db('vendor_brand');
    $brd_info = $brand->where(['vendor_brand_id'=>$list_data['BrandID']])->find();
    var_dump($brd_info);
    $brand = db('vendor_category');
    $brd_info = $brand->where(['vendor_category_id'=>$list_data['CategoryID']])->find();
    var_dump($brd_info);
    $brand = db('vendor_goods_type');
    $brd_info = $brand->where(['vendor_goods_type_id'=>$list_data['TypeID']])->find();
    var_dump($brd_info);
    die();

    $detail_data = unserialize($gds['detail_data']);
    $good_data = array_merge($list_data, $detail_data);
  }
}