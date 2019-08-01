<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/12/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class ParkingEvent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'parkingEvent';
    protected $pk = 'id';

    public static function getEventTypeMap(){
        return [
            1 => '住户车辆进',
            2 => '住户车辆出',
            3 => '租户车辆进',
            4 => '租户车辆出',
            5 => '亲情车辆进',
            6 => '亲情车辆出',
            7 => '访客车辆进',
            8 => '访客车辆出',
            9 => '快递车辆进',
            10 => '快递车辆出',
            11 => '外卖车辆进',
            12 => '外卖车辆出',
            13 => '非小区车辆进',
            14 => '非小区车辆出',
            15 => '小区服务车辆进',
            16 => '小区服务车辆出',
            17 => '小区工作车辆进',
            18 => '小区工作车辆出',
            19 => '非小区车辆进',
            20 => '非小区车辆出',
            21 => '重点关注车辆进',
            22 => '重点关注车辆出',
            23 => '重点布控车辆进',
            24 => '重点布控车辆出',
            25 => '其他车辆进',
            26 => '其他车辆出',
            27 => '车辆道闸故障',
            28 => '道闸故障消除',
            29 => '设备故障',
            30 => '设备故障消除',
            31 => '系统故障',
            32 => '系统故障消除'
        ];
    }

}