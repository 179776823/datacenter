<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;
use think\Db;

class Devices extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'device';
    protected $pk = 'deviceId';

    public function getDeviceByCodeAndType($code,$type,$channel = 0){
        $nvrType = [1,4,5];
        if($channel && in_array($type, $nvrType)){
            // nvr code
            $nvr = Db::table('nvr_device')->where('nvrCode',$code)->find();
            if(!$nvr){
                return self::where('deviceCode', $code)->where('deviceType',$type)->find();
            }else{
                $nvrdevice =  self::where('nvrId', $nvr['nvrId'])->where('channel',$channel)->where('deviceType',$type)->find();
                if(!$nvrdevice){
                    return self::where('deviceCode', $code)->where('deviceType',$type)->find();
                }else{
                    return $nvrdevice;
                }
            }
        }else{
            return self::where('deviceCode', $code)->where('deviceType',$type)->find();
        }
    }

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

    public function parking()
    {
        return $this->hasOne('Parking','id','deviceObjId');
    }

}