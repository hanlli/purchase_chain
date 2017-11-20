<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/2/20
 * Time: 14:48
 */

namespace app\admin\controller;
use app\common\controller\Admin;


class VendorManagement extends Admin
{
    public function index(){
        $data = db('vendor')->select();
        $this->assign('data',$data);
        $this->setMeta('供应商管理');
        return $this->fetch();
    }
    public function add(){
        $filter = input('get.');
        $contents = explode(',',$filter['content']);
        $data=[];
        $data['prefix'] = $contents[1];
        $data['name']   = $contents[2];
        $data['channel']   = $contents[3];
        $data['country_id'] = 30;
        $data['vendor_code'] ="A69";
        $data['discount_mode'] = "1";
        $data['date_added'] = date('Y-m-d H:i:s',time());
        $result = db('vendor')->insert($data);
        if($result){
            $this->success('添加成功！');
        }else{
            $this->error('添加失败！');
        }
    }
    public function update(){
        $filter = input('get.');
        $map=[];
        $data=[];
        $contents = explode(',',$filter['content']);
        array_shift($contents);
        $num = count($contents);
        $num = $num/4;
        for($i=0;$i<$num;$i++){
           $map['vendor_id'] = $contents[$i*4];
           $data['prefix'] = $contents[$i*4+1];
           $data['name']   = $contents[$i*4+2];
           $data['channel'] = $contents[$i*4+3];
           $data['date_added'] = date('Y-m-d H:i:s',time());
           $result = db('vendor')->where($map)->update($data);
        }
        if($result){
            return $this->success('修改成功！');
        }else{
            return  $this->error('修改失败！');
        }

    }
    public function delete(){
        $filter = input('get.');
        $vendor_ids = explode(',',$filter['vendor_ids']);
        foreach ($vendor_ids as $v){
            if($v != null){
                $result = db('vendor')->where('vendor_id',$v)->delete();
            }
        }
        if($result){
           return $this->success('删除成功！');
        }else{
           return $this->error('删除失败！');
        }

    }
}