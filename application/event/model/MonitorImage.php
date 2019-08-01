<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\event\model;

use think\Exception;
use think\Model;

class MonitorImage extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'monitorImage';
    protected $pk = 'id';


    public static function saveAllImages($imgs,$eventId,$eventType){
        $insert = array();
        foreach ($imgs as $img){
            $row = array();
            $imgUrl = saveBase64Image($img,'monitor');
            if($imgUrl){
                $row['picData'] = $img;
                $row['picUrl'] = $imgUrl;
                $row['event_id'] = $eventId;
                $row['event_type'] = $eventType;
                $row['createTime'] = $row['updateTime'] = date('Y-m-d H:i:s');
            }
            if($row){
                $insert[] = $row;
            }
        }
        if($insert){
            $monitor = new MonitorImage();
            $monitor->saveAll($insert);
        }
    }
}