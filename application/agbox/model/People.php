<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class People extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'people';
    protected $pk = 'peopleId';

    public static function createOrUpdate($params){
        if($params){
            $people = People::where('credentialType', $params['credentialType'])
                ->where('credentialNo',$params['credentialNo'])
                ->find();
            if($people){
                People::update($params,['credentialType'=>$params['credentialType'],'credentialNo'=>$params['credentialNo']]);
                return $people->peopleId;
            }else{
                $params['peopleCode'] = uuid();
                $people = self::create($params);
                return $people->peopleId;
            }
        }else{
            return 0;
        }
    }

    public static function getPeopleTypeCodeMap(){
        return [
            1 => '户籍人员',
            2 => '来沪人员',
            3 => '境外人员',
            4 => '外来服务'
        ];
    }

    public static function getEntranceTypeCodeMap(){
        return [
            1 => '住户人员',
            2 => '租户人员',
            3 => '亲情人员',
            4 => '住户服务',
            5 => '访客人员',
            6 => '快递人员',
            7 => '外卖人员',
            8 => '小区保安人员',
            9 => '小区物业人员',
            10 => '小区工作人员',
            11 => '小区服务人员',
            12 => '其他类型人员'
        ];
    }

    public static function getCredentialTypeMap(){
        return  [
            111 =>'身份证',
            114 =>'军官证',
            152 =>'临时出入证',
            131 =>'工作证',
            516 =>'港澳同胞回乡证',
            511 =>'台湾居民来往大陆通行证',
            414 =>'普通护照',
            554 =>'外国人居留证',
            990 =>'其他'
        ];
    }

    public function getListByPager($villageCode,$buildingCode,$houseCode,$page,$pagesize = 10){
        $select = self::join('peoplehouseRelation','peoplehouseRelation.peopleId = people.peopleId')
            ->join('building','building.buildingId = peoplehouseRelation.buildingId')
            ->join('house','house.houseId = peoplehouseRelation.houseId');
        if($villageCode){
            $select->where('villageCode',$villageCode);
        }
        if($buildingCode){
            $select->where('buildingCode',$buildingCode);
        }
        if($houseCode){
            $select->where('houseCode',$houseCode);
        }
        $count = $select->count();
        $offset = ($page-1)*$pagesize;
        $list = $select->limit($offset,$pagesize);
        return array($count,$list);
    }

    /**
     * @param $type 1 白名单 2 黑名单
     */
    public static function getPeopleCount($type,$personType = '')
    {

        if ($type == 1) {
            $select = self::where('isblack', 0);
            if($personType){
                $select->where('peopleTypeCode',$personType);
            }
            return $select->count();
        } else if($type == 2){
            $select = self::where('isblack', 1);
            if($personType){
                $select->where('peopleTypeCode',$personType);
            }
            return $select->count();
        }
    }
}