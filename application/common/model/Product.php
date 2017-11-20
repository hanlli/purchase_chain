<?php


namespace app\common\model;

/**
* 用户模型
*/
class Product extends \think\Model{
  public function getStockByIds($product_ids) {
    $map['product_id'] = ['in', $product_ids];
    $product_stocks = db('product')->where($map)->column('product_id,stock');
    return $product_stocks;
  }
  public function getStockInfoByIds($product_ids) {
    $map['product_id'] = ['in', $product_ids];
    $product_stocks = db('product')->where($map)->column('product_id,stock,size,sku,pproduct_id','product_id');
    return $product_stocks;
  }
}