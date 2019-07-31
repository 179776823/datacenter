<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class House extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'house';
    protected $pk = 'houseId';


    public function getListByPager($buildingCode,$floor,$page,$limit = 10){
        $offset = ($page-1) * $limit;
        $select = self::join('building','building.buildingId = house.buildingId');
        $select->where('building.buildingCode',$buildingCode);
        if($floor){
            $select->whereLike('house.floor',$floor);
        }
        $count = $select->count();
        $list = $select->limit($offset,$limit)->select();
        return array($count,$list);
    }

    public static function getHouseLabelCodeMap(){
        return  [
            1 =>'商品住房',
            2 =>'售后公房',
            3 =>'只管公房',
            4 =>'新式里弄',
            5 =>'旧式里弄',
            6 =>'其他住宅'
        ];
    }

    public static function getHousePurposeCodeMap(){
        return  [
            1 =>'成套住宅',
            2 =>'非套住宅',
            3 =>'集体宿舍',
            4 =>'其他住宅',
        ];
    }

}