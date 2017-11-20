<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/2/22
 * Time: 14:35
 */

namespace app\admin\controller;
use app\common\controller\Admin;
use PHPExcel;
use PHPExcel_Reader_Excel2007;
use PHPExcel_Reader_Excel5;

class CommodityRecommendation extends admin
{
    public function index(){
        $this->setMeta('推荐商品设置');
        return $this->fetch();
    }
    public function upload(){

        $file = request()->file('fileField');
        $base_upload = ROOT_PATH . 'public' . DS . 'uploads';
        $savePath = $file->move($base_upload);
        $ext = $savePath->getExtension();
        if($ext!='xlsx'&&$ext!='xls'){
            return  $this->error('请上传excel文件');
        }
        $PHPExcel = new PHPExcel();
        $PHPReader = new PHPExcel_Reader_Excel2007();

        if(!$PHPReader->canRead($base_upload . DS .$savePath->getSaveName())){
            $PHPReader = new PHPExcel_Reader_Excel5();
        }

        $PHPExcel = $PHPReader->load($base_upload . DS .$savePath->getSaveName());
        $currentSheet = $PHPExcel->getSheet(0);
        $allRow = $currentSheet->getHighestRow();
        $Recom_id = [];

        for($currentRow=2;$currentRow<=$allRow;$currentRow++){
            $tmp_idx = 'A';
            $Recom_info[$currentRow] = trim(strtoupper((String)$currentSheet->getCell($tmp_idx.$currentRow)->getValue()));
            if(empty($Recom_info[$currentRow])) {
                continue;
            }
            $pproduct = db('pproduct')->where(array('spu'=>$Recom_info[$currentRow]))->column('pproduct_id');
            if($pproduct!=null){
                if(array_key_exists($pproduct[0],$Recom_id)){
                  return  $this->error('第A'.$currentRow.'SPU与前面的重复了！');
                }
                $Recom_id[$pproduct[0]]=$Recom_info[$currentRow];
            }else{
                  return  $this->error('第A'.$currentRow.'SPU不存在！');
            }
        }

        $recommended_pproduct_ids = db('recommended_pproduct')->column('recommended_pproduct_id');
        foreach ($recommended_pproduct_ids as $v){
             $re = db('recommended_pproduct')->delete($v);
        }

        if(1||$re){
            foreach ($Recom_id as $k=>$v){
                $data = [];
                $data['pproduct_id'] = $k;
                $data['date_added'] = date('Y-m-d H:i:s',time());
                $result = db('recommended_pproduct')->insert($data);
            }

            if($result){
                return $this->success('推荐成功！');
            }else{
                return $this->error('推荐失败！');
            }
        }else{
            return $this->error('推荐失败');
        }

    }
}