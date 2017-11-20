<?php


namespace app\common\model;

use Exception;
use think\Log;
use think\Db;
/**
* 供应商基类
*/
class Atelier extends Vendor{
  protected static $atelier_account = 'LEOCINA';
  protected static $atelier_pwd = 'n8rB4CuBPSS7';
  protected static $base_api_url = 'http://studio69.atelier98.net/api_studio69/api_studio69.asmx/';
  protected static $invalid_seasons = ['HD','P16','SO'];

  protected static $pproduct_model = null;
  protected static $pproduct_attr_model = null;
  protected static $pproduct_image_model = null;
  protected static $product_model = null;
  protected static $missing_attr_pproduct_model = null;

  protected static $brand_dict = [];
  protected static $goods_type_dict = [];
  protected static $category_dict = [];
  protected static $parent_category_dict = [];
  protected static $season_dict = [];
  
  protected static function init(){
    // Log::init(['type'=>'File','path'=>APP_PATH.'logs/atelier_','level'=>['error']]);
    self::$pproduct_model = db('pproduct');
    self::$pproduct_attr_model = db('pproduct_attr');
    self::$pproduct_image_model = db('pproduct_image');
    self::$product_model = db('product');
    self::$missing_attr_pproduct_model = db('missing_attr_pproduct');
    //获取Atelier对应的vendor_brand, vendor_goods_type, vendor_season, vendor_category字典
    $expire_time = 3600;
    $brand_dict = cache('atelier_brand_dict');
    if(!$brand_dict) {
      $vendor_brand = db('vendor_brand');
      $brand_dict = $vendor_brand->where(['vendor_id'=>self::$vendor_id])->column('vendor_brand_id,brand_id');
      cache('atelier_brand_dict',$brand_dict, $expire_time);
    }
    self::$brand_dict = $brand_dict;

    $goods_type_dict = cache('atelier_goods_type_dict');
    if(!$goods_type_dict) {
      $vendor_goods_type = db('vendor_goods_type');
      $goods_type_dict = $vendor_goods_type->where(['vendor_id'=>self::$vendor_id])->column('vendor_goods_type_id,goods_type_id');
      cache('atelier_goods_type_dict',$goods_type_dict, $expire_time);
    }
    self::$goods_type_dict = $goods_type_dict;

    $category_dict = cache('atelier_category_dict');
    if(!$category_dict) {
      $vendor_category = db('vendor_category');
      $category_dict = $vendor_category->where(['vendor_id'=>self::$vendor_id])->column('vendor_category_id,category_id');
      cache('atelier_category_dict',$category_dict, $expire_time);
    }
    self::$category_dict = $category_dict;

    $parent_category_dict = cache('atelier_parent_category_dict');
    if(!$parent_category_dict) {
      $vendor_parent_category = db('vendor_parent_category');
      $parent_category_dict = $vendor_parent_category->where(['vendor_id'=>self::$vendor_id])->column('vendor_parent_category_id,parent_category_id');
      cache('atelier_parent_category_dict',$parent_category_dict, $expire_time);
    }
    self::$parent_category_dict = $parent_category_dict;

    $season_dict = cache('atelier_season_dict');
    if(!$season_dict) {
      $vendor_season = db('vendor_season');
      $season_dict = $vendor_season->where(['vendor_id'=>self::$vendor_id])->column('name,season_id');
      cache('atelier_season_dict',$season_dict, $expire_time);
    }
    self::$season_dict = $season_dict;

    //hardcode 时区为UTC+1
    self::$time_zone = 'Europe/Rome';
  }

  public function callApi($res, $params=array()) {
    $curl = curl_init();
    $params_str = http_build_query($params,null,'&',PHP_QUERY_RFC3986);

    curl_setopt_array($curl, array(
      CURLOPT_URL => self::$base_api_url . $res,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 3000,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $params_str,
      CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/x-www-form-urlencoded"
      ),
    ));
    $credentials = self::$atelier_account.":".self::$atelier_pwd;
    curl_setopt($curl, CURLOPT_USERPWD, $credentials);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    // var_dump($response);

