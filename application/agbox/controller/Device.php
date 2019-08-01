<?php
/**
 *
 */
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午12:21
 */

namespace app\agbox\controller;

use app\agbox\model\DeviceServer;
use app\agbox\model\FaceIdentificationEvent;
use app\agbox\model\AccessControlEvent;
use app\agbox\model\HeartbeatParking;
use app\agbox\model\NvrDevice;
use app\agbox\model\ParkingEvent;
use app\agbox\model\Devices;
use app\agbox\model\PerceptinEvent;
use app\agbox\model\UsbEvent;
use Bluerhinos\phpMQTT;
use think\Cache;
use think\Db;
use think\Exception;
use think\facade\Config;
use think\facade\Env;
use think\worker\Server;

class Device
{
    public function test()
    {
        $registerData['RegisterObject']['DeviceID'] = '111111111';
        $policeHost = Config::get('police_host');
        $port = Config::get('police_host_port');
        if ($registerData) {
            // 注册
            $registerUrl = $policeHost . '/VIID/System/Register';
            $result = send_Digest_post($registerUrl, $port, $registerData, [], true);
            var_dump($result);die;
        }
    }

    public function testself(){
        $registerUrl = 'http://127.0.0.1:3000/testauth';
        $result = send_Digest_post($registerUrl, 3000, [], [], true);
        var_dump($result);die;
    }
    /**
     * 被动抓拍
     * @param $connection
     * @param $data
     */
    public function snap()
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params'])?$json['params']:[];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        //获取服务器信息
        $server = DeviceServer::ServerIsRegister();
        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,1,$channel);
            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }

                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $device->deviceCode;
            }
            if(isset($params['target'])){
                $params['target'] = json_encode($params['target']);
            }
            if(isset($params['picUrl'])){
                $imgUrl = getImgByUrl($params['picUrl'], 'snap');
                if ($imgUrl) {
                    unset($params['eventPic']);
                    $params['eventPicUrl'] = $imgUrl;
                }
            }else if(isset($params['eventPic'])) {
                $picKey = $params['eventPic'];
                // 上传图片
                $pic = isset($post[$picKey]) ? $post[$picKey]: '';
                if ($pic) {
                    // 保存base64图片
                    $imgUrl = saveImage($pic, 'snap');
                    if ($imgUrl) {
                        unset($params['eventPic']);
                        $params['eventPicUrl'] = $imgUrl;
                    }
                }else{
                    if($_FILES){
                        $picKey = $params['eventPic'];
                        // 上传图片
                        $pic = isset($_FILES[$picKey]) ? $_FILES[$picKey] : '';
                        if($pic){
                            // 保存base64图片
                            $imgUrl = saveImage($pic,'snap',1);
                            if($imgUrl){
                                $params['eventPicUrl'] = $imgUrl;
                            }
                        }
                    }
                }
            }
            // 上传报警信息
            $params['createTime'] = date('Y-m-d H:i:s');
            if(isset($params['note'])){
                if(is_array($params['note'])){
                    $params['note'] = json_encode($params['note']);
                }
            }
            if(isset($params['note'])){
                if(is_array($params['note'])){
                    $params['note'] = json_encode($params['note']);
                }
            }
            if(array_key_exists('prepersonId',$params)){
                unset($params['prepersonId']);
            }
            if(array_key_exists('feature',$params)){
                unset($params['feature']);
            }

            if (array_key_exists('scene',$params)){
                //1是小图，0是全景图
                $params['sub'] = $params['scene'] ? 0:1;
            }
            $event = FaceIdentificationEvent::create($params);
            $event = FaceIdentificationEvent::find($event->id);
            $eventId = $event->id;
            if($eventId){
                $this->publishMqtt('event/snap/#',json_encode(['method'=>'event','id'=>$eventId]));
                // 查询同一个抓拍大小图
                $sub_pic = $event->sub==1 ? 0:1;
                $event2 = FaceIdentificationEvent::where('deviceId',$event->deviceId)->where('triggerTime',$event->triggerTime)->where('issend',0)->where('sub',$sub_pic)->order('id','desc')->find();
                if(!$event2){
                    $result = ['result'=>'success'];
                    return json($result);
                }
                $picUrl = $event->eventPicUrl;
                $picUrl2 = $event2->eventPicUrl;
                $data = '';
                $data2 = '';
                if($picUrl){
                    $picSrc = substr($picUrl,16);
                    $data = base64EncodeImage('/agbox/imgs/'.$picSrc);
                }

                if($picUrl2){
                    $picSrc2 = substr($picUrl2,16);
                    $data2 = base64EncodeImage('/agbox/imgs/'.$picSrc2);
                }

                $imgHost = Config::get('img_host');
                //新增判断是否nvr设备事件
                $sourceID = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event->id,5);
                $sourceID2 = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event2->id,5);

                $subImageList[] = [
                    'Data'=>$data,
                    'ImageID' => $sourceID,
                    'DeviceID'=>$event->deviceCode,
                    'StoragePath' => $imgHost.$picUrl,
                    'Type' => $event->sub==1 ? '11':'14',
                    'FileFormat' => 'jpg',
                    'EventSort'=>10,
                    'ShotTime' => date('YmdHis',strtotime($event->triggerTime))
                ];

                $subImageList[] = [
                    'Data'=>$data2,
                    'ImageID' => $sourceID2,
                    'DeviceID'=>$event2->deviceCode,
                    'StoragePath' => $imgHost.$picUrl2,
                    'Type' => $event2->sub==1 ? '11':'14',
                    'FileFormat' => 'jpg',
                    'EventSort'=>10,
                    'ShotTime' => date('YmdHis',strtotime($event2->triggerTime))
                ];

                $faceId = $sourceID.'06'.formartStr($event->id,5);
                $send = [
                    'FaceID' => $faceId,
                    'InfoKind' => '1',
                    'SourceID'  =>  $sourceID,
                    'DeviceID' => $event->deviceCode,
                    'SubImageList' => ['SubImageInfoObject' => $subImageList ],
                ];
                // 推给公安平台
                $lastSend['FaceListObject']['FaceObject'][] = $send;
                $registerData  = [];
                if($server->isRegister != 1){
                    //不使用设备号，改为使用服务器编号
                    $registerData['RegisterObject']['DeviceID'] = $server->serverCode;
                }
                $this->pushToPolice($registerData,$lastSend,'face',$server);
                //发送成功改变事件的发送状态
                $event->issend =1;
                $event2->issend =1;
                $event->save();
                $event2->save();
                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getevent'){
            // 拉取报警信息
            $result = [
                'jsonrpc'=>$jsonrpc,
                'result'=>['eventInfo'=>[]],
                'id'=>$id
            ];
            $eventId = $params['eventId'];
            if($eventId){
                $info = FaceIdentificationEvent::get($eventId)->toArray();
                if($info){
                    $info['deviceId'] = $info['deviceCode'];
                    $result['result']['eventInfo'] = $info;
                    return json($result);
                }else{
                    return json($result);
                }
            }
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',1)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }


