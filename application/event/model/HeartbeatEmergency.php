<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\event\model;

use think\Exception;
use think\Model;

class HeartbeatEmergency extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'heartbeat_emergency';
    protected $pk = 'id';

    public function saveData($insert){
        try{
            $heartBeat = HeartbeatEmergency::where('deviceId',$insert['deviceId'])->find();
            if($heartBeat){
                $data = $heartBeat->getData();
                if($data){
                    HeartbeatEmergency::update($insert,array('id'=>$data['id']));
                    return true;
                }else{
                    return false;
                }
            }else{
                HeartbeatEmergency::create($insert);
                return true;
            }
        }catch (Exception $exception){
            return false;
        }
    }
}