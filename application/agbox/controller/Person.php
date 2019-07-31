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
use app\agbox\model\People;
use app\agbox\model\PeopleHouseRelation;
use app\agbox\model\Villages;
use Bluerhinos\phpMQTT;
use think\Exception;
use think\facade\Config;

class Person
{
    /**
     * 更新人员信息
     */
    public function index()
    {
        $post = request()->param();
        $key = $post['key'];
        $json = $post['json'];
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params']) ? $json['params'] : [];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        $insertParam = [];
        if($method == 'update'){
            $insertParam = $this->processPersonData($params);
            // 新增或更新数据
            if(isset($params['domicile'])) {
                if(isset($params['domicile']['IDPic'])) {
                    $picKey = $params['domicile']['IDPic'];
                    // 上传图片
                    $pic = isset($post[$picKey]) ? $post[$picKey] : '';
                    // trace($pic);
                    if ($pic) {
                        // 保存base64图片
                        $imgUrl = saveImage($pic, 'people');
                        if ($imgUrl) {
                            $insertParam['domicileIDPicUrl'] = $imgUrl;
                        }
                    } else {
                        if ($_FILES) {
                            $picKey = $params['domicile']['IDPic'];
                            // 上传图片
                            $pic = $_FILES[$picKey];
                            if ($pic) {
                                // 保存base64图片
                                $imgUrl = saveImage($pic, 'people', 1);
                                if ($imgUrl) {
                                    $insertParam['domicileIDPicUrl'] = $imgUrl;
                                }
                            }
                        }
                    }
                }
            }

            // 上传报警信息
            $insertParam['createTime'] = $insertParam['updateTime'] = date('Y-m-d H:i:s');
            $insertParam['isblack'] = 0;
            $peopleId = People::createOrUpdate($insertParam);
            if($peopleId){
                $people = People::where('peopleId',$peopleId)->find();
                $mqttdata = [
                    'count' => 0,
                    'personCode' => $people->peopleCode,
                    'name' =>$people->peopleName,
                    'certifiedType'=>$people->credentialType,
                    'certifiedNo' => $people->credentialNo,
                    'url' => $people->domicileIDPicUrl,
                    'personType' => $people->peopleTypeCode,
                    'no' => $people->peopleId
                ];

                $source = 'whitelist';
                $totalcount = People::getPeopleCount(1);
                $typecount = People::getPeopleCount(1,$people->peopleTypeCode);

                $mqttdata['count'] = $totalcount;
                $this->publishMqtt('person/'.$source.'/#',json_encode($mqttdata));
//                $this->publishMqtt('person/'.$source.'/+',json_encode($mqttdata));

                $mqttdata['count'] = $typecount;
                $this->publishMqtt('person/'.$source.'/'.$people->peopleTypeCode,json_encode($mqttdata));

                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }elseif($method == 'getcredentialtypecode'){
            // 证件类型
            $type = People::getCredentialTypeMap();
            $getcredentialtype = [];
            foreach ($type as $key => $v){
                $getcredentialtype[] = [
                    'code'=>$key,
                    'name'=>$v
                ];
            }
            return json([
                'jsonrpc' => '2.0',
                'result' => [ 'credentialType'=>$getcredentialtype]
            ]);
        }else if($method == 'getpeopletypecode'){
            $type = People::getPeopleTypeCodeMap();
            $peopleType = [];
            foreach ($type as $key => $v){
                $peopleType[] = [
                    'code'=>$key,
                    'name'=>$v
                ];
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['peopleType'=>$peopleType],
                'id'=>123
            ]);
        }else if($method == 'updateblack'){
            $insertParam = $this->processPersonData($params);
            // 新增或更新数据
            if(isset($params['domicile'])) {
                if(isset($params['domicile']['IDPic'])) {
                    $picKey = $params['domicile']['IDPic'];
                    // 上传图片
                    $pic = isset($post[$picKey]) ? $post[$picKey] : '';
                    // trace($pic);
                    if ($pic) {
                        // 保存base64图片
                        $imgUrl = saveImage($pic, 'people');
                        if ($imgUrl) {
                            $insertParam['domicileIDPicUrl'] = $imgUrl;
                        }
                    } else {
                        if ($_FILES) {
                            $picKey = $params['domicile']['IDPic'];
                            // 上传图片
                            $pic = $_FILES[$picKey];
                            if ($pic) {
                                // 保存base64图片
                                $imgUrl = saveImage($pic, 'people', 1);
                                if ($imgUrl) {
                                    $insertParam['domicileIDPicUrl'] = $imgUrl;
                                }
                            }
                        }
                    }
                }
            }

            // 上传报警信息
            $insertParam['createTime'] = $insertParam['updateTime'] = date('Y-m-d H:i:s');
            $insertParam['isblack'] = 1;
            $peopleId = People::createOrUpdate($insertParam);
            if($peopleId){
                $people = People::where('peopleId',$peopleId)->find();
                $mqttdata = [
                    'count' => 0,
                    'personCode' => $people->peopleCode,
                    'notify' => false,
                    'note' =>$people->peopleName,
                    'filename' => $people->peopleName.'.jpg',
                    'groupName'=>'',
                    'url' => Config::get('img_host').$people->domicileIDPicUrl,
                    'personType' => $people->peopleTypeCode,
                    'no' => $people->peopleId
                ];
                $source = 'blacklist';
                $totalcount = People::getPeopleCount(2);
                $typecount = People::getPeopleCount(2,$people->peopleTypeCode);

                $mqttdata['count'] = $totalcount;
                $this->publishMqtt('person/'.$source.'/#',json_encode($mqttdata));
//                $this->publishMqtt('person/'.$source.'/+',json_encode($mqttdata));

                $mqttdata['count'] = $typecount;
                $this->publishMqtt('person/'.$source.'/'.$people->peopleTypeCode,json_encode($mqttdata));

                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }
    }

    /**
     * 人员房屋绑定
     */
    public function bindingHouse(){
        $post = request()->param();
        $key = $post['key'];
        $json = $post['json'];
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = $json['params'];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'bindinghouse'){
            $credentialType = $params['credentialType'];
            $credentialNo = $params['credentialNo'];
            if(!$credentialNo || !$credentialNo){
                $result = ['code'=>500,'result'=>'failed','message'=>'参数错误'];
                return json($result);
            }else{
                //检查人是否存在
                $people = People::where('credentialType',$credentialType)->where('credentialNo',$credentialNo)->find();
                if(!$people){
                    $result = ['code'=>500,'result'=>'failed','message'=>'人不存在'];
                    return json($result);
                }
                $house = $params['house'];
                if($house){
                    $message = '关联成功';
                    $code = 200;
                    $result = 'success';
                    foreach ($house as $value){
                        $villageCode = $value['villageCode'];
                        $buildingCode = $value['buildingCode'];
                        $houseCode = $value['houseCode'];
                        $houseRelCode = $value['HouseRelCode'];
                        $village = Villages::where('villageCode',$villageCode)->find();
                        $building = Building::where('buildingCode',$buildingCode)->find();
                        $house = House::where('houseCode',$houseCode)->find();
                        if($village && $building && $house){
                            $relation = [];
                            $relation['peopleId'] = $people->peopleId;
                            $relation['villageId'] = $village->villageId;
                            $relation['buildingId'] = $building->buildingId;
                            $relation['houseId'] = $house->houseId;
                            $relation['villageCode'] = $villageCode;
                            $relation['buildingCode'] = $buildingCode;
                            $relation['houseCode'] = $houseCode;
                            $relation['housePeopleRel'] = $houseRelCode;
                            $relation['createTime'] = date('Y-m-d H:i:s');
                            $relation['updateTime'] = date('Y-m-d H:i:s');
                            $relationObj = PeopleHouseRelation::create($relation);
                            if(!$relationObj){
                                $code = 500;
                                $result = 'failed';
                                $message = '关联失败';
                                break;
                            }else{
//                                $mqttdata = [
//                                    'count' => 0,
//                                    'personCode' => $people->peopleCode,
//                                    'name' =>$people->peopleName,
//                                    'certifiedType'=>$people->credentialType,
//                                    'certifiedNo' => $people->credentialNo,
//                                    'url' => $people->domicileIDPicUrl,
//                                    'personType' => $people->peopleTypeCode,
//                                    'no' => $people->peopleId
//                                ];
//                                if($people->isblack == 0){
//                                    $source = 'whitelist';
//                                    $totalcount = People::getPeopleCount(1);
//                                    $typecount = People::getPeopleCount(1,$people->peopleTypeCode);
//                                }else{
//                                    $source = 'blacklist';
//                                    $totalcount = People::getPeopleCount(2);
//                                    $typecount = People::getPeopleCount(2,$people->peopleTypeCode);
//                                }
//                                $mqttdata['count'] = $totalcount;
//                                $this->publishMqtt('person/'.$source.'/#',json_encode($mqttdata));
//                                $this->publishMqtt('person/'.$source.'/+',json_encode($mqttdata));
//
//                                $mqttdata['count'] = $typecount;
//                                $this->publishMqtt('person/'.$source.'/'.$people->peopleTypeCode,json_encode($mqttdata));
                            }
                        }else{
                            $code = 500;
                            $result = 'failed';
                            $message = '房屋数据未存在在系统中';
                            break;
                        }
                    }
                    $result = ['code'=>$code,'result'=>$result,'message'=>$message];
                    return json($result);
                }else{
                    $result = ['code'=>500,'result'=>'failed','message'=>'没有绑定的房屋'];
                    return json($result);
                }
            }
        }else{
            $result = ['result'=>'failed'];
            return json($result);
        }
    }


