<?php
namespace app\agbox\model;

use think\Model;

class Companies extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'company';
    protected $pk = 'companyId';

    public function getListByPager($villageId,$page,$limit = 10){
        $offset = ($page-1) * $limit;
        $list = self::where('villageId', $villageId)->limit($offset,$limit)->select();
        $count = self::where('villageId', $villageId)->count();
        return array($count,$list);
    }

}