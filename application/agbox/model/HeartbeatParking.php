<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class HeartbeatParking extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'heartbeat_parking';
    protected $pk = 'id';

}