//            $face = new FaceIdentificationEvent();
//            $result = $face->field('deviceId')
//                ->where('lat', 'not null')
//                ->where('lon','not null')
//                ->where('deviceId','not null')
//                ->group('deviceId')
//                ->select();
//            if($result){
//                foreach ($result as $k => $v){
//                    $deviceId = $v->deviceId;
//                    $res = FaceIdentificationEvent::where('deviceId','=',$deviceId)
//                        ->where('lat', 'not null')
//                        ->where('lon','not null')
//                        ->find();
//                    if($res){
//                        $list[] = [
//                            'deviceId' => $deviceId,
//                            'triggerTime' => date('Y-m-d H:i:s'),
//                            'shield' => false,
//                            'note' => 'abc',
//                            'gis' => [
//                                'lon'=>$res->lon,
//                                'lat'=>$res->lat,
//                                'alt' => $res->alt ? $res->alt : 1,
//                                'gisType' => $res->gisType ? $res->gisType : 1,
//                                'floor'=>$res->floor ? $res->floor : 1
//                            ]
//                        ];
//                    }
//                }
//            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }else if($method == 'updateheart'){
            // 心跳
            $deviceCode = $params['deviceId'];
            $searchDevice = new Devices();
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,1,$channel);
            if($server){
                if($server->isRegister == 1){
                    // 发送心跳给公安
                    $this->pushHeart($server->serverCode,$server);
                }
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>'success',
                'id'=>123
            ]);
        }
    }

    /**
     * 出入口
     * @param $connection
     * @param $data
     */
    public function entrance()
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params']) ? $json['params'] : '';
        //获取服务器信息
        $server = DeviceServer::ServerIsRegister();
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];
            $searchDevice = new Devices();
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,4,$channel);
            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }
                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $device->deviceCode;
            }

            if(isset($params['eventPic'])){
                $picKey = $params['eventPic'];
                // 上传图片
                $pic = isset($post[$picKey]) ? $post[$picKey]: '';
//                trace($pic);
                if($pic){
                    // 保存base64图片
                    $imgUrl = saveImage($pic,'entrance');
                    if($imgUrl){
                        $params['eventPicUrl'] = $imgUrl;
                        unset($params['eventPic']);
                    }
                }else{
                    if($_FILES){
                        $picKey = $params['eventPic'];
                        // 上传图片
                        $pic = isset($_FILES[$picKey]) ? $_FILES[$picKey] :'';
                        if($pic){
                            // 保存base64图片
                            $imgUrl = saveImage($pic,'entrance',1);
                            if($imgUrl){
                                $params['eventPicUrl'] = $imgUrl;
                                unset($params['eventPic']);
                            }
                        }
                    }
                }
            }
            // 上传报警信息
            $params['createTime'] = date('Y-m-d H:i:s');
            $event = AccessControlEvent::create($params);
            $event = AccessControlEvent::find($event->id);
            $eventId = $event->id;
            if($eventId){
                $this->publishMqtt('event/entrance/#',json_encode(['method'=>'event','id'=>$eventId]));

                $picUrl = $event->eventPicUrl;
                $data = '';
                if($picUrl){
                    $picSrc = substr($picUrl,16);
                    $data = base64EncodeImage('/agbox/imgs/'.$picSrc);
//                    list($width,$height) = getimagesize('/agbox/imgs/'.$picSrc);
                }
                $imgHost = Config::get('img_host');
                $sourceID = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event->deviceId,5);
                $subImageList[] = [
                    'Data'=>$data,
                    'ImageID' => $sourceID,
                    'DeviceID'=>$event->deviceCode,
                    'StoragePath' => $imgHost.$picUrl,
                    'Type' => '11',
                    'FileFormat' => 'jpg',
                    'EventSort'=>10,
                    'ShotTime' => date('YmdHis',strtotime($event->triggerTime))
                ];
                $faceId = $sourceID.'06'.formartStr($event->id,5);
                $send = [
                    'FaceID' => $faceId,
                    'InfoKind' => '1',
                    'SourceID'  =>  $sourceID,
                    'DeviceID' => $event->deviceCode,
                    'SubImageList' => ['SubImageInfoObject' => $subImageList ],
                ];
                // 推给公安平台
                $lastSend['FaceListObject']['FaceObject'][] = $send;
                $registerData  = [];
                if($server->isRegister != 1){
                    $registerData['RegisterObject']['DeviceID'] = $server->serverCode;
                }
                $this->pushToPolice($registerData,$lastSend,'face',$server);

                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getevent'){
            // 拉取报警信息
            $result = [
                'jsonrpc'=>$jsonrpc,
                'result'=>['eventInfo'=>[]],
                'id'=>$id
            ];
            $eventId = $params['eventId'];
            if($eventId){
                $info = AccessControlEvent::get($eventId)->toArray();
                if($info){
                    $info['credentialType'] = $info['certifiedType'];
                    $info['credentialNo'] = $info['certifiedNo'];
                    $info['certifiedTypeCode'] = $info['certifiedTypeCode'] ? $info['certifiedTypeCode'] : 0;
                    $info['deviceId'] = $info['deviceCode'];
                    $result['result']['eventInfo'] = $info;
                    return json($result);
                }else{
                    return json($result);
                }
            }
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',4)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }

//            $list = [];
//            $face = new AccessControlEvent();
//            $result = $face->field('deviceId')
//                ->where('lat', 'not null')
//                ->where('lon','not null')
//                ->where('deviceId','not null')
//                ->group('deviceId')
//                ->select();
//            if($result){
//                foreach ($result as $k => $v){
//                    $deviceId = $v->deviceId;
//                    $res = AccessControlEvent::where('deviceId','=',$deviceId)
//                        ->where('lat', 'not null')
//                        ->where('lon','not null')
//                        ->find();
//                    if($res){
//                        $list[] = [
//                            'deviceId' => $deviceId,
//                            'triggerTime' => date('Y-m-d H:i:s'),
//                            'shield' => false,
//                            'note' => 'abc',
//                            'gis' => [
//                                'lon'=>$res->lon,
//                                'lat'=>$res->lat,
//                                'alt' => $res->alt ? $res->alt : 1,
//                                'gisType' => $res->gisType ? $res->gisType : 1,
//                                'floor'=>$res->floor ? $res->floor : 1
//                            ]
//                        ];
//                    }
//                }
//            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }else if($method == 'updateheart'){
            $deviceCode = $params['deviceId'];
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,4);
            if($server){
                if($server->isRegister == 1){
                    // 发送心跳给公安
                    $this->pushHeart($server->serverCode,$server);
                }
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>'success',
                'id'=>123
            ]);
        }
    }

    /**
     * 车辆抓拍
     * @param $connection
     * @param $data
     */
    public function parking()
    {
        $bounarysplit = '';
        $contentType = request()->header('content-type');
        $bounary = explode('boundary=',$contentType);
        if($bounary && count($bounary) > 1){
            $bounarysplit = $bounary[1];
        }
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params']) ? $json['params'] : '';
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];

        //获取服务器信息
        $server = DeviceServer::ServerIsRegister();
        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];

            $searchDevice = new Devices();
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,5,$channel);

            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }

                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $device->deviceCode;
            }
            $params['carCode'] = isset($params['carType']) ? $params['carType']: $params['carCode'];

            // 图片特殊的处理
            if(isset($params['platePic'])) {
                $picKey = $params['platePic'];
                $imgUrl = saveParkingImage($picKey,'parking',$bounarysplit);
                if($imgUrl){
                    $params['platePic'] = $imgUrl;
                }
            }

            if(isset($params['eventPic'])){
                $picKey = $params['eventPic'];
                // 上传图片
                $pic = isset($post[$picKey]) ? $post[$picKey]: '';
                if($pic){
                    // 保存base64图片
                    $imgUrl = saveImage($pic,'parking');
                    if($imgUrl){
                        $params['eventPicUrl'] = $imgUrl;
                        unset($params['eventPic']);
                    }
                }else{
                    if($_FILES){
                        $picKey1 = $params['eventPic'];
                        $picKey2 = str_replace('.jpg','_jpg',$params['eventPic']) ;
                        // 上传图片
                        if(array_key_exists($picKey1,$_FILES)){
                            $picKey = $picKey1;
                        }else{
                            $picKey = $picKey2;
                        }
                        // 上传图片
                        $pic = isset($_FILES[$picKey]) ? $_FILES[$picKey] : '';
                        if($pic){
                            // 保存base64图片
                            $imgUrl = saveImage($pic,'parking',1);
                            if($imgUrl){
                                $params['eventPicUrl'] = $imgUrl;
                                unset($params['eventPic']);
                            }
                        }
                    }
                }
            }

            if(isset($params['platePic'])){
                $picKey = $params['platePic'];
                // 上传图片
                $pic = isset($post[$picKey]) ? $post[$picKey]: '';
                if($pic){
                    // 保存base64图片
                    $imgUrl = saveImage($pic,'parking');
                    if($imgUrl){
                        $params['platePic'] = $imgUrl;
//                        unset($params['platePic']);
                    }
                }else{
                    if($_FILES){
                        $picKey1 = $params['platePic'];
                        $picKey2 = str_replace('.jpg','_jpg',$params['platePic']) ;
                        // 上传图片
                        if(array_key_exists($picKey1,$_FILES)){
                            $picKey = $picKey1;
                        }else{
                            $picKey = $picKey2;
                        }
                        // 上传图片
                        $pic = isset($_FILES[$picKey]) ? $_FILES[$picKey] : '';
                        if($pic){
                            // 保存base64图片
                            $imgUrl = saveImage($pic,'parking',1);
                            if($imgUrl){
                                $params['platePic'] = $imgUrl;
//                                unset($params['platePic']);
                            }
                        }
                    }
                }
            }
            // 上传报警信息
            $params['createTime'] = date('Y-m-d H:i:s');
            $event = ParkingEvent::create($params);
            $event = ParkingEvent::find($event->id);
            $eventId = $event->id;
            if($eventId){
                $this->publishMqtt('event/parking/#',json_encode(['method'=>'event','id'=>$eventId,'code'=>5]));

                $imgHost = Config::get('img_host');
                //新增判断是否nvr设备发送的事件
                $sourceID = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event->deviceId,5);
                //车牌图片来源标识
                $sourceID2 = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event->deviceId-1,5);

                $motorVehicleID = $sourceID.'02'.formartStr($event->id,5);

                $picUrl = $event->eventPicUrl;
                $platepicUrl = $event->platePic;//车牌图片
                $data = '';
                $plate_data = '';
                if($picUrl){
                    $picSrc = substr($picUrl,16);
                    $data = base64EncodeImage('/agbox/imgs/'.$picSrc);
                }
                //获取车牌图片二进制数据
                if($platepicUrl){
                    $platepicSrc = substr($platepicUrl,16);
                    $plate_data = base64EncodeImage('/agbox/imgs/'.$platepicSrc);
                }
                // 车牌小图的数据详情
                $subImageList[] = [
                    'Data'=>$plate_data,
                    'ImageID' => $sourceID2,
                    'StoragePath' => $imgHost.$platepicUrl,
                    'Type' => '02',
                    'FileFormat' => 'jpg',
                    'ShotTime' => date('YmdHis',strtotime($event->triggerTime))
                ];
                $subImageList[] = [
                    'Data'=>$data,
                    'ImageID' => $sourceID,
                    'StoragePath' => $imgHost.$picUrl,
                    'Type' => '14',
                    'FileFormat' => 'jpg',
                    'ShotTime' => date('YmdHis',strtotime($event->triggerTime))
                ];
                $send = [
                    'MotorVehicleID' => $motorVehicleID,
                    'InfoKind' => '1',
                    'SourceID'  => $sourceID,
                    'DeviceID' => $event->deviceCode,
                    'StorageUrl1' => $event->eventPicUrl ? $imgHost.$event->eventPicUrl : '',// 场景图url
                    'HasPlate' => $event->plateNo ? 1 : 2,
                    'PlateClass' => $event->plateCode,
                    'PlateNo' => $event->plateNo,
                    'VehicleColor' => '',
                    'PassTime' => date('YmdHis',strtotime($event->triggerTime)),
                    'SubImageList' => ['SubImageInfoObject' => $subImageList ]
                ];
                // 推给公安平台
                $lastSend['MotorVehicleListObject']['MotorVehicleObject'][] = $send;

                $registerData  = [];
                if($server->isRegister != 1){
                    //注册使用服务器编码
                    $registerData['RegisterObject']['DeviceID'] = $server->serverCode;
                }
                $this->pushToPolice($registerData,$lastSend,'vehicle',$server);

                $result = [
                    'jsonrpc'=>"2.0",
                    'result'=>'success',
                    'id'=>$eventId
                ];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getevent'){
            // 拉取报警信息
            $result = [
                'jsonrpc'=>$jsonrpc,
                'result'=>['eventInfo'=>[]],
                'id'=>$id
            ];
            $eventId = $params['eventId'];
            if($eventId){
                $info = ParkingEvent::get($eventId)->toArray();
                if($info){
                    $info['deviceId'] = $info['deviceCode'];
                    $result['result']['eventInfo'] = $info;
                    return json($result);
                }else{
                    return json($result);
                }
            }
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',5)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'name'  => $r->parking->parkName,
                        'totalSpace' => $r->parking->parkNum,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }

//            $list = [];
//            $face = new ParkingEvent();
//            $result = $face->field('deviceId')
//                ->where('lat', 'not null')
//                ->where('lon','not null')
//                ->where('deviceId','not null')
//                ->group('deviceId')
//                ->select();
//            if($result){
//                foreach ($result as $k => $v){
//                    $deviceId = $v->deviceId;
//                    $res = ParkingEvent::where('deviceId','=',$deviceId)
//                        ->where('lat', 'not null')
//                        ->where('lon','not null')
//                        ->find();
//                    if($res){
//                        $list[] = [
//                            'deviceId' => $deviceId,
//                            'triggerTime' => date('Y-m-d H:i:s'),
//                            'shield' => false,
//                            'note' => 'abc',
//                            'gis' => [
//                                'lon'=>$res->lon,
//                                'lat'=>$res->lat,
//                                'alt' => $res->alt ? $res->alt : 1,
//                                'gisType' => $res->gisType ? $res->gisType : 1,
//                                'floor'=>$res->floor ? $res->floor : 1
//                            ]
//                        ];
//                    }
//                }
//            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }else if($method == 'geteventcode'){
            $list = ParkingEvent::getEventTypeMap();
            $eventCode = [];
            foreach ($list as $k => $v){
                $eventCode[] = [
                    'code' => $k,
                    'name' => $v
                ];
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['eventCode'=>$eventCode],
                'id'=>123
            ]);
        }else if($method == 'updateheart'){
            $deviceCode = $params['deviceId'];
            $searchDevice = new Devices();
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,5,$channel);
            if($device){
                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $deviceCode;
            }else{
                $params['deviceId'] = 0;
                $params['deviceCode'] = $deviceCode;
            }
            $params['parkingName'] = isset($params['name']) ? $params['name'] : '';
            $params['totalParkingNumber'] = isset($params['totalSpace']) ? $params['totalSpace'] : 0;
            $params['remainingParkingNumber'] = isset($params['leftSpace']) ? $params['leftSpace'] : 0;
            $params['lon'] = isset($params['gis']['lon']) ? $params['gis']['lon'] : 0;
            $params['lat'] = isset($params['gis']['lat']) ? $params['gis']['lat'] : 0;
            $params['alt'] = isset($params['gis']['alt']) ? $params['gis']['alt'] : 0;
            $params['floor'] = isset($params['gis']['floor']) ? $params['gis']['floor'] : 0;
            $params['heartTime'] = date('Y-m-d H:i:s');
            $params['online'] = 1;
            unset($params['gis']);
            unset($params['name']);
            unset($params['totalSpace']);
            unset($params['leftSpace']);
            HeartbeatParking::create($params);
            if($server && $server->isRegister == 1){
                $this->pushHeart($server->serverCode,$server);
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>'success',
                'id'=>123
            ]);
        }
    }

    /**
     * usb防插拔
     * @return \think\response\Json
     */
    public function usbalarm()
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params']) ? $json['params'] : '';
        $id = isset($json['id']) ? $json['id']:0;
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];

        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,3);
            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }
                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $deviceCode;
            }
            $params['eventType'] = isset($params['eventCode']) ? $params['eventCode'] : 0 ;
            $params['cardNo'] =  isset($params['icId']) ? $params['icId'] : '';
            // 上传报警信息
            $params['createTime'] = $params['dataTime'] = date('Y-m-d H:i:s');
            unset($params['eventCode']);

            $event = UsbEvent::create($params);
            $eventId = $event->id;
            if($eventId){
                if($params['eventType'] == 2){
                    $this->publishMqtt('event/usbalarm/'.$deviceCode,json_encode([
                        'method'=>'event',
                        'channel'=>$params['channel'],
                        'eventCode'=>2,
                        'triggerTime'=>isset($params['triggerTime']) ? $params['triggerTime'] : date('Y-m-d H:i:s'),
                        'icId' => isset($params['icId']) ? $params['icId'] : ''
                    ]));
                }else{
                    $this->publishMqtt('event/usbalarm/'.$deviceCode,json_encode([
                        'method'=>'event',
                        'channel'=>$params['channel'],
                        'eventCode'=>$params['eventType'],
                        'triggerTime'=>isset($params['triggerTime']) ? $params['triggerTime'] : date('Y-m-d H:i:s')
                    ]));
                }
                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',3)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }
    }

    /**
     * 泛感知
     * @return \think\response\Json
     */
    public function perception()
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params'])?$json['params']:[];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,8);
            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }
                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $deviceCode;
            }
            // 上传报警信息
            $params['createTime'] = date('Y-m-d H:i:s');
            $params['eventType'] = $params['eventCode'];
            $event = PerceptinEvent::create($params);
            $eventId = $event->id;
            if($eventId){
                $this->publishMqtt('event/perception/#',json_encode([
                    'method'=>'event',
                    'id'=>$eventId,
                    'channel'=>$params['channel'],
                    'eventCode'=>$params['eventCode'],
                    'triggerTime' => $params['triggerTime'],
                    'note' => $params['note']
                ]));
                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getevent'){
            // 拉取报警信息
            $result = [
                'jsonrpc'=>$jsonrpc,
                'result'=>['eventInfo'=>[]],
                'id'=>$id
            ];
            $eventId = $params['eventId'];
            if($eventId){
                $info = PerceptinEvent::get($eventId)->toArray();
                if($info){
                    $info['deviceId'] = $info['deviceCode'];
                    $result['result']['eventInfo'] = $info;
                    return json($result);
                }else{
                    return json($result);
                }
            }
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',8)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }else if($method == 'geteventcode'){
            $list = PerceptinEvent::getEventTypeMap();
            $eventCode = [];
            foreach ($list as $k => $v){
                $eventCode[] = [
                    'code' => $k,
                    'name' => $v
                ];
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['perceptionEventCode'=>$eventCode],
                'id'=>123
            ]);
        }
    }


    /**
     * 获取事件列表
     * @return \think\response\Json
     */
    public function getEventList(){
        $post = request()->param();
        if(!isset($post['type'])){
            return json([
                'code'=>'500',
                'message'=>'参数错误'
            ]);
        }
        $type = $post['type'];
        $deviceCode = isset($post['deviceCode']) ? $post['deviceCode'] : 0;
        $deviceId = isset($post['deviceId']) ? $post['deviceId'] : 0;
        $list = [];
        if($type == 'snap'){
            $eventMap = FaceIdentificationEvent::getEventTypeMap();

            $snap = new FaceIdentificationEvent();
            if($deviceId){
                $list = $snap->where('deviceId',$deviceId)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }else if($deviceCode){
                $list = $snap->where('deviceCode',$deviceCode)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }

        }elseif($type == 'entrance'){
            $eventMap = AccessControlEvent::getEventTypeMap();

            $entrance = new AccessControlEvent();
            if($deviceId){
                $list = $entrance->where('deviceId',$deviceId)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }elseif($deviceCode){
                $list = $entrance->where('deviceCode',$deviceCode)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }

        }elseif($type == 'parking'){
            $eventMap = ParkingEvent::getEventTypeMap();

            $parking = new ParkingEvent();
            if($deviceId){
                $list = $parking->where('deviceId',$deviceId)
                    ->limit(100)
                    ->order('id','desc')
                    ->select();
            }else if($deviceCode){
                $list = $parking->where('deviceCode',$deviceCode)
                    ->limit(100)
                    ->order('id','desc')
                    ->select();
            }
        }elseif($type == 'usb'){
            $eventMap = UsbEvent::getEventTypeMap();

            $usb = new UsbEvent();
            if($deviceId){
                $list = $usb->where('deviceId',$deviceId)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }elseif($deviceCode){
                $list = $usb->where('deviceCode',$deviceCode)
                    ->limit(100)
                    ->order('id', 'desc')
                    ->select();
            }

        }
        foreach ($list as &$l){
            if($type == 'snap'){
                $l['note'] = '';
            }
//            $l['deviceCode'] = $l['deviceId'];
            $l['picUrl'] = isset($l['eventPicUrl']) ? $l['eventPicUrl'] : '';
            if($eventMap){
                if($l['eventType']){
                    $l['eventType'] = isset($eventMap[$l['eventType']]) ?  $eventMap[$l['eventType']] : $l['eventType'];
                }elseif($l['eventCode']){
                    $l['eventType'] = isset($eventMap[$l['eventCode']]) ?  $eventMap[$l['eventCode']] : $l['eventCode'];
                }
            }
//            $l['deviceId'] = 0;
        }
        return json([
            'code'=>200,
            'message'=>'success',
            'list' => $list
        ]);
    }

    /**
     * 获取设备列表
     * $deviceType 1-人脸抓拍 2-视频安防监控 3-USB防插拔 4-出入口控制 5-停车库 6-入侵和紧急报警 7-实时电子巡检 8-状态感知检测 9-状态采集检测
     */
    public function getList(){
        $post = request()->param();
        if(!isset($post['type'])){
            return json([
                'code'=>'500',
                'message'=>'参数错误'
            ]);
        }
        $type = $post['type'];
        if($type == 'snap'){
            $deviceType = 1;
        }elseif($type == 'entrance'){
            $deviceType = 4;
        }elseif($type == 'parking'){
            $deviceType = 5;
        }elseif($type == 'usb'){
            $deviceType = 3;
        }elseif($type == 'perceptin'){
            $deviceType = 8;
        }
        $device = new Devices();
        $list = $device->where('state',1)
            ->where('deviceType',$deviceType)
//            ->order('deviceId','desc')
            ->select();

        $newList = [];
        foreach($list as $key=>$device){
            $nvr = null;
            if($device->nvrId >0){
                $nvr = Db::table('nvr_device')->where('nvrId',$device->nvrId)->find();
            }
            if($deviceType == 5){
                if($device->parking){
                    if($device->deviceId){
                        $heart = HeartbeatParking::where('deviceId',$device->deviceId)
                            ->order('id','desc')
                            ->limit(1)
                            ->find();
                    }

                    $row = [
                        'deviceId' => $device->deviceId,
                        'deviceCode' => $device->deviceCode,
                        'deviceNumber' => $device->deviceNumber,
                        'address'   => $device->parking->address,
                        'parkName' => $device->parking->parkName,
                        'parkNum' => $device->parking->parkNum,
                        'note'  =>  $device->note,
                        'lat'   => $device->lat ? $device->lat : $device->parking->lat,
                        'lon'   =>  $device->lon ? $device->lon : $device->parking->lon,
                        'alt'   =>  $device->alt ? $device->alt : $device->parking->alt,
                        'gisType'   =>  $device->gisType ? $device->gisType - 1 : 0,
                        'isCloudDevice' => $device->isCloudDevice,
                        'isHidden' => $device->isHidden,
                        'floor' => $device->floor ? $device->floor : $device->parking->floor,
                        'triggerTime' => $heart ? $heart->heartTime:'',
                        'channel' => $device->channel,
                        'nvrCode'=>$nvr ? $nvr['nvrCode']:'',
                        'serverType'=>$device->serverType
                    ];
                    $newList[] = $row;
                }
            }else{
                $row = [
                    'deviceId' => $device->deviceId,
                    'deviceCode' => $device->deviceCode,
                    'deviceNumber' => $device->deviceNumber,
                    'note'  =>  $device->note,
                    'lat'   => $device->lat,
                    'lon'   =>  $device->lon,
                    'alt'   =>  $device->alt,
                    'gisType'   =>  $device->gisType ? $device->gisType - 1 : 0,
                    'isHidden' => $device->isHidden,
                    'floor' => $device->floor,
                    'channel' => $device->channel,
                    'nvrCode'=>$nvr ? $nvr['nvrCode']:'',
                    'serverType'=>$device->serverType
                ];
                $newList[] = $row;
            }
        }


        return json([
            'code'=>200,
            'message'=>'success',
            'list' => $newList
        ]);
    }

    /**
     * 人脸抓拍
     * @return \think\response\Json
     */
    public function capture()
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
        if($key!=Config::get('data_key')){
            $result = ['result'=>'failed','message'=>'key is error'];
            return json($result);
        }
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params'])?$json['params']:[];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        //获取服务器信息
        $server = DeviceServer::ServerIsRegister();
        if($method == 'addevent'){
            $deviceCode = $params['deviceId'];
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,1,$channel);
            if(!$device){
                $result = ['result'=>'failed','message'=>'no device'];
                return json($result);
            }else{
                if($device->isHidden == 1){
                    // 已屏蔽
                    $result = ['result'=>'failed','message'=>'device is hidden'];
                    return json($result);
                }

                $params['deviceId'] = $device->deviceId;
                $params['deviceCode'] = $device->deviceCode;
            }
            if(isset($params['target'])){
                $params['target'] = json_encode($params['target']);
            }
            if(isset($params['picUrl'])){
                $imgUrl = getImgByUrl($params['picUrl'], 'snap');
                if ($imgUrl) {
                    unset($params['eventPic']);
                    $params['eventPicUrl'] = $imgUrl;
                }
            }else if(isset($params['eventPic'])) {
                $picKey = $params['eventPic'];
                // 上传图片
                $pic = isset($post[$picKey]) ? $post[$picKey]: '';
//                trace($pic);
                if ($pic) {
                    // 保存base64图片
                    $imgUrl = saveImage($pic, 'snap');
                    if ($imgUrl) {
                        unset($params['eventPic']);
                        $params['eventPicUrl'] = $imgUrl;
                    }
                }else{
                    if($_FILES){
                        $picKey1 = $params['eventPic'];
                        $picKey2 = str_replace('.jpg','_jpg',$params['eventPic']) ;
                        // 上传图片
                        if(array_key_exists($picKey1,$_FILES)){
                            $picKey = $picKey1;
                        }else{
                            $picKey = $picKey2;
                        }
                        $pic = isset($_FILES["$picKey"]) ? $_FILES["$picKey"] : '';
                        if($pic){
                            // 保存base64图片
                            $imgUrl = saveImage($pic,'snap',1);
                            if($imgUrl){
                                $params['eventPicUrl'] = $imgUrl;
                            }
                        }
                    }
                }
            }
            // 上传报警信息
            $params['createTime'] = date('Y-m-d H:i:s');
            if(isset($params['note'])){
                if(is_array($params['note'])){
                    $params['note'] = json_encode($params['note']);
                }
            }
            if(array_key_exists('prepersonId',$params)){
                unset($params['prepersonId']);
            }
            if(array_key_exists('feature',$params)){
                unset($params['feature']);
            }

            if (array_key_exists('scene',$params)){
                //1是小图，0是全景图
                $params['sub'] = $params['scene'] ? 0:1;
            }
            //此处注意要修改表中字段personCode 为varchar(64)
            unset($params['eventPic']);
            unset($params['scene']);
            unset($params['notify']);
            $event = FaceIdentificationEvent::create($params);
            $event = FaceIdentificationEvent::find($event->id);
            $eventId = $event->id;
            if($eventId){
                $this->publishMqtt('event/snap/#',json_encode(['method'=>'event','id'=>$eventId]));
                // 查询同一个抓拍大小图
                $sub_pic = $event->sub==1 ? 0:1;
                $event2 = FaceIdentificationEvent::where('deviceId',$event->deviceId)->where('triggerTime',$event->triggerTime)->where('sub',$sub_pic)->where('issend',0)->order('id','desc')->find();
                if(!$event2){
                    $result = ['result'=>'success'];
                    return json($result);
                }
                $picUrl = $event->eventPicUrl;
                $picUrl2 = $event2->eventPicUrl;
                $data = '';
                $data2 = '';
                if($picUrl){
                    $picSrc = substr($picUrl,16);
                    $data = base64EncodeImage('/agbox/imgs/'.$picSrc);
                }
                if($picUrl2){
                    $picSrc2 = substr($picUrl2,16);
                    $data2 = base64EncodeImage('/agbox/imgs/'.$picSrc2);
                }
                $imgHost = Config::get('img_host');
                $sourceID = formartStr($event->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event->id,5);
                $sourceID2 = formartStr($event2->deviceCode,20).'02'.date('YmdHis',strtotime($event->triggerTime)).formartStr($event2->id,5);

                $subImageList[] = [
                    'Data'=>$data,
                    'ImageID' => $sourceID,
                    'DeviceID'=> $event->deviceCode,
                    'StoragePath' => $imgHost.$picUrl,
                    'Type' => $event->sub==1 ? '11':'14',
                    'FileFormat' => 'jpg',
                    'EventSort'=>10,
                    'ShotTime' => date('YmdHis',strtotime($event->triggerTime))
                ];

                $subImageList[] = [
                    'Data'=>$data2,
                    'ImageID' => $sourceID2,
                    'DeviceID'=>$event2->deviceCode,
                    'StoragePath' => $imgHost.$picUrl2,
                    'Type' => $event2->sub==1 ? '11':'14',
                    'FileFormat' => 'jpg',
                    'EventSort'=>10,
                    'ShotTime' => date('YmdHis',strtotime($event2->triggerTime))
                ];

                $faceId = $sourceID.'06'.formartStr($event->id,5);
                $send = [
                    'FaceID' => $faceId,
                    'InfoKind' => '1',
                    'SourceID'  =>  $sourceID,
                    'DeviceID' => $event->deviceCode,
                    'SubImageList' => ['SubImageInfoObject' => $subImageList],
                ];
                // 推给公安平台
                $lastSend['FaceListObject']['FaceObject'][] = $send;
                $registerData  = [];
                if($server->isRegister != 1){
                    $registerData['RegisterObject']['DeviceID'] = $server->serverCode;
                }
                $this->pushToPolice($registerData,$lastSend,'face',$server);
                $event->issend =1;
                $event2->issend =1;
                $event->save();
                $event2->save();
                $result = ['result'=>'success'];
            }else{
                $result = ['result'=>'failed'];
            }
            return json($result);
        }else if($method == 'getevent'){
            // 拉取报警信息
            $result = [
                'jsonrpc'=>$jsonrpc,
                'result'=>['eventInfo'=>[]],
                'id'=>$id
            ];
            $eventId = $params['eventId'];
            if($eventId){
                $info = FaceIdentificationEvent::get($eventId)->toArray();
                if($info){
                    $info['deviceId'] = $info['deviceCode'];
                    $result['result']['eventInfo'] = $info;
                    return json($result);
                }else{
                    return json($result);
                }
            }
        }else if($method == 'getdevicelist'){
            $list = [];
            $devices = new Devices();
            $result = $devices->where('deviceType',1)
                ->select();
            if($result){
                foreach ($result as $r){
                    $list[] = [
                        'deviceId' => $r->deviceCode,
                        'triggerTime' => date('Y-m-d H:i:s'),
                        'shield' => $r->isHidden == 1 ? true : false,
                        'note' => $r->note ? $r->note : 'abc',
                        'gis' => [
                            'lon'=>$r->lon,
                            'lat'=>$r->lat,
                            'alt' => $r->alt ? $r->alt : 1,
                            'gisType' => $r->gisType ? $r->gisType : 1,
                            'floor'=>$r->floor ? $r->floor : 1
                        ]
                    ];
                }
            }


//            $face = new FaceIdentificationEvent();
//            $result = $face->field('deviceId')
//                ->where('lat', 'not null')
//                ->where('lon','not null')
//                ->where('deviceId','not null')
//                ->group('deviceId')
//                ->select();
//            if($result){
//                foreach ($result as $k => $v){
//                    $deviceId = $v->deviceId;
//                    $res = FaceIdentificationEvent::where('deviceId','=',$deviceId)
//                        ->where('lat', 'not null')
//                        ->where('lon','not null')
//                        ->find();
//                    if($res){
//                        $list[] = [
//                            'deviceId' => $deviceId,
//                            'triggerTime' => date('Y-m-d H:i:s'),
//                            'shield' => false,
//                            'note' => 'abc',
//                            'gis' => [
//                                'lon'=>$res->lon,
//                                'lat'=>$res->lat,
//                                'alt' => $res->alt ? $res->alt : 1,
//                                'gisType' => $res->gisType ? $res->gisType : 1,
//                                'floor'=>$res->floor ? $res->floor : 1
//                            ]
//                        ];
//                    }
//                }
//            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>['deviceList'=>$list],
                'id'=>123
            ]);
        }else if($method == 'updateheart'){
            // 心跳
            $deviceCode = $params['deviceId'];
            $channel = isset($params['channel']) ? $params['channel'] : 0;
            $searchDevice = new Devices();
            $device = $searchDevice->getDeviceByCodeAndType($deviceCode,1,$channel);
            if($server){
                if($server->isRegister == 1){
                    // 发送心跳给公安
                    $this->pushHeart($server->serverCode,$server);
                }
            }
            return json([
                'jsonrpc'=>'2.0',
                'result'=>'success',
                'id'=>123
            ]);
        }
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

    private function pushHeart($serverCode,$server,$time=0){

        $policeHost = Config::get('police_host');
        $port = Config::get('police_host_port');
        $url = $policeHost.'/VIID/System/Keepalive';

        if($policeHost){
            // 推给公安平台
            $lastSend['KeepaliveObject']['DeviceID'] = $serverCode;
            $header[] = 'User-Identify:'.$serverCode;
            $result = send_post($url,$port,$lastSend,$header,true,false);
            $result = json_decode($result,true);
            if(!$result || ($result && $result['ResponseStatusObject']['StatusCode'] != 0)){
                $server->isRegister=0;
                $server->updateTime = date('Y-m-d H:i:s',time());
                $server->save();
                if($time==0){
                    $time = time();
                }
                $this->againRegister($result,$serverCode,$server,$time);
            }

        }

    }

    private function pushToPolice($registerData = [],$eventData,$pushType,$server)
    {
        $policeHost = Config::get('police_host');
        if (!$policeHost || !$pushType) {
            return false;
        }
        $port = Config::get('police_host_port');
        $url = '';
        if ($registerData) {
            // 注册
            $uri = '/VIID/System/Register';
            $result = send_Digest_post($policeHost,$uri, $port, $registerData, '', true);
            if($result){
                $result = json_decode($result,true);
                if($result && $result['ResponseStatusObject']['StatusCode'] == 0){
                    // 成功
                    if($server){
                        $server->isRegister = 1;
                        $server->updateTime = date('Y-m-d H:i:s',time());
                        $server->save();
                        //根据again判断是否直接返回
                        if($pushType=='again'){
                            return true;
                        }
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }
        }
        if ($pushType == 'face') {
            $url = $policeHost . '/VIID/Faces';
        } else if ($pushType == 'vehicle') {
            $url = $policeHost . '/VIID/MotorVehicles';
        }
        if($url){
            $header[] = 'User-Identify:'.$server->serverCode;
            send_post($url,$port,$eventData,$header,true);
        }
    }

    //$resutl 保活返回结果，根据状态判断是否需要再次注册，然后再次保活一次
    private function againRegister($result,$serverCode,$server,$time){
        if(time()-$time>3){
            return true;
        }
        if($result && $result['ResponseStatusObject']['StatusCode'] != 0){
            // 未注册,先去注册
            $registerData  = [];
            $registerData['RegisterObject']['DeviceID'] = $serverCode;

            //pushType类型again，在后面用作判断是否还往下执行，此类型就不需要往下执行了,返回一个true
            $second_result = $this->pushToPolice($registerData,'','again',$server);
            if($second_result){
                $this->pushHeart($serverCode,$server);
            }else{
                return false;
            }
        }else{
            return true;
        }

    }
    
}