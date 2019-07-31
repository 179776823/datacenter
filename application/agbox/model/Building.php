<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class Building extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'building';
    protected $pk = 'buildingId';

    public function getListByPager($villageCode,$buildingNo,$page,$limit = 10){
        $offset = ($page-1) * $limit;
        $select = self::join('village','village.villageId = building.villageId');
        $select->where('village.villageCode',$villageCode);
        if($buildingNo){
            $select->whereLike('buildingNo',$buildingNo);
        }
        $count = $select->count();
        $list = $select->limit($offset,$limit)->select();
        return array($count,$list);
    }
}