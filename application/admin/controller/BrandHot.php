<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/3/7
 * Time: 15:05
 */

namespace app\admin\controller;
use app\common\controller\Admin;


class BrandHot extends Admin
{
public function index(){
    $list = db('hot_brand')->alias('a')->join('brand b','a.brand_id=b.brand_id')->field('a.hot_brand_id,b.name')->select();
    $this->assign('list',$list);
    $this->setMeta('品牌优先设置');
    return $this->fetch();
}
public function search() {
       $filter = input('get.');
       $data = db('brand')->where('name','like','%'.$filter['filter_name'].'%')->limit(10)->select();
        foreach ($data as $v){
            $json[] = ['name'=>$v['name'], 'brand_id'=>$v['brand_id']];
        }

        $response = json($json, 200);
        return $response;
}
public function delete(){
    $filter = input("get.");
    $map=[];
    $map=explode(',',$filter['brand_ids']);
    foreach ($map as $v){
        $result = db('hot_brand')->where('hot_brand_id',$v)->delete();
    }

    if($result){
        return $this->success('删除成功！');
    }else{
        return $this->error('删除失败！');
    }
}
public function add(){
    $filter = input("get.");
    $map=[];
    $map=explode(',',$filter['brand_ids']);
    $data = [];
    $len = count($map);
    $data['brand_id'] = $map[$len-1];
    $data['date_added'] = date("Y-m-d H:i:s",time());
    $result = db('hot_brand')->insert($data);
    if($result){
        return $this->success('添加成功！');
    }else{
        return $this->error('添加失败！');
    }

}

}