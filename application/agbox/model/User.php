<?php


namespace app\agbox\model;


use think\Model;

class User extends Model
{
    protected $table='user';
    /**
     * 登录操作
     * @param $username     $string 用户名
     * @param $password     $password 密码
     */
    public static function login($username,$password){
        $userData = self::where('username','eq',$username) ->find();

        //  如果查找到数据
        if ($userData){
            //  获取该用户信息对应的盐值
            $salt = $userData['salt'];
            //  将密码进行 MD5 加密，将其与数据库中的密码字符串作对比，看看匹不匹配
            $newPassword = md5($password.$salt);
            if ( $userData['password'] == $newPassword ){
                return 10000;
            }else{
                return 10002;    //  密码错误
            }
        }else{
            return 10001;    //  该用户不存在
        }
    }
}