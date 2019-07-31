<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/12/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class UsbEvent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'usbEvent';
    protected $pk = 'id';

    public static function getEventTypeMap(){
        return [
            1 => '设备布防',
            2 => '设备撤防',
            3 => 'USB插入',
            4 => '报警拔出',
            5 => '撤防拔出',
            6 => '超时未回复'
        ];
    }

}