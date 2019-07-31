<?php
/**
 * 人脸识别tcp接收
 */
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午12:21
 */

namespace app\event\controller;

use app\event\model\FaceIdentificationEvent;
use app\event\model\HeartbeatFace;
use think\Exception;
use think\worker\Server;
use think\facade\Config;
use GatewayClient\Gateway;

class Face extends Server
{

    public function __construct()
    {
        $this->protocol = 'tcp';
        $this->host = config('worker.tcpIp');
        $this->port = '5301';
        parent::__construct();
    }

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        try {
            // 检测信息是否完整
            $frist = substr( $data, 0, 1 );
            $last = substr($data,strlen($data)-1,1);
            if($frist == '[' && $last == ']'){
                // 报警信息
                $data = substr(trim($data),1,-1);
            }else{
                // 上传脸部识别图片
                $imgUrl = saveImage($data,'face');
                $connection->send($imgUrl);
                Gateway::sendToAll($imgUrl);
                return;
            }
            $insert = array();
            $explode = explode(",",$data);
            foreach ($explode as $row){
                $keyword = explode(":",$row);
                if(count($keyword) == 2){
                    $insert[$keyword[0]] =  iconv("gbk", "utf-8//ignore", $keyword[1]);
                }else{
                    $realValue = '';
                    foreach ($keyword as $k => $v){
                        if($k != 0){
                            $realValue .= ':'.$v;
                        }
                    }
                    $insert[$keyword[0]] = substr($realValue,1,strlen($realValue)-1);
                }
            }
            if($insert){
                $insert['deviceId'] = model('Device')->getDeviceIdByCode($insert['deviceCode']);
                if(!$insert['deviceId']){
                    $connection->send('failed:该设备无权限');
                }else{
                    if(isset($insert['heartTime'])){
                        // 插入心跳数据
                        $isInsert = model('HeartbeatFace')->saveData($insert);
                        if($isInsert){
                            $connection->send('success');
                        }else{
                            $connection->send('failed');
                        }
                    }else{
                        $insert['createTime'] = $insert['updateTime'] = date('Y-m-d H:i:s');
                        // 插入报警数据
                        $event = new FaceIdentificationEvent;
                        $isInsert = $event->save($insert);
                        if($isInsert){
                            // 模拟客户端发送给gatewayServer
                            //Gateway::sendToUid('1','来了个报警！！');
                            Gateway::sendToAll('111');
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