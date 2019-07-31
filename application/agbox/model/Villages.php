<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午1:31
 */
namespace app\agbox\model;

use think\Model;

class Villages extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'village';
    protected $pk = 'villageId';

    public function getListByPager($page,$limit = 10){
        $offset = ($page-1) * $limit;
        $list = self::limit($offset,$limit)->select();
        $count = self::count();
        return array($count,$list);
    }
}