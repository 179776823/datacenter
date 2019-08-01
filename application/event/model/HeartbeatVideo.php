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

class HeartbeatVideo extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'heartbeat_video';
    protected $pk = 'id';

    public function saveData($insert){
        try{
            $heartBeat = HeartbeatVideo::where('deviceId',$insert['deviceId'])->find();
            if($heartBeat){
                $data = $heartBeat->getData();
                if($data){
                    HeartbeatVideo::update($insert,array('id'=>$data['id']));
                    return true;
                }else{
                    return false;
                }
            }else{
                HeartbeatVideo::create($insert);
                return true;
            }
        }catch (Exception $exception){
            return false;
        }
    }
}