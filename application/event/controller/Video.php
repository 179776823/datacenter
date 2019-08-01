<?php
/**
 * 视频安防监控接收
 */
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午12:21
 */

namespace app\event\controller;

use app\event\model\VideoSecurityEvent;
use app\event\model\HeartbeatVideo;
use app\event\model\EventImage;
use think\Exception;
use think\worker\Server;
use think\facade\Config;
use GatewayClient\Gateway;

class Face extends Server
{

    public function __construct()
    {
        $this->protocol = 'http';
        $this->host = config('worker.tcpIp');
        $this->port = '5302';
        parent::__construct();
    }

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        $post = $data['post'];
        if($post){
            try {
                $imageInsert = array();
                $insert = $post;
                if($insert){
                    $insert['deviceId'] = model('Device')->getDeviceIdByCode($insert['deviceCode']);
                    if(!$insert['deviceId']){
                        $connection->send('failed:该设备无权限');
                    }else{
                        if(isset($insert['heartTime'])){
                            $insert['online'] = 1;
                            $insert['shield'] = 2;
                            // 插入心跳数据
                            $isInsert = model('HeartbeatVideo')->saveData($insert);
                            if($isInsert){
                                $connection->send('success');
                            }else{
                                $connection->send('failed');
                            }
                        }else{
                            if(isset($insert['picData'])){
                                $imageInsert['picData'] = $insert['picData'];
                                // 保存base64图片
                                $imgUrl = saveBase64Image($insert['picData'],'video');
                                if($imgUrl){
                                    $insert['localPicUrl'] = $imgUrl;
                                }
                            }
                            unset($insert['picData']);
                            $insert['createTime'] = $insert['updateTime'] = date('Y-m-d H:i:s');
                            // 插入报警数据
                            $event = VideoSecurityEvent::create($insert);
                            $eventId = $event->id;
                            if($eventId){
                                $imageInsert['event_type'] = 2;
                                $imageInsert['event_id'] = $eventId;
                                $imageInsert['createTime'] = $imageInsert['updateTime'] = date('Y-m-d H:i:s');
                                EventImage::create($imageInsert);
                                // 模拟客户端发送给gatewayServer
                                //Gateway::sendToUid('1','来了个报警！！');
                                Gateway::sendToAll('视频安防报警来了');
                                $connection->send('success');
                            }else{
                                $connection->send('failed');
                            }
                        }
                    }
                }
            }catch (Exception $exception){
                $connection->send('failed:'.$exception->getMessage());
            }
        }else{
            $connection->send('no data');
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {

    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {

    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
    }
}