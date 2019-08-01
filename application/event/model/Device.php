<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\event\model;

use think\Model;

class Device extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'device';


    public function getDeviceIdByCode($code){
        return 1;
        if($code){
            $device = Device::where('deviceCode', $code)->find();
            if($device){
                return $device->deviceId;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

}