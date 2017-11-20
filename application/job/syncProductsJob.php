<?php
namespace app\job;

use think\queue\Job;
use app\common\model\Atelier as AtApi;

class syncProductsJob{

    public function fire(Job $job, $data){
      //....这里执行具体的任务 
      db('missing_vendor_attr')->insert(['vendor_id'=>11,'type'=>'brand','name'=>'sbs','key_id'=>$data,'date_added'=>date('Y-m-d H:i:s')]);
      
      if ($job->attempts() > 3) {
           //通过这个方法可以检查这个任务已经重试了几次了
      }
      //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
      $job->delete();
      // 也可以重新发布这个任务
      // $job->release($delay); //$delay为延迟时间
      return;
    }

    // public function syncProducts(Job $job, $data){
    public function syncProducts( $job, $data){
      $vendor_id = 1; //先hardcode atelier的供应商id
      $atelier = new AtApi(['vendor_id'=>$vendor_id]);
      $ret = $atelier->syncProducts();
      if($ret) {
        $job->delete();
      }
    }

    public function addNewAttr(Job $job, $data){
    }

    public function updateDiscount(Job $job, $data){
      //折扣同步也做成精细化，满足条件的season与product才需要更新，避免过度更新
    }    

    public function failed($data){
      // ...任务达到最大重试次数后，失败了
    }

}