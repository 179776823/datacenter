<?php
namespace app\base\controller;

class User
{
    public function add()
    {
        $param = request()->param();
        print_r($param);die;
        echo input('id');
        echo "hello";
    }

}