    if ($err) {
      Log::record("cURL Error #:" . $err, 'error');
    } else {
      try {
        //解码xml
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        return $array;
      } catch (Exception $e) {
        Log::record('Call '.$res.' failed. Exception is: '.$e->getMessage(), 'error');
      }
    }
    return false;
  }

  /*
  * $size=false 时返回spu
  */
  protected function getSku($code, $vid=false, $size=false) {
    $spu = $this->getData('prefix') . str_replace([' ','/','&'], '_', $code);
    if($vid) {
      $spu .= '-'.trim($vid);
    }
    if($size) {
      $spu .= '-'.trim($size);
    }
    return $spu;
  }

  public function addProduct($goods_data) {
    // var_dump($goods_data['GoodsName']);

    $data['vendor_id'] = self::$vendor_id;
    $data['country_id'] = $this->getData('country_id');
    $data['vendor_product_id'] = $goods_data['ID'];
    $missing_attr_pproducts = [];

    if(array_key_exists($goods_data['BrandID'], self::$brand_dict)) {
      $data['brand_id'] = self::$brand_dict[$goods_data['BrandID']];
    } else {
      $data['brand_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id,'type'=>'brand','key_id'=>$goods_data['BrandID'],'date_added'=>date('Y-m-d H:i:s')];
    }
    if(array_key_exists($goods_data['TypeID'], self::$goods_type_dict)) {
      $data['goods_type_id'] = self::$goods_type_dict[$goods_data['TypeID']];
    } else {
      $data['goods_type_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id,'type'=>'good_types','key_id'=>$goods_data['TypeID'],'date_added'=>date('Y-m-d H:i:s')];
    }
    if(array_key_exists($goods_data['CategoryID'], self::$category_dict)) {
      $data['category_id'] = self::$category_dict[$goods_data['CategoryID']];
    } else {
      $data['category_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id,'type'=>'category','key_id'=>$goods_data['CategoryID'],'date_added'=>date('Y-m-d H:i:s')];
    }
    if(array_key_exists($goods_data['ParentCategoryID'], self::$parent_category_dict)) {
      $data['parent_category_id'] = self::$parent_category_dict[$goods_data['ParentCategoryID']];
    } else {
      $data['parent_category_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id,'type'=>'parent_category','key_id'=>$goods_data['ParentCategoryID'],'date_added'=>date('Y-m-d H:i:s')];
    }
    if(array_key_exists($goods_data['Season'], self::$season_dict)) {
      $data['season_id'] = self::$season_dict[$goods_data['Season']];
    } else {
      $data['season_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id,'type'=>'season','key_id'=>$goods_data['Season'],'date_added'=>date('Y-m-d H:i:s')];
    }
    $data['vendor_code'] = $this->getData('vendor_code');
    $data['spu'] = $this->getSku($goods_data['Code'], $goods_data['ID']);
    $data['code'] = $goods_data['Code'];
    $data['name'] = $goods_data['GoodsName'];
    $data['chs_name'] = $goods_data['GoodsName'];

    //获取Pictures内第一个Picture
    if(isset($goods_data['Pictures'])&&isset($goods_data['Pictures']['Picture'])) {
      if(array_key_exists('PictureUrl',$goods_data['Pictures']['Picture'])) {
        $pics = array($goods_data['Pictures']['Picture']);
      } else {
        $pics = $goods_data['Pictures']['Picture'];
      }
      $pic = reset($pics);
      $data['image'] = $pic['PictureThumbUrl'];  
    } else {
      $data['image'] = '';
    }
    $data['currency_id'] = $this->getData('currency_id');
    //计算IS69的折扣，暂时先按照固定值写，后面需要增加批量更新商品折扣功能
    $data['discount'] = '10';

    $data['price'] = $goods_data['Price'];

    //Cost未来也会根据折扣规则重新计算
    $data['cost'] = $goods_data['Price'];
    $data['msrp'] = $goods_data['Price'];
    //根据所有子商品的stock计算出母商品的stock
    if(isset($goods_data['Stock'])&&isset($goods_data['Stock']['Item'])) {
      if(array_key_exists('Qty',$goods_data['Stock']['Item'])) {
        $items = array($goods_data['Stock']['Item']);
      } else {
        $items = $goods_data['Stock']['Item'];
      }
      $total_stk = 0;
      foreach ($items as $stk) {
        $total_stk += $stk['Qty'];
      }
      $data['stock'] = $total_stk;
    } else {
      $data['stock'] = 0;  
    }

    $data['status'] = 1;
    $data['sort_order'] = 10;
    
    //Atelier时区为基于UTC +1
    $data['date_added'] = date('Y-m-d H:i:s',strtotime($goods_data['CreatedTime']));
    $data['date_modified'] = date('Y-m-d H:i:s',strtotime($goods_data['ModifyTime']));
    $pproduct_id = self::$pproduct_model->insert($data, true, true);
    //pproduct_attr，目前全部直接使用Atelier69接口所返回原始值，后面可以使用多语言字段替换
    $data = [];
    $data['pproduct_id'] = $pproduct_id;
    $data['super_color'] = $goods_data['SuperColor'];
    $data['color'] = $goods_data['Color'];
    $data['fabric'] = $goods_data['Fabric'];
    $data['composition'] = $goods_data['Composition'];
    $data['size_and_fit'] = is_array($goods_data['SizeAndFit'])?'':$goods_data['SizeAndFit'];
    $data['made_in'] = $goods_data['MadeIn'];
    self::$pproduct_attr_model->insert($data, true);
    //pproduct_image
    if(isset($goods_data['Pictures'])&&isset($goods_data['Pictures']['Picture'])) {
      if(array_key_exists('PictureUrl',$goods_data['Pictures']['Picture'])) {
        $pics = array($goods_data['Pictures']['Picture']);
      } else {
        $pics = $goods_data['Pictures']['Picture'];
      }
      $all_data = [];
      foreach ($pics as $pic) {
        $data = [];
        $data['pproduct_id'] = $pproduct_id;
        $data['thumb_image'] = $pic['PictureThumbUrl'];
        $data['image'] = $pic['PictureUrl'];
        $data['sort_order'] = $pic['No'];
        $all_data[] = $data;
      }
      self::$pproduct_image_model->insertAll($all_data);
    }
    //product
    if(isset($goods_data['Stock'])&&isset($goods_data['Stock']['Item'])) {
      if(array_key_exists('Qty',$goods_data['Stock']['Item'])) {
        $items = array($goods_data['Stock']['Item']);
      } else {
        $items = $goods_data['Stock']['Item'];
      }
      $all_data = [];
      foreach ($items as $stk) {
        $data = [];
        $data['pproduct_id'] = $pproduct_id;
        //暂时给没有Size的商品配置默认值为TU
        $stk['Size'] = empty($stk['Size'])||is_array($stk['Size'])?'TU':$stk['Size'];
        $data['sku'] = $this->getSku($goods_data['Code'], $goods_data['ID'], $stk['Size']);
        $data['size'] = $stk['Size'];
        $data['stock'] = $stk['Qty'];
        $all_data[] = $data;
      }
      self::$product_model->insertAll($all_data);
    }
    //插入缺失属性值的产品表，以便后面补全属性
    if(!empty($missing_attr_pproducts)) {
      foreach ($missing_attr_pproducts as $key => $value) {
        $missing_attr_pproducts[$key]['pproduct_id'] = $pproduct_id;
      }
      // self::$missing_attr_pproduct_model->insertAll($missing_attr_pproducts);
    }
    return [true, $missing_attr_pproducts];
  }

  /**
   * 初始同步vendor_brand表
   */
  public function initBrand(){
    $res = 'DictBrand';
    $db_name = 'vendor_brand';
    $main_key = 'Brand';

    $api_ret = $this->callApi($res);
    $sync_model = db($db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $sync_model->execute('truncate ' . $db_name);
      foreach ($api_ret[$main_key] as $ar) {
        $tmp = array('vendor_id'=>self::$vendor_id, 'brand_id'=>'0', 'vendor_brand_id'=>$ar['ID'], 'name'=>$ar['Name']);
        $sync_model->insert($tmp);
      }
    }
  }

  public function initGoodsType(){
    $res = 'GetGoodsType';    
    $db_name = 'vendor_goods_type';
    $main_key = 'Type';

    $api_ret = $this->callApi($res);
    $sync_model = db($db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $sync_model->execute('truncate ' . $db_name);
      foreach ($api_ret[$main_key] as $ar) {
        $tmp = array('vendor_id'=>self::$vendor_id, 'goods_type_id'=>'0', 'vendor_goods_type_id'=>$ar['ID'], 'name'=>$ar['Name']);
        $sync_model->insert($tmp);
      }
    }
  }

  public function initSeason(){
    $res = 'DictSeason';
    $db_name = 'vendor_season';
    $main_key = 'Season';

    $invalid_seasons = self::$invalid_seasons;

    $api_ret = $this->callApi($res);
    
    $sync_model = db($db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $sync_model->execute('truncate ' . $db_name);
      foreach ($api_ret[$main_key] as $key=>$name) {
        if(in_array($name, $invalid_seasons)) {
          continue;
        }
        $tmp = array('vendor_id'=>self::$vendor_id, 'season_id'=>'0', 'vendor_season_id'=>($key+1), 'name'=>$name);
        $sync_model->insert($tmp);
      }
    }
  }

  public function initCategory(){
    $res = 'GetGoodsCategory';
    $db_name = 'vendor_category';
    $main_key = 'Category';

    $api_ret = $this->callApi($res);
    //因为本接口返回category_id存在重复，先做一遍去重
    $api_ret_unique = array();
    foreach ($api_ret[$main_key] as $ar) {
      if(!isset($api_ret_unique[$ar['ID']])) {
        $api_ret_unique[$ar['ID']] = $ar;
      }
    }
    $api_ret = $api_ret_unique;
    $sync_model = db($db_name);
    if($api_ret) {
      $sync_model->execute('truncate ' . $db_name);
      foreach ($api_ret as $key=>$ar) {
        $tmp = array('vendor_id'=>self::$vendor_id, 'category_id'=>'0', 'vendor_category_id'=>$key, 'name'=>$ar['Name']);
        $sync_model->insert($tmp);
      }
    }

    //初始化vendor_parent_category
    $db_name = 'vendor_parent_category';
    $api_ret_unique = array();
    foreach ($api_ret as $ar) {
      if(!isset($api_ret_unique[$ar['ParentID']])) {
        $api_ret_unique[$ar['ParentID']] = $ar;
      }
    }
    $api_ret = $api_ret_unique;
    $sync_model = db($db_name);
    if($api_ret) {
      $sync_model->execute('truncate ' . $db_name);
      foreach ($api_ret as $key=>$ar) {
        $tmp = array('vendor_id'=>self::$vendor_id, 'parent_category_id'=>'0', 'vendor_parent_category_id'=>$key, 'name'=>$ar['ParentName']);
        $sync_model->insert($tmp);
      }
    }
  }

  /**
   * 基于atelier98接口初始化brand、goods_type、season、category四表
   */
  public function initFourBaseTableByAtelier() {
    $this->initBrand();
    $this->initBrandByAtelier();
    $this->initGoodsType();
    $this->initGoodsTypeByAtelier();
    $this->initSeason();
    $this->initSeasonByAtelier();
    $this->initCategory();
    $this->initCategoryByAtelier();
  }

  public function initBrandByAtelier() {
    $res = 'DictBrand';
    $db_name = 'brand';
    $main_key = 'Brand';
    $api_ret = $this->callApi($res);
    $sync_model = db($db_name);
    $sync_model->execute('truncate ' . $db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $vendor_brand_dict = array();
      $sort_order = 5;
      foreach ($api_ret[$main_key] as $ar) {
        $tmp = array('name'=>$ar['Name'],'chs_name'=>$ar['Name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
        $brand_id = $sync_model->insertGetId($tmp);
        $sort_order += 5;
        $vendor_brand_dict[$brand_id] = $ar['ID'];
      }
      $sync_model = db('vendor_brand');
      foreach ($vendor_brand_dict as $brand_id => $vendor_brand_id) {
        $map = [];
        $map['vendor_id'] = self::$vendor_id;
        $map['vendor_brand_id'] = $vendor_brand_id;
        $sync_model->where($map)->update(['brand_id'=>$brand_id]);
      }
    }
  }
  public function initGoodsTypeByAtelier() {
    $res = 'GetGoodsType';    
    $db_name = 'goods_type';
    $main_key = 'Type';
    $api_ret = $this->callApi($res);
    $sync_model = db($db_name);
    $sync_model->execute('truncate ' . $db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $vendor_goods_type_dict = array();
      $sort_order = 5;
      foreach ($api_ret[$main_key] as $ar) {
        $tmp = array('name'=>$ar['Name'],'chs_name'=>$ar['Name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
        $goods_type_id = $sync_model->insertGetId($tmp);
        $sort_order += 5;
        $vendor_goods_type_dict[$goods_type_id] = $ar['ID'];
      }
      $sync_model = db('vendor_goods_type');
      foreach ($vendor_goods_type_dict as $goods_type_id => $vendor_goods_type_id) {
        $map = [];
        $map['vendor_id'] = self::$vendor_id;
        $map['vendor_goods_type_id'] = $vendor_goods_type_id;
        $sync_model->where($map)->update(['goods_type_id'=>$goods_type_id]);
      }
    }
  }
  public function initSeasonByAtelier() {
    $res = 'DictSeason';    
    $db_name = 'season';
    $main_key = 'Season';
    $invalid_seasons = self::$invalid_seasons;
    $api_ret = $this->callApi($res);
    $sync_model = db($db_name);
    $sync_model->execute('truncate ' . $db_name);
    if($api_ret && isset($api_ret[$main_key])) {
      $vendor_season_dict = array();
      $sort_order = 5;
      foreach ($api_ret[$main_key] as $key=>$name) {
        if(in_array($name, $invalid_seasons)) {
          continue;
        }
        $tmp = array('name'=>$name,'chs_name'=>$name, 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
        $season_id = $sync_model->insertGetId($tmp);
        $sort_order += 5;
        $vendor_season_dict[$season_id] = $name;
      }
      $sync_model = db('vendor_season');
      foreach ($vendor_season_dict as $season_id => $name) {
        $map = [];
        $map['vendor_id'] = self::$vendor_id;
        $map['name'] = $name;
        $sync_model->where($map)->update(['season_id'=>$season_id]);
      }
    }
  }
  public function initCategoryByAtelier() {
    $res = 'GetGoodsCategory';
    $main_key = 'Category';
    $api_ret = $this->callApi($res);
    //因为本接口返回category_id存在重复，先做一遍去重
    $api_ret_unique = array();
    foreach ($api_ret[$main_key] as $ar) {
      if(!isset($api_ret_unique[$ar['ID']])) {
        $api_ret_unique[$ar['ID']] = $ar;
      }
    }
    $api_ret = $api_ret_unique;
    
    if($api_ret) {
      //先建立parent_category和vendor_parent_category_dict
      $db_name = 'parent_category';
      $sync_model = db($db_name);
      $sync_model->execute('truncate ' . $db_name);
      $vendor_parent_category_dict = array();
      $parent_category_dict = array();
      $api_ret_parent = [];
      foreach ($api_ret as $ar) {
        if(!isset($api_ret_parent[$ar['ParentID']])) {
          $api_ret_parent[$ar['ParentID']] = $ar;
        }
      }
      $sort_order = 5;
      foreach ($api_ret_parent as $ar) {
        $tmp = array('name'=>$ar['ParentName'], 'chs_name'=>$ar['ParentName'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
        $parent_category_id = $sync_model->insertGetId($tmp);
        $sort_order += 5;
        $vendor_parent_category_dict[$parent_category_id] = $ar['ParentID'];
        $parent_category_dict[$ar['ParentID']] = $parent_category_id;
      }
      $sync_model = db('vendor_parent_category');
      foreach ($vendor_parent_category_dict as $parent_category_id => $vendor_parent_category_id) {
        $map = [];
        $map['vendor_id'] = self::$vendor_id;
        $map['vendor_parent_category_id'] = $vendor_parent_category_id;
        $sync_model->where($map)->update(['parent_category_id'=>$parent_category_id]);
      }
      //然后建立category
      $db_name = 'category';
      $main_key = 'Category';
      $sync_model = db($db_name);
      $sync_model->execute('truncate ' . $db_name);
      $vendor_category_dict = array();
      $sort_order = 5;
      foreach ($api_ret as $ar) {
        $tmp = array('name'=>$ar['Name'], 'chs_name'=>$ar['Name'],'parent_id'=>$parent_category_dict[$ar['ParentID']], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
        $category_id = $sync_model->insertGetId($tmp);
        $sort_order += 5;
        $vendor_category_dict[$category_id] = $ar['ID'];
      }
      $sync_model = db('vendor_category');
      foreach ($vendor_category_dict as $category_id => $vendor_category_id) {
        $map = [];
        $map['vendor_id'] = self::$vendor_id;
        $map['vendor_category_id'] = $vendor_category_id;
        $sync_model->where($map)->update(['category_id'=>$category_id]);
      }
    }
  }

  public function getMissingAttrs($existing_products, $new_products) {
    $missing_attrs = [];

    $brand_ids = [];
    $goods_type_ids = [];
    $category_ids = [];
    $parent_category_ids = [];
    $season_ids = [];

    foreach ($existing_products as $pp) {
      $brand_ids[] = $pp['BrandID'];
      $goods_type_ids[] = $pp['TypeID'];
      $category_ids[] = $pp['CategoryID'];
      $parent_category_ids[] = $pp['ParentCategoryID'];
      $season_ids[] = $pp['Season'];
    }
    $brand_ids = array_unique($brand_ids);
    $goods_type_ids = array_unique($goods_type_ids);
    $category_ids = array_unique($category_ids);
    $parent_category_ids = array_unique($parent_category_ids);
    $season_ids = array_unique($season_ids);
    foreach ($new_products as $pp) {
      $brand_ids[] = $pp['BrandID'];
      $goods_type_ids[] = $pp['TypeID'];
      $category_ids[] = $pp['CategoryID'];
      $parent_category_ids[] = $pp['ParentCategoryID'];
      $season_ids[] = $pp['Season'];
    }
    $brand_ids = array_unique($brand_ids);
    $goods_type_ids = array_unique($goods_type_ids);
    $category_ids = array_unique($category_ids);
    $parent_category_ids = array_unique($parent_category_ids);
    $season_ids = array_unique($season_ids);

    $orig_brand_ids = array_keys(self::$brand_dict);
    $orig_goods_type_ids = array_keys(self::$goods_type_dict);
    $orig_category_ids = array_keys(self::$category_dict);
    $orig_parent_category_ids = array_keys(self::$parent_category_dict);
    $orig_season_ids = array_keys(self::$season_dict);

    $new_brand_ids = array_diff($brand_ids, $orig_brand_ids);
    $new_goods_type_ids = array_diff($goods_type_ids, $orig_goods_type_ids);
    $new_category_ids = array_diff($category_ids, $orig_category_ids);
    $new_parent_category_ids = array_diff($parent_category_ids, $orig_parent_category_ids);
    $new_season_ids = array_diff($season_ids, $orig_season_ids);

    foreach ($new_brand_ids as $brand_id) {
      $missing_attrs[] = ['vendor_id'=>self::$vendor_id, 'type'=>'brand', 'name'=>$brand_id, 'key_id'=>$brand_id, 'date_added'=>date('Y-m-d H:i:s')];
    }
    foreach ($new_goods_type_ids as $goods_type_id) {
      $missing_attrs[] = ['vendor_id'=>self::$vendor_id, 'type'=>'goods_type', 'name'=>$goods_type_id, 'key_id'=>$goods_type_id, 'date_added'=>date('Y-m-d H:i:s')];
    }
    foreach ($new_category_ids as $category_id) {
      $missing_attrs[] = ['vendor_id'=>self::$vendor_id, 'type'=>'category', 'name'=>$category_id, 'key_id'=>$category_id, 'date_added'=>date('Y-m-d H:i:s')];
    }
    foreach ($new_parent_category_ids as $parent_category_id) {
      $missing_attrs[] = ['vendor_id'=>self::$vendor_id, 'type'=>'parent_category', 'name'=>$parent_category_id, 'key_id'=>$parent_category_id, 'date_added'=>date('Y-m-d H:i:s')];
    }
    foreach ($new_season_ids as $season_id) {
      $missing_attrs[] = ['vendor_id'=>self::$vendor_id, 'type'=>'season', 'name'=>$season_id, 'key_id'=>$season_id, 'date_added'=>date('Y-m-d H:i:s')];
    }

    return $missing_attrs;
  }

  public function getNeedSyncProducts($last_synced_time) {
    $res = 'GetGoodsListTS';
    $main_key = 'Good';
    $params = ['timestamp'=>$last_synced_time];

    // cache(null);
    // $api_ret = cache('tmp_api_ret_GetGoodsListTS');
    // if(!$api_ret) {
    //   $api_ret = $this->callApi($res,$params);  
    //   cache('tmp_api_ret_GetGoodsListTS',$api_ret);
    // }
    $api_ret = $this->callApi($res,$params);

    if(!array_key_exists($main_key, $api_ret)) {
      return null;
    }
    $goods_list = $api_ret[$main_key];
    
    $vendor_products_ids = array_column($goods_list, 'ID');
    //搜索属于当前系统的商品ID
    $existing_product_ids = $this->getExistingProductIds($vendor_products_ids);
    //array diff出需要新增的商品ID
    $new_product_ids = array_diff($vendor_products_ids, $existing_product_ids);
    //分解$goods_list为$existing_products和$new_products
    $existing_products = [];
    $new_products = [];
    foreach ($goods_list as $gd) {
      $gd['vendor_product_id'] = $gd['ID'];
      if(in_array($gd['ID'], $new_product_ids)) {
        $new_products[] = $gd;
      } else {
        $existing_products[] = $gd;
      }
    }

    return [$existing_products, $new_products];
  }

  public function getStockData($vendor_product_id) {
    $res = 'GetGoodsStockByGoodsID';
    $main_key = 'Good';
    $params = ['goodsID'=>$vendor_product_id];
    $api_ret = $this->callApi($res,$params);  
    // var_dump($vendor_product_id);
    // var_dump(time());
    if(!is_array($api_ret)||!array_key_exists($main_key, $api_ret)) {
      return [];
    }
    return $api_ret[$main_key];
  }

  public function getDetailData($vendor_product_id) {
    $res = 'GetGoodsDetailByGoodsID';
    $main_key = 'Good';
    $params = ['GoodsID'=>$vendor_product_id];
    $api_ret = $this->callApi($res,$params);  
    // var_dump($vendor_product_id);
    // var_dump(time());
    if(!is_array($api_ret)||!array_key_exists($main_key, $api_ret)) {
      return [];
    }
    return $api_ret[$main_key]; 
  }

  public function updateProduct($good_data) {
    // var_dump('updateing '.$good_data['GoodsName']);

    $missing_attr_pproducts = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['vendor_product_id'] = $good_data['vendor_product_id'];
    if(array_key_exists($good_data['Season'], self::$season_dict)) {
      $data['season_id'] = self::$season_dict[$good_data['Season']];  
    } else {
      $data['season_id'] = 0;
      $missing_attr_pproducts[] = ['vendor_id'=>self::$vendor_id, 'type'=>'season', 'key_id'=>$good_data['Season'], 'date_added'=>date('Y-m-d H:i:s')];
    }
    //待完善，此处还需要根据折扣规则来做计算
    $data['msrp'] = $good_data['Price'];
    $data['cost'] = $good_data['Price'];
    $data['discount'] = 10; //需要换成getDiscount函数
    $data['price'] = $data['discount']*$good_data['Price']/10;
    //更新价格、季节
    $need_update_pp = false;
    $updated = false;
    $orig_data = self::$pproduct_model->where($map)->find();
    // var_dump($orig_data);
    foreach ($data as $key => $value) {
      // if($orig_data[$key]!=$value) {
      if(abs($orig_data[$key]-$value)>0.000001) {
        $need_update_pp = true;
      }
    }
    
    //更新库存product和pproduct的stock
    $pproduct_id = $orig_data['pproduct_id'];
    $map = [];
    $map['pproduct_id'] = $pproduct_id;
    $orig_size_stock = self::$product_model->where($map)->column('product_id, stock', 'size');
    $size_stock = [];
    $new_total_stock = 0;
    if(isset($good_data['Stocks'])&&isset($good_data['Stocks']['Stock'])) {
      if(array_key_exists('Qty',$good_data['Stocks']['Stock'])) {
        $items = array($good_data['Stocks']['Stock']);
      } else {
        $items = $good_data['Stocks']['Stock'];
      }
      foreach ($items as $stk) {
        $stk['Size'] = empty($stk['Size'])||is_array($stk['Size'])?'TU':$stk['Size'];
        $size_stock[$stk['Size']] = $stk['Qty'];
        $new_total_stock += $stk['Qty'];
      }
    }

    if($new_total_stock != $orig_data['stock']) {
      $data['stock'] = $new_total_stock;
    }

    if($need_update_pp) {
      // var_dump('updating '.$orig_data['name']);
      // var_dump($data);
      // var_dump($orig_data);
      self::$pproduct_model->where($map)->update($data);
      $updated = true;
    } else if(isset($data['stock'])) {
      // var_dump('change '.$orig_data['name'] . 'stock '.$orig_data['stock'].' to '.$data['stock']);
      self::$pproduct_model->where($map)->update(['stock'=>$data['stock']]);
      $updated = true;
    }

    $to_change_stock = [];
    $to_add_stock = [];
    foreach ($size_stock as $size => $qty) {
      if(!array_key_exists($size, $orig_size_stock)) {
        $to_add_stock[] = ['size'=>$size, 'stock'=>$qty];
        continue;
      }
      if($orig_size_stock[$size]['stock'] != $qty) {
        $to_change_stock[] = ['product_id'=>$orig_size_stock[$size]['product_id'], 'stock'=>$qty];
      }
    }

    $to_del_stock = [];
    foreach ($orig_size_stock as $size => $oss) {
      if(!array_key_exists($size, $size_stock)) {
        $to_del_stock[] = ['product_id'=>$oss['product_id']];
      }
    }

    if(!empty($to_change_stock)) {
      foreach ($to_change_stock as $tcs) {
        self::$product_model->where(['product_id'=>$tcs['product_id']])->update(['stock'=>$tcs['stock']]);
      }
    }
    if(!empty($to_add_stock)) {
      foreach ($to_add_stock as $tas) {
        self::$product_model->insert([
          'pproduct_id'=>$pproduct_id,
          'stock'=>$tas['stock'],
          'sku'=>$this->getSku($orig_data['code'], $pproduct_id, $tas['size']),
          'size'=>$tas['size']
          ]);
      }
    }
    if(!empty($to_del_stock)) {
      foreach ($to_del_stock as $tds) {
        self::$product_model->where(['product_id'=>$tds['product_id']])->delete();
      }
    }

    if(!empty($missing_attr_pproducts)) {
      foreach ($missing_attr_pproducts as $key => $value) {
        $missing_attr_pproducts[$key]['pproduct_id'] = $pproduct_id;
      }
    }

    // if(!empty($to_change_stock)||!empty($to_add_stock)||!empty($to_del_stock)) {
    //   var_dump($to_change_stock);
    //   var_dump($to_add_stock);
    //   var_dump($to_del_stock);
    // }
    return [$updated, $missing_attr_pproducts];
  }

  public function updateMissingSeason($missing_attrs_model) {
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'season';
    $need_sync = $missing_attrs_model->where($map)->find();
    if(empty($need_sync)) {
      return;
    }
    //Season
    //读取接口获取最新的name dict
    $cur_attrs = [];
    $res = 'DictSeason';
    $main_key = 'Season';
    $invalid_seasons = self::$invalid_seasons;
    $api_ret = $this->callApi($res);
    if($api_ret && isset($api_ret[$main_key])) {
      foreach ($api_ret[$main_key] as $key=>$name) {
        if(in_array($name, $invalid_seasons)) {
          continue;
        }
        $cur_attrs[$name] = $name;
      }
    } else {
      return;
    }
    // var_dump($cur_attrs);
    //读出现有vendor_attr属性表中已经存在的names
    $db_name = 'vendor_season';
    $sync_model = db($db_name);
    $map = ['vendor_id'=>self::$vendor_id];
    $orig_attrs = $sync_model->where($map)->column('name');
    // var_dump($orig_attrs);
    $need_add_attrs = array_diff($cur_attrs, $orig_attrs);
    // var_dump($need_add_attrs);
    $need_del_attrs = array_diff($orig_attrs, $cur_attrs);
    // var_dump($need_del_attrs);
    //然后和当前数据库vendor_xxx内的属性值做比较，删除不存在的xxx，添加新增的xxx
    $max_key = $sync_model->where($map)->max('vendor_season_id');
    $max_key++;
    foreach ($need_add_attrs as $naa) {
      $tmp = array('vendor_id'=>self::$vendor_id, 'season_id'=>'0', 'vendor_season_id'=>($max_key++), 'name'=>$naa);
        $sync_model->insert($tmp);
    }
    foreach ($need_del_attrs as $nda) {
      $map = [];
      $map = ['vendor_id'=>self::$vendor_id, 'name'=>$nda];
      $sync_model->where($map)->delete();
    }
    //更新missing_attrs表内每个attr的状态
    $new_attrs = $sync_model->where($map)->column('name');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'season';
    $map['name'] = ['in', $new_attrs];
    $missing_attrs_model->where($map)->update(['status'=>'1']);
  }

  public function updateMissingBrand($missing_attrs_model) {
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'brand';
    $need_sync = $missing_attrs_model->where($map)->find();
    if(empty($need_sync)) {
      return;
    }
    //Brand
    //读取接口获取最新的name dict
    $cur_attrs = [];
    $res = 'DictBrand';
    $main_key = 'Brand';
    $api_ret = $this->callApi($res);
    if($api_ret && isset($api_ret[$main_key])) {
      foreach ($api_ret[$main_key] as $ar) {
        $cur_attrs[$ar['ID']] = $ar['Name'];
      }
    } else {
      return;
    }
    // var_dump($cur_attrs);
    //读出现有vendor_attr属性表中已经存在的names
    $db_name = 'vendor_brand';
    $sync_model = db($db_name);
    $map = ['vendor_id'=>self::$vendor_id];
    $orig_attrs = $sync_model->where($map)->column('vendor_brand_id,name');
    // var_dump($orig_attrs);
    $need_add_attrs = array_diff_key($cur_attrs, $orig_attrs);
    // var_dump($need_add_attrs);
    $need_del_attrs = array_diff_key($orig_attrs, $cur_attrs);
    // var_dump($need_del_attrs);
    //然后和当前数据库vendor_xxx内的属性值做比较，删除不存在的xxx，添加新增的xxx
    foreach ($need_add_attrs as $key=>$naa) {
      $tmp = array('vendor_id'=>self::$vendor_id, 'brand_id'=>'0', 'vendor_brand_id'=>$key, 'name'=>$naa);
        $sync_model->insert($tmp);
    }
    foreach ($need_del_attrs as $key=>$nda) {
      $map = [];
      $map = ['vendor_id'=>self::$vendor_id, 'vendor_brand_id'=>$key];
      $sync_model->where($map)->delete();
    }
    //更新missing_attrs表内每个attr的状态
    $new_attrs = $sync_model->where($map)->column('vendor_brand_id');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'brand';
    $map['key_id'] = ['in', $new_attrs];
    $missing_attrs_model->where($map)->update(['status'=>'1']);
  }

  public function updateMissingGoodsType($missing_attrs_model) {
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'goods_type';
    $need_sync = $missing_attrs_model->where($map)->find();
    if(empty($need_sync)) {
      return;
    }
    //GoodsType
    //读取接口获取最新的name dict
    $cur_attrs = [];
    $res = 'GetGoodsType';    
    $main_key = 'Type';
    $api_ret = $this->callApi($res);
    if($api_ret && isset($api_ret[$main_key])) {
      foreach ($api_ret[$main_key] as $ar) {
        $cur_attrs[$ar['ID']] = $ar['Name'];
      }
    } else {
      return;
    }
    // var_dump($cur_attrs);
    //读出现有vendor_attr属性表中已经存在的names
    $db_name = 'vendor_goods_type';
    $sync_model = db($db_name);
    $map = ['vendor_id'=>self::$vendor_id];
    $orig_attrs = $sync_model->where($map)->column('vendor_goods_type_id,name');
    // var_dump($orig_attrs);
    $need_add_attrs = array_diff_key($cur_attrs, $orig_attrs);
    // var_dump($need_add_attrs);
    $need_del_attrs = array_diff_key($orig_attrs, $cur_attrs);
    // var_dump($need_del_attrs);die();
    //然后和当前数据库vendor_xxx内的属性值做比较，删除不存在的xxx，添加新增的xxx
    foreach ($need_add_attrs as $key=>$naa) {
      $tmp = array('vendor_id'=>self::$vendor_id, 'goods_type_id'=>'0', 'vendor_goods_type_id'=>$key, 'name'=>$naa);
        $sync_model->insert($tmp);
    }
    foreach ($need_del_attrs as $key=>$nda) {
      $map = [];
      $map = ['vendor_id'=>self::$vendor_id, 'vendor_goods_type_id'=>$key];
      $sync_model->where($map)->delete();
    }
    //更新missing_attrs表内每个attr的状态
    $new_attrs = $sync_model->where($map)->column('vendor_goods_type_id');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'goods_type';
    $map['key_id'] = ['in', $new_attrs];
    $missing_attrs_model->where($map)->update(['status'=>'1']);
  }

  public function updateMissingCategory($missing_attrs_model) {
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = ['in', ['category', 'parent_category']];
    $need_sync = $missing_attrs_model->where($map)->find();
    if(empty($need_sync)) {
      return;
    }
    //Category
    //读取接口获取最新的name dict
    $cur_attrs = [];
    $res = 'GetGoodsCategory';
    $db_name = 'vendor_category';
    $main_key = 'Category';

    $api_ret = $this->callApi($res);
    //因为本接口返回category_id存在重复，先做一遍去重
    $api_ret_unique = array();
    foreach ($api_ret[$main_key] as $ar) {
      if(!isset($api_ret_unique[$ar['ID']])) {
        $api_ret_unique[$ar['ID']] = $ar;
        $cur_attrs[$ar['ID']] = $ar['Name'];
      }
    }
    $api_ret = $api_ret_unique;

    //临时性为了做分类同步接口返回数据用
    $remote_category_info = $api_ret_unique;

    //初始化vendor_parent_category
    $cur_parent_attrs = [];
    $db_name = 'vendor_parent_category';
    $api_ret_unique = array();
    foreach ($api_ret as $ar) {
      if(!isset($api_ret_unique[$ar['ParentID']])) {
        $api_ret_unique[$ar['ParentID']] = $ar;
        $cur_parent_attrs[$ar['ParentID']] = $ar['ParentName'];
      }
    }

    //获取orig属性值
    $db_name = 'vendor_category';
    $sync_model = db($db_name);
    $map = ['vendor_id'=>self::$vendor_id];
    $orig_attrs = $sync_model->where($map)->column('vendor_category_id,name');

    $db_name = 'vendor_parent_category';
    $sync_parent_model = db($db_name);
    $map = ['vendor_id'=>self::$vendor_id];
    $orig_parent_attrs = $sync_parent_model->where($map)->column('vendor_parent_category_id,name');
    
    $need_add_attrs = array_diff_key($cur_attrs, $orig_attrs);
    // var_dump($need_add_attrs);
    $need_del_attrs = array_diff_key($orig_attrs, $cur_attrs);
    // var_dump($need_del_attrs);die();

    $need_add_parent_attrs = array_diff_key($cur_parent_attrs, $orig_parent_attrs);
    // var_dump($need_add_parent_attrs);
    $need_del_parent_attrs = array_diff_key($orig_parent_attrs, $cur_parent_attrs);
    // var_dump($need_del_parent_attrs);die();

    foreach ($need_add_attrs as $key=>$naa) {
      $tmp = array('vendor_id'=>self::$vendor_id, 'category_id'=>'0', 'vendor_category_id'=>$key, 'name'=>$naa);
        $sync_model->insert($tmp);
    }
    foreach ($need_del_attrs as $key=>$nda) {
      $map = [];
      $map = ['vendor_id'=>self::$vendor_id, 'vendor_category_id'=>$key];
      $sync_model->where($map)->delete();
    }

    foreach ($need_add_parent_attrs as $key=>$naa) {
      $tmp = array('vendor_id'=>self::$vendor_id, 'parent_category_id'=>'0', 'vendor_parent_category_id'=>$key, 'name'=>$naa);
        $sync_parent_model->insert($tmp);
    }
    foreach ($need_del_parent_attrs as $key=>$nda) {
      $map = [];
      $map = ['vendor_id'=>self::$vendor_id, 'vendor_parent_category_id'=>$key];
      $sync_parent_model->where($map)->delete();
    }
    
    //更新missing_attrs表内每个attr的状态
    $new_attrs = $sync_model->where($map)->column('vendor_category_id');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'category';
    $map['key_id'] = ['in', $new_attrs];
    $missing_attrs_model->where($map)->update(['status'=>'1']);

    $new_attrs = $sync_parent_model->where($map)->column('vendor_parent_category_id');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['status'] = 0;
    $map['type'] = 'parent_category';
    $map['key_id'] = ['in', $new_attrs];
    $missing_attrs_model->where($map)->update(['status'=>'1']);

    // return $remote_category_info;
  }

  public function updateMissingVendorAttr() {
    //根据missing_attrs表内存在的本vendor需要更新的属性们
    $missing_attrs_model = db('missing_vendor_attr');
    $this->updateMissingSeason($missing_attrs_model);
    $this->updateMissingBrand($missing_attrs_model);
    $this->updateMissingGoodsType($missing_attrs_model);
    $this->updateMissingCategory($missing_attrs_model);
    
    //下面的属性更新任务暂时自动插入，后期多个供应商时，需要人工插入任务！
    $this->autoUpdateMissingAttr();
  }

  public function autoUpdateMissingAttr() {
    //根据missing attr表内记录，更新attr表中字段，只更新不重建！
    //找到attr表中id为0的记录，然后查看对应的key在主值表中是否存在，如果不存在则插入主值表，同时更新attr表内字段
    //Season
    $attr_model = db('vendor_season');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['season_id'] = 0;
    $need_add_attrs = $attr_model->where($map)->select();
    $main_attr_model = db('season');
    $main_key = 'name';
    $main_attr_idx = 'season_id';
    $sort_order = $main_attr_model->max('sort_order');
    $sort_order += 5;
    $vendor_attr_dict = [];
    foreach ($need_add_attrs as $naa) {
      $tmp = array('name'=>$naa['name'],'chs_name'=>$naa['name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
      $attr_id = $main_attr_model->insertGetId($tmp);
      $sort_order += 5;
      $vendor_attr_dict[$attr_id] = $naa[$main_key];
    }
    foreach ($vendor_attr_dict as $attr_id => $name) {
      $map = [];
      $map['vendor_id'] = self::$vendor_id;
      $map['name'] = $name;
      $attr_model->where($map)->update([$main_attr_idx=>$attr_id]);
    }
    //Brand
    $attr_model = db('vendor_brand');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['brand_id'] = 0;
    $need_add_attrs = $attr_model->where($map)->select();
    $main_attr_model = db('brand');
    $main_key = 'vendor_brand_id';
    $main_attr_idx = 'brand_id';
    $sort_order = $main_attr_model->max('sort_order');
    $sort_order += 5;
    $vendor_attr_dict = [];
    // var_dump($need_add_attrs);die();
    foreach ($need_add_attrs as $naa) {
      $tmp = array('name'=>$naa['name'],'chs_name'=>$naa['name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
      $attr_id = $main_attr_model->insertGetId($tmp);
      $sort_order += 5;
      $vendor_attr_dict[$attr_id] = $naa[$main_key];
    }
    foreach ($vendor_attr_dict as $attr_id => $vendor_vendor_id) {
      $map = [];
      $map['vendor_id'] = self::$vendor_id;
      $map[$main_key] = $vendor_vendor_id;
      $attr_model->where($map)->update([$main_attr_idx=>$attr_id]);
    }
    //GoodsType
    $attr_model = db('vendor_goods_type');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['goods_type_id'] = 0;
    $need_add_attrs = $attr_model->where($map)->select();
    $main_attr_model = db('goods_type');
    $main_key = 'vendor_goods_type_id';
    $main_attr_idx = 'goods_type_id';
    $sort_order = $main_attr_model->max('sort_order');
    $sort_order += 5;
    $vendor_attr_dict = [];
    // var_dump($need_add_attrs);die();
    foreach ($need_add_attrs as $naa) {
      $tmp = array('name'=>$naa['name'],'chs_name'=>$naa['name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
      $attr_id = $main_attr_model->insertGetId($tmp);
      $sort_order += 5;
      $vendor_attr_dict[$attr_id] = $naa[$main_key];
    }
    foreach ($vendor_attr_dict as $attr_id => $vendor_vendor_id) {
      $map = [];
      $map['vendor_id'] = self::$vendor_id;
      $map[$main_key] = $vendor_vendor_id;
      $attr_model->where($map)->update([$main_attr_idx=>$attr_id]);
    }
    //Category & ParentCategory
    $attr_model = db('vendor_parent_category');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['parent_category_id'] = 0;
    $need_add_attrs = $attr_model->where($map)->select();
    $main_attr_model = db('parent_category');
    $main_key = 'vendor_parent_category_id';
    $main_attr_idx = 'parent_category_id';
    $sort_order = $main_attr_model->max('sort_order');
    $sort_order += 5;
    $vendor_attr_dict = [];
    // var_dump($need_add_attrs);die();
    foreach ($need_add_attrs as $naa) {
      $tmp = array('name'=>$naa['name'],'chs_name'=>$naa['name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
      $attr_id = $main_attr_model->insertGetId($tmp);
      $sort_order += 5;
      $vendor_attr_dict[$attr_id] = $naa[$main_key];
    }
    foreach ($vendor_attr_dict as $attr_id => $vendor_vendor_id) {
      $map = [];
      $map['vendor_id'] = self::$vendor_id;
      $map[$main_key] = $vendor_vendor_id;
      $attr_model->where($map)->update([$main_attr_idx=>$attr_id]);
    }
    $attr_model = db('vendor_category');
    $map = [];
    $map['vendor_id'] = self::$vendor_id;
    $map['category_id'] = 0;
    $need_add_attrs = $attr_model->where($map)->select();
    $main_attr_model = db('category');
    $main_key = 'vendor_category_id';
    $main_attr_idx = 'category_id';
    $sort_order = $main_attr_model->max('sort_order');
    $sort_order += 5;
    $vendor_attr_dict = [];
    // var_dump($need_add_attrs);die();
    foreach ($need_add_attrs as $naa) {
      $tmp = array('name'=>$naa['name'],'chs_name'=>$naa['name'], 'status'=>1, 'sort_order'=>$sort_order, 'date_added'=>date('Y-m-d H:i:s'));
      $attr_id = $main_attr_model->insertGetId($tmp);
      $sort_order += 5;
      $vendor_attr_dict[$attr_id] = $naa[$main_key];
    }
    foreach ($vendor_attr_dict as $attr_id => $vendor_vendor_id) {
      $map = [];
      $map['vendor_id'] = self::$vendor_id;
      $map[$main_key] = $vendor_vendor_id;
      $attr_model->where($map)->update([$main_attr_idx=>$attr_id]);
    }

    //更新missing attr product表中缺失商品，此处后期做成独立的队列任务
    $this->updateMissingAttrProduct();
  }

  protected function updateMissingAttrForProduct($nup) {
    switch ($nup['type']) {
      case 'season':
        if(array_key_exists($nup['key_id'], self::$season_dict)) {
          $data['season_id'] = self::$season_dict[$nup['key_id']];
          $map = ['pproduct_id'=>$nup['pproduct_id']];
          self::$pproduct_model->where($map)->update($data);
          return true;
        }
        break;
      case 'brand':
        if(array_key_exists($nup['key_id'], self::$brand_dict)) {
          $data['brand_id'] = self::$brand_dict[$nup['key_id']];
          $map = ['pproduct_id'=>$nup['pproduct_id']];
          self::$pproduct_model->where($map)->update($data);
          return true;
        }
        break;
      case 'goods_type':
        if(array_key_exists($nup['key_id'], self::$goods_type_dict)) {
          $data['goods_type_id'] = self::$goods_type_dict[$nup['key_id']];
          $map = ['pproduct_id'=>$nup['pproduct_id']];
          self::$pproduct_model->where($map)->update($data);
          return true;
        }
        break;
      case 'category':
        if(array_key_exists($nup['key_id'], self::$category_dict)) {
          $data['category_id'] = self::$category_dict[$nup['key_id']];
          $map = ['pproduct_id'=>$nup['pproduct_id']];
          self::$pproduct_model->where($map)->update($data);
          return true;
        }
        break;
      case 'parent_category':
        if(array_key_exists($nup['key_id'], self::$parent_category_dict)) {
          $data['parent_category_id'] = self::$parent_category_dict[$nup['key_id']];
          $map = ['pproduct_id'=>$nup['pproduct_id']];
          self::$pproduct_model->where($map)->update($data);
          return true;
        }
        break;
      default:
        break;
    }
    return false;
  }

}