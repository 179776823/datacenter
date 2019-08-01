<?php


namespace app\agbox\model;


use think\Model;

class DeviceServer extends Model
{
    protected $table='deviceserver';

    public static function ServerIsRegister(){
        $server = self::where('id',1)->find();
        $nowtime = time();
        $time_diff = 60;//设置时间差
        $isRegister = 0;
        if($server->isRegister==1 && $nowtime-strtotime($server->updateTime)<=$time_diff){
            $server->isRegister = 1;
            return $server;
        }else{
            $server->isRegister = 0;
            return $server;
        }
    }
}