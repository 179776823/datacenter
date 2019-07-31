<?php

namespace app\agbox\controller;
use app\agbox\model\User as UserModel;
use think\Db;
use think\facade\Request;

class User
{
    public function login(){
        //  判断请求是否是 POST 请求
        if ( Request::isPost() ){
            //  获取用户登录时候的用户名、密码
            $username = request()->param('username');
            $password = request()->param('password');

            $result = UserModel::login($username,$password);
            /**
             * 通过获得的条件去进行不同的操作
             */
            switch ($result){
                case 10000:
                    $result = ['result'=>'success','message'=>'登录成功'];
                    return json($result);
                    break;

                case 10001:
                    $result = ['result'=>'fail','message'=>'用户不存在'];
                    return json($result);
                    break;

                case 10002:
                    $result = ['result'=>'fail','message'=>'密码错误'];
                    return json($result);
                    break;
            }
        }else{
            $result = ['result'=>'fail','message'=>'登录失败'];
            return json($result);
        }
    }

    public function updateUser(){
        //  判断请求是否是 POST 请求
        if ( Request::isPost() ){
            //  获取用户登录时候的用户名、密码
            $username = request()->param('username');
            $password = request()->param('password');
            if(empty($username) || empty($password)){
                $result = ['result'=>'fail','message'=>'用户名或密码不能为空'];
                return json($result);
            }
            $salt = makeRandomNum(4);
            $password_md = md5($password .$salt);
            $user = UserModel::find();
            if($user){
                $user->username = $username;
                $user->password = $password_md;
                $user->salt = $salt;
                $user->save();
                $result = ['result'=>'success','message'=>'修改成功'];
            }else{
                $result = ['result'=>'fail','message'=>'修改失败'];
            }
            return json($result);
        }else{
            $user = Db::table('user')->where('id',1)->field('username,id')->find();
            $result = ['result'=>$user];
            return json($result);
        }
    }
}