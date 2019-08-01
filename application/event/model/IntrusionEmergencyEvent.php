<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\event\model;

use think\Model;

class IntrusionEmergencyEvent extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'intrusionEmergencyEvent';
    protected $pk = 'id';


}