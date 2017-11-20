<?php


namespace app\common\model;

/**
* 供应商基类
*/
class Vendor extends \think\Model{
  protected static $vendor_id;
  protected static $time_zone = 'Asia/Shanghai';

  public function __construct($data = []){
    if(isset($data['vendor_id'])) {
      self::$vendor_id = $data['vendor_id'];
      $vendor_data = db('vendor')->where(['vendor_id'=>self::$vendor_id])->find();
      $data = $vendor_data;
    }
    parent::__construct($data);
  }

  public function initBrand(){}
  public function initGoodsType(){}
  public function initSeason(){}
  public function initCategory(){}
  protected function getNeedSyncProducts($last_synced_time) {}
  protected function getStockData($vendor_product_id) {}
  protected function getDetailData($vendor_product_id) {}
  protected function updateProduct($good_data) {}
  protected function addProduct($good_data) {}
  protected function getMissingAttrs($existing_products, $new_products) {}
  protected function updateMissingVendorAttr() {}
  protected function updateMissingAttrForProduct($nup) {}

  public function convertDateFromTimezone($date,$timezone_to,$format) {
     $date = new \DateTime($date);
     $date->setTimezone( new \DateTimeZone($timezone_to) );
     return $date->format($format);
  }

  protected function getLastSyncedTime() {
    $map['vendor_id'] = self::$vendor_id;
    return db('vendor_sync')->where($map)->value('last_synced_time', '1970-01-01 00:00:00');
  }

  protected function getExistingProductIds($vendor_products_ids) {
    $map['vendor_id'] = self::$vendor_id;
    $map['vendor_product_id'] = ['in', $vendor_products_ids];
    $existing_product_ids = db('pproduct')->where($map)->column('vendor_product_id');
    return $existing_product_ids;
  }

  public function syncProducts() {
    $missing_attrs = [];
    $missing_attr_pproducts = [];
    $updated_number = 0;
    $created_number = 0;
    //获取最新的vendor(atelier)商品同步时间
    $last_synced_time = $this->getLastSyncedTime();
    $cur_time = date('Y-m-d H:i:s');
    $cur_synced_time = $this->convertDateFromTimezone($cur_time,self::$time_zone,'Y-m-d H:i:s');
    //如果上次同步时间与当前时间超过一定数值，则做系统级错误提示并主动通知管理员
    $long_no_sync_time = 3600; //一个小时未同步则报错
    if(strtotime($last_synced_time) <= time()+$long_no_sync_time) {
      //insert if not exsit
      // db('sync_error_log')->insert();
      // return;
    }
    // $start_time = time();
    // var_dump(time());
    //插入vendor_sync_history同步记录
    $data = ['vendor_id'=>self::$vendor_id, 'start_time'=>date('Y-m-d H:i:s'), 'end_time'=>'1970-01-01 00:00:00'];
    $vendor_sync_history_id = db('vendor_sync_history')->insertGetId($data);
    //根据同步时间获取最新的商品list
    $need_sync_products = $this->getNeedSyncProducts($last_synced_time);
    // var_dump(count($need_sync_products[0]));
    // var_dump(count($need_sync_products[1]));die();
    if($need_sync_products) {
      //区分list为已经存在的商品和需要新增的商品
      list($existing_products, $new_products) = $need_sync_products;
      //获取所有商品列表中所有属性ID，并且判断这些属性ID是否存在与当前系统中，如果不存在，则添加missing_attrs数组
      $missing_attrs = $this->getMissingAttrs($existing_products, $new_products);
      //对于已经存在的商品，调用detail接口获取商品价格、season、price数据做商品数据更新
      foreach ($existing_products as $ep) {
        $stock_data = $this->getStockData($ep['vendor_product_id']);
        $good_data = array_merge($ep, $stock_data);
        $upd_rlst = $this->updateProduct($good_data);
        if(count($upd_rlst)==2) {
          list($updated, $mapp) = $upd_rlst;
          if($updated){
            $updated_number++;
          }
          $missing_attr_pproducts = array_merge($missing_attr_pproducts,$mapp);
        }
      }
      //对于需要新增的商品，调用detail接口，合并list数据，调用商品新增接口新增商品。同时如果商品中存在暂时无法确定的四个属性id，则记录到需要更新的商品属性list中  
      foreach ($new_products as $np) {
        $detail_data = $this->getDetailData($np['vendor_product_id']);
        $good_data = array_merge($np, $detail_data);
        $add_rlst = $this->addProduct($good_data);
        if(count($add_rlst)==2) {
          list($added, $mapp) = $add_rlst;
          if($added){
            $created_number++;
          }
          $missing_attr_pproducts = array_merge($missing_attr_pproducts,$mapp);
        }
      }
      // var_dump($missing_attr_pproducts);
      // var_dump($created_number);
      // var_dump(time()-$start_time);
    }
    //同步完成后更新vendor_sync内的最新同步时间，同时更新vendor_sync_history的end_time
    $data = ['last_synced_time'=>$cur_synced_time,'vendor_id'=>self::$vendor_id];
    db('vendor_sync')->insert($data, true);
    $map = ['vendor_sync_history_id'=>$vendor_sync_history_id];
    $data = ['updated_number'=>$updated_number, 'created_number'=>$created_number, 'end_time'=>date('Y-m-d H:i:s')];
    db('vendor_sync_history')->where($map)->update($data);
    
    //如果存在非空的missing_attr_pproducts和missing_attrs，则插入到对应数据表中
    $need_update_missing_vendor = false;
    if(!empty($missing_attrs)) {
      $missing_attrs_model = db('missing_vendor_attr');
      foreach ($missing_attrs as $ma) {
        $map = ['vendor_id'=>$ma['vendor_id'], 'type'=>$ma['type'], 'key_id'=>$ma['key_id']];
        $ma_tmp = $missing_attrs_model->where($map)->find();
        if(!$ma_tmp) {
          $missing_attrs_model->insert($ma, true);  
          $need_update_missing_vendor = true;
        }
      }
    }
    if(!empty($missing_attr_pproducts)) {
      $missing_attr_pproducts_model = db('missing_attr_pproduct');
      foreach ($missing_attr_pproducts as $mapp) {
        $missing_attr_pproducts_model->insert($mapp, true);
      }
    }

  // var_dump($missing_attr_pproducts);
  // var_dump($missing_attrs);
  // var_dump($missing_attr_pproducts);
  // var_dump($created_number);
  // var_dump(time()-$start_time);

    //下面的属性更新任务暂时自动插入，后期多个供应商时，需要人工插入任务！
    //如果存在需要新增的new attr，则在消息队列中插入addNewAttr任务（插入之前先判断是否已经存在addNewAttr任务，如果有则不插入）
    if($need_update_missing_vendor) {
      $this->updateMissingVendorAttr();
    }

    return true;
  }

  public function updateMissingAttrProduct() {
    //根据更新过的各个attr表更新missing_attr_pproduct中未存在attr id的商品的attr id
    $missing_attr_pproduct_model = db('missing_attr_pproduct');
    $need_update_products = $missing_attr_pproduct_model->where(['vendor_id'=>self::$vendor_id])->select();
    if(empty($need_update_products)) {
      return;
    }
    cache(null);
    self::init();
    //更新所有数据
    foreach ($need_update_products as $nup) {
      $ret = $this->updateMissingAttrForProduct($nup);
      //删除已经更新过的商品
      if($ret) {
        $missing_attr_pproduct_model->where(['missing_attr_pproduct_id'=>$nup['missing_attr_pproduct_id']])->delete();
      }
    }
  }
  
}