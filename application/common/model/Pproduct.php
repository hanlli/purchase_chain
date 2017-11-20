<?php


namespace app\common\model;

/**
* 供应商基类
*/
class Pproduct extends \think\Model{
  protected static $brand_dict = [];
  protected static $goods_type_dict = [];
  protected static $category_dict = [];
  protected static $parent_category_dict = [];
  protected static $season_dict = [];
  protected static $hot_brand_dict = [];
  protected static $brand_idx_dict = []; //品牌首字母

  public function __construct($data = []){
    parent::__construct($data);
  }

  protected static function init()
  {
    //初始化品牌、分类、季节、类型字典
    $expire_time = 3600;
    $brand_dict = cache('real_brand_dict');
    if(!$brand_dict) {
      $brand = db('brand');
      $brand_dict = $brand->column('brand_id,chs_name');
      cache('real_brand_dict',$brand_dict, $expire_time);
    }
    self::$brand_dict = $brand_dict;
    $hot_brand_dict = cache('real_hot_brand_dict');
      if(!$hot_brand_dict) {
          $hot_brand = db('hot_brand')->alias('h')->join('brand b','h.brand_id=b.brand_id');
          $hot_brand_dict = $hot_brand->column('h.brand_id,b.name');
          cache('real_hot_brand_dict',$hot_brand_dict, $expire_time);
      }
      self::$hot_brand_dict = $hot_brand_dict;

    $goods_type_dict = cache('real_goods_type_dict');
    if(!$goods_type_dict) {
      $goods_type = db('goods_type');
      $goods_type_dict = $goods_type->column('goods_type_id,chs_name');
      cache('real_goods_type_dict',$goods_type_dict, $expire_time);
    }
    self::$goods_type_dict = $goods_type_dict;

    $category_dict = cache('real_category_dict');
    if(!$category_dict) {
      $category = db('category');
      $category_dict = $category->column('category_id,chs_name');
      cache('real_category_dict',$category_dict, $expire_time);
    }
    self::$category_dict = $category_dict;

    $parent_category_dict = cache('real_parent_category_dict');
    if(!$parent_category_dict) {
      $parent_category = db('parent_category');
      $parent_category_dict = $parent_category->column('parent_category_id,chs_name');
      cache('real_parent_category_dict',$parent_category_dict, $expire_time);
    }
    self::$parent_category_dict = $parent_category_dict;

    $season_dict = cache('real_season_dict');
    if(!$season_dict) {
      $season = db('season');
      $season_dict = $season->column('season_id,chs_name');
      cache('real_season_dict',$season_dict, $expire_time);
    }
    self::$season_dict = $season_dict;

    //品牌首字符
    $first_upper_letter = function($name) {return strtoupper($name[0]);};
    self::$brand_idx_dict = array_map($first_upper_letter, $brand_dict);
  }

  public function pproduct_attr()
  {
      return $this->hasOne('PproductAttr');
  }
  
  public function product()
  {
      return $this->hasMany('Product');
  }

  public function recommended_pproduct()
  {
      return $this->hasOne('recommended_pproduct');
  }

  public function pproduct_image()
  {
      return $this->hasMany('pproduct_image');
  }

  protected function getBrandAttr($value, $data){

    return isset(self::$brand_dict[$data['brand_id']])?self::$brand_dict[$data['brand_id']]:'';
  }

  protected function getCategoryAttr($value, $data){
    return isset(self::$category_dict[$data['category_id']])?self::$category_dict[$data['category_id']]:'';
  }

  protected function getParentCategoryAttr($value, $data){
    return isset(self::$parent_category_dict[$data['parent_category_id']])?self::$parent_category_dict[$data['parent_category_id']]:'';
  }

  protected function getSeasonAttr($value, $data){
    return isset(self::$season_dict[$data['season_id']])?self::$season_dict[$data['season_id']]:'';
  }

  protected function getGoodsTypeAttr($value, $data){
    return isset(self::$goods_type_dict[$data['goods_type_id']])?self::$goods_type_dict[$data['goods_type_id']]:'';
  }

  public function getHotBrandDict(){
      return self::$hot_brand_dict;
  }

  public function getBrandDict() {
    return self::$brand_dict;
  }
  public function getCategoryDict() {
    return self::$category_dict;
  }
  public function getParentCategoryDict() {
    return self::$parent_category_dict;
  }
  public function getSeasonDict() {
    return self::$season_dict;
  }
  public function getGoodsTypeDict() {
    return self::$goods_type_dict;
  }
  public function getBrandIdxDict() {
    return self::$brand_idx_dict;
  }

  public function getPrices($product_ids) {
    $map['pproduct_id'] = ['in', $product_ids];
    $pproduct_price = db('pproduct')->where($map)->column('pproduct_id,price');
    return $pproduct_price;
  }

}