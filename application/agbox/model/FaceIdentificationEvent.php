<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class FaceIdentificationEvent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'faceIdentificationEvent';
    protected $pk = 'id';

    public static function getEventTypeMap(){
        return [
            1 => '住户人员进',
            2 => '住户人员出',
            3 => '租户人员进',
            4 => '租户人员出',
            5 => '亲情人员进',
            6 => '亲情人员出',
            7 => '访客人员进',
            8 => '访客人员出',
            9 => '快递人员进',
            10 => '快递人员出',
            11 => '外卖人员进',
            12 => '外卖人员出',
            13 => '非小区人员进',
            14 => '非小区人员出',
            15 => '小区服务人员进',
            16 => '小区服务人员出',
            17 => '小区工作人员进',
            18 => '小区工作人员出',
            19 => '非小区人员进',
            20 => '非小区人员出',
            21 => '重点关注人员进',
            22 => '重点关注人员出',
            23 => '重点布控人员进',
            24 => '重点布控人员出',
            25 => '其他人员进',
            26 => '其他人员出',
            27 => '设备故障',
            28 => '设备故障消除',
            29 => '系统故障',
            30 => '系统故障消除'
        ];
    }
}