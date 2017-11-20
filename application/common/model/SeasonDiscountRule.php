<?php
/**
 * Created by PhpStorm.
 * User: think-tec
 * Date: 2017/2/15
 * Time: 15:21
 */

namespace app\common\model;

use think\db;
class SeasonDiscountRule extends Base
{
   public function change()
   {

   }

   public function vendor()
   {
       return $this->hasOne('vendor','vendor_id','vendor_id');
   }

    public function season()
    {
        return $this->hasOne('season','season_id','season_id');
    }
}