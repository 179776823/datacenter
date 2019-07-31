<?php
/**
 * Created by PhpStorm.
 * User: anna
 * Date: 2018/7/10
 * Time: 上午12:20
 */
#!/usr/bin/env php
namespace think;

define('APP_PATH', __DIR__ . '/application/');

// 加载基础文件
require __DIR__ . '/thinkphp/base.php';
require_once __DIR__ . '/vendor/autoload.php';


// 执行应用并响应
Container::get('app',[APP_PATH])->bind('event/Face')->run()->send();