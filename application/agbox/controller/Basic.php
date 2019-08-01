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


class Basic
{
    /**
     * 被动抓拍
     * @param $connection
     * @param $data
     */
    public function index()
    {
        $post = request()->param();
        $json = $post['json'];
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = isset($json['params'])?$json['params']:[];
//        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'getgistype'){
            $gisTypeList = [
                ['code'=>1,'name'=>'WGS84'],
                ['code'=>2,'name'=>'CGCS2000'],
                ['code'=>3,'name'=>'BD09'],
                ['code'=>4,'name'=>'GCJ02'],
                ['code'=>5,'name'=>'西安80'],
                ['code'=>6,'name'=>'北京54'],
                ['code'=>7,'name'=>'其他'],
            ];
            return json(
                [
                    'jsonrpc' => '2.0',
                    'result' => ['gisType' => $gisTypeList]
                ]
            );
        }
    }

}