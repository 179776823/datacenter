<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2019/01/02
 * Time: 上午12:21
 */

namespace app\agbox\controller;

use app\agbox\model\Building;
use app\agbox\model\House;
use app\agbox\model\Villages;
use Bluerhinos\phpMQTT;
use think\Exception;

class Base
{
    /**
     * 小区信息
     */
    public function addVillage()
    {
        $post = request()->param();
        $villageId = isset($post['id'])?$post['id']:0;
//        $villageName = $post['villageName'];
//        $provinceCode = $post['provinceCode'];
//        $cityCode = $post['cityCode'];
//        $districtCode = $post['districtCode'];
//        $streetCode = $post['streetCode'];
//        $roadCode = $post['roadCode'];
//        $address = $post['address'];
//        $policeStation = $post['policeStation'];
        if($villageId){
            $village = Villages::where('villageId',$villageId)->find();
            if($village){
                if(Villages::update($post,['villageId',$villageId])){
                    $result = ['result'=>'success','message'=>['id'=>$village->villageId],'code'=>200];
                }else{
                    $result = ['result'=>'failed','message'=>'更新失败','code'=>500];
                }
            }else{
                $result = ['result'=>'failed','message'=>'参数id错误','code'=>500];
            }
            return json($result);
        }else{
            $post['villageCode'] = uuid();
            $post['createTime'] = $post['updateTime'] = date('Y-m-d H:i:s');
            $create = Villages::create($post);
            if($create){
                $result = ['code'=>200,'message'=>'success','result'=>['id'=>$create->villageId]];
            }else{
                $result = ['code'=>500,'message'=>'failed','result'=>'failed'];
            }
            return json($result);
        }
    }

    public function addHouse(){
        $post = request()->param();
        $houseId = isset($post['id'])?$post['id']:0;
        $villageCode = $post['villageCode'];
        $buildingCode = $post['buildingCode'];
        if(!$villageCode || !$buildingCode){
            $result = ['result'=>'failed','code'=>500,'message'=>'参数错误'];
            return json($result);
        }
        $village = Villages::where('villageCode',$villageCode)->find();
        $building = Building::where('buildingCode',$buildingCode)->find();
        if(!$village || !$building){
            $result = ['result'=>'failed','code'=>500,'message'=>'参数错误'];
            return json($result);
        }
        if($building->villageId != $village->villageId){
            $result = ['result'=>'failed','code'=>500,'message'=>'小区与房屋不匹配'];
            return json($result);
        }
        if($houseId){
            $house = House::where('houseId',$houseId)->find();
            if($house){
                $post['villageId'] = $village->villageId;
                $post['buildingId'] = $building->buildingId;
                unset($post['villageCode']);
                unset($post['buildingCode']);
                unset($post['id']);
                if(House::where('houseId',$houseId)->update($post)){
                    $result = ['result'=>['id'=>$houseId],'msg'=>'success','code'=>200];
                }else{
                    $result = ['result'=>'failed','message'=>'更新失败','code'=>500];
                }
            }else{
                $result = ['result'=>'failed','message'=>'参数id错误','code'=>500];
            }
            return json($result);
        }else{
            $post['houseCode'] = uuid();
            $post['villageId'] = $village->villageId;
            $post['buildingId'] = $building->buildingId;
            unset($post['villageCode']);
            unset($post['buildingCode']);
            $post['createTime'] = $post['updateTime'] = date('Y-m-d H:i:s');
            $create = House::create($post);
            if($create){
                $result = ['result'=>['id'=>$create->houseId],'message'=>'success','code'=>200];
            }else{
                $result = ['result'=>'failed','message'=>'failed','code'=>500];
            }
            return json($result);
        }
    }

    public function addBuilding(){
        $post = request()->param();
        $buildingId = isset($post['id'])?$post['id']:0;
        $villageCode = $post['villageCode'];
        if(!$villageCode){
            $result = ['result'=>'failed','code'=>500,'message'=>'参数错误'];
            return json($result);
        }
        $village = Villages::where('villageCode',$villageCode)->find();
        if(!$village){
            $result = ['result'=>'failed','code'=>500,'message'=>'参数错误'];
            return json($result);
        }
        if($buildingId){
            $building = Building::where('buildingId',$buildingId)->find();
            if($building){
                $post['villageId'] = $village->villageId;
                unset($post['villageCode']);
                unset($post['id']);
                if(Building::where('buildingId',$buildingId)->update($post)){
                    $result = ['result'=>['id'=>$building->buildingId],'msg'=>'success','code'=>200];
                }else{
                    $result = ['result'=>'failed','message'=>'更新失败','code'=>500];
                }
            }else{
                $result = ['result'=>'failed','message'=>'参数id错误','code'=>500];
            }
            return json($result);
        }else{
            $post['buildingCode'] = uuid();
            $post['villageId'] = $village->villageId;
            unset($post['villageCode']);
            $post['createTime'] = $post['updateTime'] = date('Y-m-d H:i:s');
            $create = Building::create($post);
            if($create){
                $result = [
                    'code' => 200,
                    'message'=>'success',
                    'result' => [
                        'id' => $create->buildingId
                    ]
                ];
            }else{
                $result = [
                    'code' => 500,
                    'message'=>'failed',
                    'result' => 'failed'
                ];
            }
            return json($result);
        }
    }

    public function delHouse(){
        $post = request()->param();
        if(!isset($post['id'])){
            return json(array('code'=>500,'message'=>'param failed'));
        }
        $house = House::get(['houseId'=>$post['id']]);
        if(!$house){
            return json(array('code'=>500,'message'=>'该房屋不存在,无法删除'));
        }
        try {
            House::destroy(['houseId'=>$post['id']]);
            return json([
                'code'=>200,
                'message'=>'success',
            ]);
        }catch (Exception $exception){
            return json([
                'code'=>500,
                'message'=>$exception->getMessage()
            ]);
        }
    }

    public function delBuilding(){
        $post = request()->param();
        if(!isset($post['id'])){
            return json(array('code'=>500,'message'=>'param failed'));
        }
        $building = Building::get(['buildingId'=>$post['id']]);
        if(!$building){
            return json(array('code'=>500,'message'=>'该楼栋不存在,无法删除'));
        }
        try {
            Building::destroy(['buildingId'=>$post['id']]);
            return json([
                'code'=>200,
                'message'=>'success',
            ]);
        }catch (Exception $exception){
            return json([
                'code'=>500,
                'message'=>$exception->getMessage()
            ]);
        }
    }

}