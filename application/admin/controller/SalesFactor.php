<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/2/21
 * Time: 18:07
 */

namespace app\admin\controller;
use app\common\controller\Admin;

class SalesFactor extends Admin
{
  public function index(){
      $map = ['status'=>'0'];
      $sf_model = db('sales_factor');
      $data = $sf_model->where($map)->select();
      if(empty($data)) {
        $data = [['factor'=>1, 'from_time'=>'', 'status'=>0]];
      }
      //当前正在使用的销售系数
      $map = ['status'=>'1'];
      $cur_factor = $sf_model->where($map)->order('from_time desc')->limit(1)->find();
      $cur_factor = empty($cur_factor)?1:$cur_factor['factor'];
      if(empty($cur_factor)) {
        $cur_factor = 1;
      } 
      $cur_factor = number_format($cur_factor, 2);
      $this->setMeta('销售系数设置');
      $this->assign('data',$data);
      $this->assign('cur_factor',$cur_factor);
      return $this->fetch();
  }
  /*
   * 时间验证
   */
  public function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
  }
  public function edit(){
      $posts = input('post.');
      //验证生效时间是否大于当前时间
      $from_time = $posts['from_time'];
      if (!$this->validateDate($from_time)) {
        return $this->error("生效起始时间格式有误");
      }
      if(strtotime($from_time)<=time()) {
        return $this->error("生效起始时间应大于当前时间");
      }
      //factor是否大于1
      $factor = $posts['factor'];
      if($factor<1) {
        return $this->error("销售系数需大于等于1"); 
      }
      
      $map = [];
      $map['status'] = 0;
      $sf_model = db('sales_factor');
      $sf_tmp = $sf_model->where($map)->find();
      $data = [];
      $data['from_time'] = $from_time;
      $data['factor'] = number_format($factor, 2);
      $data['date_added'] = date('Y-m-d H:i:s');
      if($sf_tmp) {
          $sf_model->where(['sales_factor_id'=>$sf_tmp['sales_factor_id']])->update($data);
      } else {
          $sf_model->insert($data);
      }
      return $this->success("折扣更新成功！");
  }
}