<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2019/01/02
 * Time: 上午12:21
 */

namespace app\agbox\controller;

use app\agbox\model\AccessControlEvent;
use app\agbox\model\FaceIdentificationEvent;
use app\agbox\model\ParkingEvent;
use app\agbox\model\People;

use Bluerhinos\phpMQTT;
use think\Exception;
use think\facade\Config;

class Schedule
{
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

    /**
     * 获取黑名单的mqtt列表
     */
    public function mqttBlackList(){
        $people = new People();
        $list = $people->where('isblack',1)->order('peopleId','desc')->select();
        foreach ($list as $l){
            $mqttdata = [
                'count' => 0,
                'personCode' => $l->peopleCode,
                'notify' => false,
                'note' =>$l->peopleName,
                'filename' => $l->peopleName.'.jpg',
                'groupName'=>'',
                'url' => Config::get('img_host').$l->domicileIDPicUrl,
                'personType' => $l->peopleTypeCode,
                'no' => $l->peopleId
            ];
            $source = 'blacklist';
            $totalcount = People::getPeopleCount(2);
            $typecount = People::getPeopleCount(2,$l->peopleTypeCode);

            $mqttdata['count'] = $totalcount;
            $this->publishMqtt('person/'.$source.'/#',json_encode($mqttdata));

            $mqttdata['count'] = $typecount;
            $this->publishMqtt('person/'.$source.'/'.$l->peopleTypeCode,json_encode($mqttdata));
        }
    }

    /**
     * 清除parking事件数据
     */
    public function clearParkingEvent(){
        $id = 0;
        $date = date('Y-m-d 00:00:00',strtotime('-30 days'));
        while (1){
            $event = new ParkingEvent();
            $query = $event->where('id','>',$id)->where('triggerTime','<',$date)->order('id','asc')->limit(100);
            $select = $query->select();
            if($select->isEmpty() == 1){
                break;
            }
            foreach ($select as $s){
                $id = $s->id;
            }
            $query->delete();
        }
        echo '执行结束';
    }

    /**
     * 出入口
     */
    public function clearAccessControlEvent(){
        $id = 0;
        $date = date('Y-m-d 00:00:00',strtotime('-30 days'));
        while (1){
            $event = new AccessControlEvent();
            $query = $event->where('id','>',$id)->where('triggerTime','<',$date)->order('id','asc')->limit(100);
            $select = $query->select();
            if($select->isEmpty() == 1){
                break;
            }
            foreach ($select as $s){
                $id = $s->id;
            }
            $query->delete();
        }
        echo '执行结束';
    }

    /**
     * 人脸识别
     */
    public function clearFaceEvent(){
        $id = 0;
        $date = date('Y-m-d 00:00:00',strtotime('-30 days'));
        while (1){
            $event = new FaceIdentificationEvent();
            $query = $event->where('id','>',$id)->where('triggerTime','<',$date)->order('id','asc')->limit(100);
            $select = $query->select();
            if($select->isEmpty() == 1){
                break;
            }
            foreach ($select as $s){
                $id = $s->id;
            }
            $query->delete();
        }
        echo '执行结束';
    }
}