<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/2/15
 * Time: 12:02
 */

namespace app\admin\controller;
use app\common\controller\Admin;


class Discount extends Admin
{
    /*
     * 季节折扣列表页
     */
    public function index()
    {
        $season_discount_rule_model = model('season_discount_rule');
        $list = $season_discount_rule_model->with('vendor,season')->paginate(20);
        $data = array(
            'list' => $list,
            'page' => $list->render(),
        );
        $vendors = db('vendor')->select();
        $data['vendors'] = $vendors;
        $data['selected_vendor_id'] = 1;
        $this->assign($data);
        $this->setMeta('季节折扣展示');
        return $this->fetch();

    }
    /*
     * 季节折扣设置页
     */
    public function add(){
        $filter = input('get.');
        $vendor = db('vendor')->select();
        $data['vendor'] = $vendor;

        if(isset($filter['vendor_id'])&&$filter['vendor_id']!=0){
            $map['vendor_id'] = $filter['vendor_id'];
            $vendors = db('vendor')->where($map)->select();
        }else{
            $vendors = db('vendor')->select();
        }
        if(isset($filter['vendor_id'])&&$filter['vendor_id']!=0){
            $map['sdr.vendor_id'] = $filter['vendor_id'];
        }
        $data['vendors'] = $vendors;
        $map['sdr.status'] = 0;
        $list = db('season')->alias('s')->join('season_discount_rule sdr','s.season_id=sdr.season_id')->where($map)->column('s.name,s.season_id, sdr.season_discount_rule_id, sdr.discount, sdr.from_time');
        $season_list = db('season')->alias('s')->column('s.season_id, s.name');
        $new_list = [];
        foreach ($season_list as $sid=>$sl) {
            if(array_key_exists($sl, $list)) {
                $new_list[] = $list[$sl];
            } else {
                $new_list[] = ['name'=>$sl,'season_id'=>$sid,'season_discount_rule_id'=>null, 'discount'=>null, 'from_time'=>null];
            }
        }
        $data['new_list'] = $new_list;
        $data['selected_vendor_id'] = isset($filter['vendor_id'])?$filter['vendor_id']:0;
        $this->assign($data);

        $this->setMeta('季节折扣设置');

        return $this->fetch();

    }
    public function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    /*
     * 添加或修改季节折扣
     */
    public function edit(){
        $posts = input('post.');
        $vendor_id = $posts['target'];
        //验证生效时间是否大于当前时间
        $from_time = $posts['from_time'];
        if (!$this->validateDate($from_time)) {
            return $this->error("生效起始时间格式有误");
        }
        if(strtotime($from_time)<=time()) {
            return $this->error("生效起始时间应大于当前时间");
        }
        //原则上还要验证季节是否都存在于season表中
        //验证每个上传上来的季节折扣都在(0,10]之间
        $discounts = $posts['discount'];
        $discounts = array_shift($discounts);
        foreach ($discounts as $season_id => $sdr_val) {
            $discount_val = array_shift($sdr_val);
            if($discount_val<=0 || $discount_val>10) {
                return $this->error("折扣值需要在0和10之间");
            }
        }
        
        $map = [];
        $map['vendor_id'] = $vendor_id;
        $map['status'] = 0;
        $sdr_model = db('season_discount_rule');
        foreach ($discounts as $season_id => $sdr_val) {
            //检查vendor_id, season_id, status=0的记录是否存在，如果存在则删除再更新
            $map['season_id'] = $season_id;
            $sdr_tmp = $sdr_model->where($map)->find();
            $data = [];
            $data['from_time'] = $from_time;
            $data['discount'] = array_shift($sdr_val);
            $data['date_added'] = date('Y-m-d H:i:s');
            if($sdr_tmp) {
                $sdr_model->where(['season_discount_rule_id'=>$sdr_tmp['season_discount_rule_id']])->update($data);
            } else {
                $data['vendor_id'] = $vendor_id;
                $data['season_id'] = $season_id;
                $sdr_model->insert($data);
            }
        }
        return $this->success("折扣更新成功！");
    }
}