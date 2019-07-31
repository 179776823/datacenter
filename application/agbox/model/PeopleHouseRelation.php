<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class PeopleHouseRelation extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'peoplehouseRelation';
    protected $pk = 'id';

    public static function getRelationMap(){
        return [
            1 => '自住',
            2 => '租赁',
            3 => '民宿',
            4 => '其他'
        ];
    }
}