    private function processPersonData($params){
        $map = [
            'peopleName' => 'peopleName',
            'credentialType' => 'credentialType',
            'credentialNo'  => 'credentialNo',
            'personTypeCode' => 'peopleTypeCode',
            'source' => 'source',
            'domicile' => [
                'nationCode'    => 'domicileNationCode',
                'birthDate' => 'domicileBirthDate',
                'genderCode' => 'genderCode',
                'address'   => 'domicileAddress',
                'provinceCode' => 'domicileProvinceCode',
                'cityCode' => 'domicileCityCode',
                'districtCode' => 'domicileDistrictCode',
                'streetCode'    =>  'domicileStreetCode',
                'roadCode'  =>  'domicileRoadCode'
            ],
            'origin' => 'origin',
            'typeCode' => 'typeCode',
            'residence' => [
                'address'   =>  'residenceAddress',
                'provinceCode' => 'residenceProvinceCode',
                'cityCode' => 'residenceCityCode',
                'districtCode' => 'residenceDistrictCode',
                'streetCode'    =>  'residenceStreetCode',
                'roadCode'  =>  'residenceRoadCode'
            ],
            'educationCode' =>  'educationCode',
            'maritalStatusCode' => 'maritalStatusCode',
            'spouseName' => 'spouseName',
            'spouseType' => 'spouseType',
            'spouseNO' =>'spouseNO',
            'nationalityCode'=> 'nationalityCode',
            'entryTime' => 'entryTime',
            'surnameEng' => 'surnameEng',
            'nameEng' => 'nameEng',
            'phone1' => [
                'no'    =>  'phoneNoOne',
                'name'  =>  'phoneNoOnePerson',
                'credentialType'    =>  'phoneNoOnePersonType',
                'credentialNo'  =>  'phoneNoOnePersonID'
            ],
            'phone2' => [
                'no'    =>  'phoneNoTwo',
                'name'  =>  'phoneNoTwoPerson',
                'credentialType'    =>  'phoneNoTwoPersonType',
                'credentialNo'  =>  'phoneNoTwoPersonID'
            ],
            'phone3' => [
                'no'    =>  'phoneNoThree',
                'name'  =>  'phoneNoThreePerson',
                'credentialType'    =>  'phoneNoThreePersonType',
                'credentialNo'  =>  'phoneNoThreePersonID'
            ],
            'SecurityCardNo'    =>  'securityCardNo',
            'entranceTypeCode'  =>  'entranceTypeCode'
        ];
        $return = [];
        foreach ($params as $key => $value){
            if(!is_array($value)){
                $return[$map[$key]] = $value;
            }else{
                foreach ($value as $key2 => $value2){
                    if($key2 == 'IDPic'){
                        continue;
                    }
                    $return[$map[$key][$key2]] = $value2;
                }
            }
        }
        return $return;
    }


