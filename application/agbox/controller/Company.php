<?php
namespace app\agbox\controller;

use app\agbox\model\Companies;
use app\agbox\model\Villages;
use think\Exception;
use think\facade\Config;

class Company
{
    /**
     * 获取单位列表
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
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'getlist'){
            $page = isset($params['page']) ? $params['page'] : 1;
            $villageId = isset($params['villageId']) ? $params['villageId'] : 1;
            $pagesize = isset($params['pagesize'])?$params['pagesize'] : 1;
            $companies = new Companies();
            $villageCode = '';
            list($total,$list) = $companies->getListByPager($villageId,$page,$pagesize);
            if($total > 0){
                $village = Villages::where('villageId',$villageId)->find();
                if($village){
                    $villageCode = $village->villageCode;
                }else{
                    $result = ['result'=>'failed','message'=>'no village'];
                    return json($result);
                }
            }
            $result = [
                'page' => $page,
                'page_size' => $pagesize,
                'total_page' => ceil($total/$pagesize),
                'count' => $total,
                'villageCode' => $villageCode,
                'companyList' => []
            ];
            if($list){
                foreach ($list as &$l){
                    $l['companyID'] = $l['companyId'];
                    $l['gis'] = [
                        'lon' => $l['lon'],
                        'lat' => $l['lat'],
                        'alt' => $l['alt'],
                        'gisType' => $l['gisType'],
                        'floor' => $l['floor']
                    ];
                }
            }
            $result['companyList'] = $list;
            return json($result);

        }
    }


}