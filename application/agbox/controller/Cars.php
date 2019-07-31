<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2019/01/02
 * Time: 上午12:21
 */

namespace app\agbox\controller;

use app\agbox\model\AccessControlEvent;
use app\agbox\model\Car;
use app\agbox\model\ParkingEvent;
use think\Exception;

class Cars
{
    /**
     * 后台获取车辆列表
     */
    public function getlist(){
        $car = new Car();
        $list = $car->where('enable',1)->order('carId','desc')->select();
        $carList = [];
        $codeType = Car::getPlateCodeMap();
        $carType = Car::getTypeMap();

        foreach ($list as $l){
            $row = [];
            $row['carId'] = $l->carId;
            $row['plateNo'] = $l->plateNo;
            $row['plateType'] = isset($codeType[$l->plateType]) ? $codeType[$l->plateType] : $l->plateType;
            $row['carType'] = isset($carType[$l->carType]) ? $carType[$l->carType] : $l->carType;;
            $row['name'] = $l->name;
            $row['contactTel'] = $l->contactTel;
            $row['note'] = '';
            $carList[] = $row;
        }
        return json([
            'code' => 200,
            'message' => 'success',
            'result' => [
                'list'=>$carList,
            ]
        ]);
    }

    public function getCarsEvent(){
        $post = request()->param();
        if(!isset($post['plateNo'])){
            return json([
                'code'=>'500',
                'message'=>'参数错误'
            ]);
        }
        $plateNo = isset($post['plateNo']) ? $post['plateNo'] : 0;
        $parkingEvent = new ParkingEvent();
        $events = $parkingEvent->where('plateNo',$plateNo)->order('id','desc')->select();
        $list = [];
        if($events){
            $eventMap = ParkingEvent::getEventTypeMap();
            foreach ($events as $parking){
                $row = [];
                $row['triggerTime'] = $parking->triggerTime;
                $row['plateNo'] = $parking->plateNo;
                $row['deviceCode'] = $parking->deviceCode;
                if($eventMap){
                    if($parking->eventType){
                        $row['eventType'] = isset($eventMap[$parking->eventType]) ?  $eventMap[$parking->eventType] : $parking->eventType;
                    }elseif($parking->eventCode){
                        $row['eventType'] = isset($eventMap[$parking->eventCode]) ?  $eventMap[$parking->eventCode] : $parking->eventCode;
                    }
                }
                $row['picUrl'] = isset($parking->eventPicUrl) ? $parking->eventPicUrl : '';
                $list[] = $row;
            }
        }
        return json([
            'code' => 200,
            'message' => 'success',
            'result' => [
                'list'=>$list,
            ]
        ]);
    }
}