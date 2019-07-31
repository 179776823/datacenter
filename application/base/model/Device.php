<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\base\model;

use think\Model;

class Device extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'device';


    public function getDeviceIdByCode($code){
        if($code){
            return 3;
        }else{
            return 0;
        }
    }

}