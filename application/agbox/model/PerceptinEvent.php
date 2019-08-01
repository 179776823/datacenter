<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/12/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class PerceptinEvent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'perceptinEvent';
    protected $pk = 'id';

    public static function getEventTypeMap(){
        return [
            1 => '紧急求助告警',
            2 => '紧急求助告警消除',
            3 => '紧急设备故障告警',
            4 => '紧急设备故障告警消除',
            5 => '紧急电池欠压告警',
            6 => '紧急电池欠压告警消除',
            7 => '紧急平台故障告警',
            8 => '紧急平台故障告警消除',
            9 => '电气火灾监控探测器告警',
            10 => '电气火灾监控探测告警消除',
            11 => '电气火灾电池欠压告警',
            12 => '紧急设备故障告警消除',
            13 => '电气火灾设备故障告警',
            14 => '电气火灾设备故障告警消除',
            15 => '电气火灾平台故障告警',
            16 => '电气火灾平台故障告警消除',
            17 => '可燃气体探测器告警',
            18 => '可燃气体探测器告警消除',
            19 => '可燃气体设备故障告警',
            20 => '可燃气体设备故障告警消除',
            21 => '可燃气体电池欠压告警',
            22 => '可燃气体电池欠压告警消除',
            23 => '可燃气体平台故障告警',
            24 => '可燃气体平台故障告警消除',
            25 => '门体状态探测告警',
            26 => '门体状态探测告警消除',
            27 => '门体状态设备故障告警',
            28 => '门体状态设备故障告警消除',
            29 => '门体状态电池欠压告警',
            30 => '门体状态电池欠压告警消除',
            31 => '门体状态平台故障告警',
            32 => '门体状态平台故障告警消除',
            33 => '二次供水水箱打开告警',
            34 => '二次供水水箱打开告警消除',
            35 => '二次供水箱盖松动告警',
            36 => '二次供水箱盖松动告警消除',
            37 => '二次供水水箱设防成功',
            38 => '二次供水水箱设防失败',
            39 => '二次供水设备故障告警',
            40 => '二次供水设备故障告警消除',
            41 => '二次供水电池欠压告警',
            42 => '二次供水电池欠压告警消除',
            43 => '二次供水平台故障告警',
            44 => '二次供水平台故障告警消除',
            45 => '消防占道告警',
            46 => '消防占道告警消除',
            47 => '消防占道设备故障告警',
            48 => '消防占道设备故障告警消除',
            49 => '消防占道电池欠压告警',
            50 => '消防占道电池欠压告警消除',
            51 => '消防占道平台故障告警',
            52 => '消防占道平台故障告警消除',
            53 => '窨井盖打开告警',
            54 => '窨井盖打开告警消除',
            55 => '窨井盖松动告警',
            56 => '窨井盖松动告警消除',
            57 => '窨井盖水位告警',
            58 => '窨井盖水位告警消除',
            59 => '窨井盖设防成功',
            60 => '窨井盖设防失败',
            61 => '窨井盖设备故障告警',
            62 => '窨井盖设备故障告警消除"',
            63 => '窨井盖电池欠压告警',
            64 => '窨井盖电池欠压告警消除',
            65 => '窨井盖平台故障告警',
            66 => '窨井盖平台故障告警消除',
            67 => '其他状态探测告警',
            68 => '其他状态探测告警消除',
            69 => '其他状态设备故障告警',
            70 => '其他状态设备故障告警消除',
            71 => '其他状态电池欠压告警',
            72 => '其他状态电池欠压告警消除',
            73 => '其他状态平台故障告警',
            74 => '其他状态平台故障告警消除',
            75 => '火灾探测器告警',
            76 => '火灾探测器告警消除',
            77 => '火灾探测器设备故障告警',
            78 => '火灾探测器设备故障告警消除',
            79 => '火灾探测器电池欠压告警',
            80 => '火灾探测器电池欠压告警消除',
            81 => '火灾探测器平台故障告警',
            82 => '火灾探测器平台故障告警消除',
        ];
    }

}