    /**
     * 后台获取用户列表
     */
    public function getlist(){
        $people = new People();
        $list = $people->order('peopleId','desc')->select();
        $peopleList = [];
        $peopleType = People::getPeopleTypeCodeMap();
        $peopleEntranceType = People::getEntranceTypeCodeMap();
        $peopleCredentialType = People::getCredentialTypeMap();
        $peopleHouseType = PeopleHouseRelation::getRelationMap();
        $summary = [
            'jzcount' => 0,
            'hjcount' => 0,
            'lhcount' => 0,
            'wjcount' => 0
        ];
        foreach ($list as $l){
            $row = [];
            $row['peopleId'] = $l->peopleId;
            $row['peopleName'] = $l->peopleName;
            $row['credentialNo'] = $l->credentialNo ?substr($l->credentialNo, 0, 3).'********'.substr($l->credentialNo, -4,4) : '';
            $row['phoneNoOne'] = $l->phoneNoOne ? substr_replace($l->phoneNoOne, '****', 3, 5) : '';
            $row['phoneNoTwo'] = $l->phoneNoTwo ? substr_replace($l->phoneNoTwo, '****', 3, 5) : '';
            $row['phoneNoThree'] = $l->phoneNoThree ? substr_replace($l->phoneNoThree, '****', 3, 5) : '';
            $row['peopleType'] = isset($peopleType[$l->peopleTypeCode]) ? $peopleType[$l->peopleTypeCode] : $l->peopleTypeCode;
            $row['picUrl'] = $l->domicileIDPicUrl;
            if($l->peopleTypeCode == 1){
                $summary['hjcount'] ++;
            }elseif ($l->peopleTypeCode == 2){
                $summary['lhcount'] ++;
            }else if($l->peopleTypeCode == 3){
                $summary['wjcount'] ++;
            }
            $row['updateTime'] = $l->updateTime;
            $row['credentialType'] = isset($peopleCredentialType[$l->credentialType]) ? $peopleCredentialType[$l->credentialType] : $l->credentialType;
            $row['domicileAddress'] = $l->domicileAddress; // 户籍地址
            $row['residenceAddress'] = $l->residenceAddress; //居住地址
            $row['entranceType'] = isset($peopleEntranceType[$l->entranceTypeCode]) ? $peopleEntranceType[$l->entranceTypeCode] : $l->entranceTypeCode;
            $row['houseList'] = [];
            $relation = new PeopleHouseRelation();
            $relations = $relation->leftJoin('building b','b.buildingId = peoplehouseRelation.buildingId')
                ->leftJoin('house h','h.houseId = peoplehouseRelation.houseId')
                ->where('peopleId',$l->peopleId)->select();
            foreach ($relations as $r){
                $hl = [];
                $hl['buildingNo'] = $r->buildingNo;
                $hl['houseNo'] = $r->houseNo;
                $hl['relation'] = isset($peopleHouseType[$r->housePeopleRel]) ? $peopleHouseType[$r->housePeopleRel] : $r->housePeopleRel;
                $row['houseList'][] = $hl;
            }
            $peopleList[] = $row;
        }
        $summary['totalcount'] = count($peopleList);
        return json([
            'code' => 200,
            'message' => 'success',
            'result' => [
                'list'=>$peopleList,
                'summary' => $summary
            ]
        ]);
    }

    private function publishMqtt($topic,$param){
        try{
//            $server = "192.168.31.69";     // change if necessary
            $server = "127.0.0.1";     // change if necessary
            $port = 1883;                     // change if necessary
            $username = "guest";                   // set your username
            $password = "guest";                   // set your password
            $client_id = uniqid("servicedata-publisher"); // make sure this is unique for connecting to sever - you could use uniqid()
//$client_id ="ClientID".rand();
            $mqtt = new phpMQTT($server, $port, $client_id);

            if ($mqtt->connect(true, NULL, $username, $password)) {
                $mqtt->publish($topic, $param, 0);
                $mqtt->close();
                return true;
            } else {
                return false;
            }
        }catch (Exception $exception){
            return false;
        }
    }
}