<?php
namespace app\agbox\controller;

use app\agbox\model\Car;
use think\Exception;

class Share
{
    /**
     * 更新车辆信息
     * @param $connection
     * @param $data
     */
    public function car()
    {
        $post = request()->param();
        $key = $post['key'];
        $json = $post['json'];
        $json = json_decode($json,true);
        $method = $json['method'] ? strtolower($json['method']) : '';
        $params = $json['params'];
        $id = $json['id'];
        $jsonrpc = isset($json['json-rpc']) ? $json['json-rpc'] : $json['jsonrpc'];
        if($method == 'updateplate') {
            $insert = [];
            if (isset($params['set'])) {
                $insert['set'] = json_encode($params['set']);
            }
            $map = $this->getCarMap();
            foreach ($params as $key => $value){
                if(isset($map[$key])){
                    if($key == 'enable'){
                        $insert[$map[$key]] = $value == true ? 1 :0;
                    }else{
                        $insert[$map[$key]] = $value;
                    }
                }
            }
            $insert['createTime'] = date('Y-m-d H:i:s');
            $eventId = Car::createOrUpdate($insert);
            if ($eventId) {
                $result = ['result' => 'success'];
            } else {
                $result = ['result' => 'failed'];
            }
            return json($result);
        }else if($method == 'getcartypecode'){
            $type = Car::getTypeMap();
            $cartype = [];
            foreach ($type as $key => $v){
                $cartype[] = [
                    'code'=>$key,
                    'name'=>$v
                ];
            }

            return json([
                'jsonrpc'=>'2.0',
                'result'=>['carType'=>$cartype],
                'id'=>123
            ]);
        }else if($method =='getplatecolorcode'){
            $type = Car::getPlateCarColorMap();
            $platecartype = [];
            foreach ($type as $key => $v){
                $platecartype[] = [
                    'code'=>$key,
                    'name'=>$v
                ];
            }

            return json([
                'jsonrpc'=>'2.0',
                'result'=>['plateColor'=>$platecartype],
                'id'=>123
            ]);
        }else if($method =='getplatetypecode'){
            $type = Car::getPlateCodeMap();
            $platecartype = [];
            foreach ($type as $key => $v){
                $platecartype[] = [
                    'code'=>$key,
                    'name'=>$v
                ];
            }

            return json([
                'jsonrpc'=>'2.0',
                'result'=>['plateCode'=>$platecartype],
                'id'=>123
            ]);
        }
    }

    private function getCarMap(){
        $map = [
            'plateNo' => 'plateNo',
            'plateTypeCode' => 'plateType',
            'carTypeCode'  => 'carType',
            'credentialType' => 'credentialType',
            'credentialNo'  => 'credentialNo',
            'enable' => 'enable'
        ];
        return $map;
    }
}