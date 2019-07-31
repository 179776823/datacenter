<?php
namespace app\agbox\controller;

use app\agbox\model\Building;
use app\agbox\model\Companies;
use app\agbox\model\House;
use app\agbox\model\People;
use app\agbox\model\PeopleHouseRelation;
use app\agbox\model\Villages;
use think\Exception;
use think\facade\Config;

class Village
{
    /**
     * 获取小区列表
     * @param $connection
     * @param $data
     */
    public function index()
    {
        $post = request()->param();
        if(!$post){
            return json([
                'jsonrpc'=>'2.0',
                'error'=>[
                    'code'=> -32700,
                    'message' => '解析错误',
                    'ver' => '2.1.0(27)'
                ],
                'id' => null
            ]);
        }
        $key = $post['key'];
        $json = $post['json'];
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params'])?$json['params']:[];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'getperson'){
            $page = isset($params['page']) ? $params['page'] : 1;
            $villageCode = isset($params['villageCode']) ? $params['villageCode'] : '';
            $buildingCode = isset($params['buildingCode']) ? $params['buildingCode'] : '';
            $houseCode = isset($params['houseCode']) ? $params['houseCode'] : '';
            $pagesize = isset($params['pagesize'])?$params['pagesize'] : 1;

            if(!$villageCode || !$buildingCode || !$houseCode ){
                $result = ['result'=>'failed','message'=>'param error'];
                return json($result);
            }
            $people = new People();
            list($total,$list) = $people->getListByPager($villageCode,$buildingCode,$houseCode,$page,$pagesize);
            $result = [
                'page' => $page,
                'page_size' => $pagesize,
                'total_page' => ceil($total/$pagesize),
                'count' => $total,
                'person' => []
            ];
            $person = [];
            if($list){
                foreach ($list as $l){
                    $row = [];
                    $row['villageCode'] = $l['villageCode'];
                    $row['buildingCode'] = $l['buildingCode'];
                    $row['buildingNo'] = $l['buildingNo'];
                    $row['houseCode'] = $l['houseCode'];
                    $row['houseNo'] = $l['houseNo'];
                    $row['name'] = $l['peopleName'];
                    $row['credentialType'] = $l['credentialType'];
                    $row['housePeopleRel'] = $l['housePeopleRel'];
                    $row['url'] = $l['idCardPicUrl'];
                    $row['peosonCode'] = $l['peopleId'];
                    $person[] = $row;
                }
            }
            $result['person'] = $person;
            return json($result);
        }else if($method == 'getlist'){
            $page = isset($params['page']) ? $params['page'] : 1;
            $pagesize = isset($params['pageSize'])?$params['pageSize'] : 10;
            $villages = new Villages();
            $villageCode = '';
            list($total,$list) = $villages->getListByPager($page,$pagesize);
            $result = [
                'page' => ceil($total/$pagesize),
                'total_page' => ceil($total/$pagesize),
                'page_size' => $pagesize,
                'count' => $total,
                'villageList' => [],
            ];
            $villageList = [];
            if($list){
                foreach ($list as $l){
                    $row = [];
                    $row['villageCode'] = $l['villageCode'];
                    $row['villageName'] = $l['villageName'];
                    $row['address'] = $l['address'] ? $l['address'] : '';
                    $row['updateTime'] = $l['updateTime'];
                    $villageList[] = $row;
                }
            }
            $result['villageList'] = $villageList;
            return json($result);
        }else if($method == 'getbuilding'){
            $page = isset($params['page']) ? $params['page'] : 1;
            $pagesize = isset($params['pageSize'])?$params['pageSize'] : 10;
            $villageCode = isset($params['villageCode'])?$params['villageCode'] : '';
            $buildingNo = isset($params['buildingNo'])?$params['buildingNo'] : '';
            $buildings = new Building();
            list($total,$list) = $buildings->getListByPager($villageCode,$buildingNo,$page,$pagesize);
            $result = [
                'page' => $page,
                'page_size' => $pagesize,
                'total_page' => ceil($total/$pagesize),
                'count' => $total,
                'villageCode' => $villageCode,
                'buildingList' => [],
            ];
            $buildingList = [];
            if($list){
                foreach ($list as $l){
                    $row = [];
                    $row['buildingCode'] = $l['buildingCode'];
                    $row['buildingNo'] = $l['buildingNo'];
                    $row['floorTotal'] = $l['floorTotal'];
                    $row['houseTotal'] = $l['houseTotal'];
                    $row['note'] = $l['Note'];
                    $buildingList[] = $row;
                }
            }
            $result['buildingList'] = $buildingList;
            return json($result);
        }else if($method == 'gethouse'){
            $page = isset($params['page']) ? $params['page'] : 1;
            $pagesize = isset($params['pageSize'])?$params['pageSize'] : 10;
            $buildingCode = isset($params['buildingCode'])?$params['buildingCode'] : '';
            $floor = isset($params['floor'])?$params['floor'] : '';
            $houses = new House();
            list($total,$list) = $houses->getListByPager($buildingCode,$floor,$page,$pagesize);
            $result = [
                'page' => $page,
                'page_size' => $pagesize,
                'total_page' => ceil($total/$pagesize),
                'count' => $total,
                'buildingCode' => $buildingCode,
                'houseList' => [],
            ];
            $houseLabelMap = House::getHouseLabelCodeMap();
            $housePurposeMap = House::getHousePurposeCodeMap();
            $houseList = [];
            if($list){
                foreach ($list as $l){
                    $row = [];
                    $row['houseCode'] = $l['houseCode'];
                    $row['floor'] = $l['floor'];
                    $row['houseNo'] = $l['houseNo'];
                    $row['houseLabelCode'] = $l['houseLabelId'];
                    $row['houseLabel'] = isset($houseLabelMap[$l['houseLabelId']])?$houseLabelMap[$l['houseLabelId']]:'';
                    $row['housePurposeCode'] = $l['housePurposeId'];
                    $row['housePurpose'] = isset($housePurposeMap[$l['housePurposeId']])?$housePurposeMap[$l['housePurposeId']]:'';
                    $row['note'] = $l['Note'];
                    $houseList[] = $row;
                }
            }
            $result['houseList'] = $houseList;
            return json($result);
        }
    }


    public function getBuildingList(){
        $post = request()->param();
        if(!isset($post['villageCode'])){
            return json([
                'code'=>'500',
                'message'=>'参数错误'
            ]);
        }
        $page = isset($post['page']) ? $post['page'] : 1;
        $pagesize = isset($post['pageSize'])?$post['pageSize'] : 1000;
        $villageCode = isset($post['villageCode'])?$post['villageCode'] : '';
        $buildingNo = isset($post['buildingNo'])?$post['buildingNo'] : '';
        $buildings = new Building();
        list($total,$list) = $buildings->getListByPager($villageCode,$buildingNo,$page,$pagesize);
        $result = [
            'page' => $page,
            'page_size' => $pagesize,
            'total_page' => ceil($total/$pagesize),
            'count' => $total,
            'villageCode' => $villageCode,
            'buildingList' => [],
        ];
        $buildingList = [];
        if($list){
            foreach ($list as $l){
                $row = [];
                $row['buildingCode'] = $l['buildingCode'];
                $row['buildingNo'] = $l['buildingNo'];
                $row['floorTotal'] = $l['floorTotal'];
                $row['houseTotal'] = $l['houseTotal'];
                $row['note'] = $l['Note'];
                $row['lon'] = $l['lon'];
                $row['lat'] = $l['lat'];
                $row['alt'] = $l['alt'];
                $row['gisArea'] = $l['gisArea'];
                $row['gisType'] = $l['gisType'];
                $row['createTime'] = $l['createTime'];
                $row['id'] = $l['buildingId'];
                $buildingList[] = $row;
            }
        }
        $result['buildingList'] = $buildingList;

        return json([
            'code'=>200,
            'message'=>'success',
            'result' => $result
        ]);
    }

    public function getHouseList(){
        $post = request()->param();
        if(!isset($post['buildingCode'])){
            return json([
                'code'=>'500',
                'message'=>'参数错误'
            ]);
        }
        $page = isset($post['page']) ? $post['page'] : 1;
        $pagesize = isset($post['pageSize'])?$post['pageSize'] : 1000;
        $buildingCode = isset($post['buildingCode'])?$post['buildingCode'] : '';
        $floor = isset($post['floor'])?$post['floor'] : '';
        $houses = new House();
        list($total,$list) = $houses->getListByPager($buildingCode,$floor,$page,$pagesize);
        $result = [
            'page' => $page,
            'page_size' => $pagesize,
            'total_page' => ceil($total/$pagesize),
            'count' => $total,
            'buildingCode' => $buildingCode,
            'houseList' => [],
        ];
        $houseLabelMap = House::getHouseLabelCodeMap();
        $housePurposeMap = House::getHousePurposeCodeMap();
        $houseList = [];
        if($list){
            foreach ($list as $l){
                $row = [];
                $row['houseCode'] = $l['houseCode'];
                $row['floor'] = $l['floor'];
                $row['houseNo'] = $l['houseNo'];
                $row['houseLabelCode'] = $l['houseLabelId'];
                $row['houseLabel'] = isset($houseLabelMap[$l['houseLabelId']])?$houseLabelMap[$l['houseLabelId']]:'';
                $row['housePurposeCode'] = $l['housePurposeId'];
                $row['housePurpose'] = isset($housePurposeMap[$l['housePurposeId']])?$housePurposeMap[$l['housePurposeId']]:'';
                $row['houseArea'] = $l['houseArea'];
                $row['peopleNumber'] = $l['peopleNumber'];
                $row['lon'] = $l['lon'];
                $row['lat'] = $l['lat'];
                $row['alt'] = $l['alt'];
                $row['gisType'] = $l['gisType'];
                $row['createTime'] = $l['createTime'];
                $row['note'] = $l['Note'];
                $row['id'] = $l['houseId'];
                $houseList[] = $row;
            }
        }
        $result['houseList'] = $houseList;

        return json([
            'code'=>200,
            'message'=>'success',
            'result' => $result
        ]);
    }
}