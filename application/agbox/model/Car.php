<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class Car extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'car';
    protected $pk = 'carId';

    public static function createOrUpdate($params){
        if($params){
            $car = Car::where('plateNo', $params['plateNo'])
                ->find();
            $people = People::where('credentialType', $params['credentialType'])
                ->where('credentialNo',$params['credentialNo'])
                ->find();
            if($people){
                $params['peopleId'] = $people->peopleId;
                $params['name'] = $people->peopleName;
            }
            if($car){
                $params['updateTime'] = date('Y-m-d H:i:s');
                Car::update($params,['plateNo'=>$params['plateNo']]);
                return $car->carId;
            }else{
                $car = self::create($params);
                return $car->carId;
            }
        }else{
            return 0;
        }
    }
    public static function getTypeMap(){
        return [
            1 => '住户车辆',
            2 => '租户车辆',
            3 => '亲情车辆',
            4 => '服务车辆',
            5 => '亲情车辆进',
            6 => '亲情车辆出',
            7 => '访客车辆进',
            8 => '访客车辆出',
            9 => '快递车辆进',
            10 => '其他车辆'
        ];
    }

    public static function getPlateCarColorMap(){
        return [
            1 => '未知',
            2 => '蓝色',
            3 => '白色',
            4 => '黄色',
            5 => '黑色',
            6 => '绿色'
        ];
    }

    public static function getPlateCodeMap(){
        return [
            1 => '普通蓝牌',
            2 => '普通黑牌',
            3 => '普通黄牌',
            4 => '双层黄牌',
            5 => '教练车牌',
            6 => '警车车牌',
            7 => '新式武警车牌',
            8 => '新式军车',
            9 => '大使馆车牌',
            10 => '新能源车牌',
            11 => '其他车牌'
        ];
    }

}