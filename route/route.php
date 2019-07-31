<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

Route::rule('user/add','base/user/add');
Route::rule('image/get','base/image/get');
Route::rule('api/device/getList','agbox/device/getList');
Route::rule('api/device/getEventList','agbox/device/getEventList');
Route::rule('api/import/addDevice','agbox/import/addDevice');
Route::rule('api/import/updateDevice','agbox/import/updateDevice');
Route::rule('api/import/delDevice','agbox/import/delDevice');
Route::rule('api/person/getlist','agbox/person/getlist');
Route::rule('api/cars/getlist','agbox/cars/getlist');
Route::rule('api/cars/getCarsEvent','agbox/cars/getCarsEvent');
Route::rule('api/village/getBuildingList','agbox/village/getBuildingList');
Route::rule('api/village/getHouseList','agbox/village/getHouseList');
Route::rule('api/base/delHouse','agbox/base/delHouse');
Route::rule('api/base/delBuilding','agbox/base/delBuilding');
Route::rule('api/base/addBuilding','agbox/base/addBuilding');
Route::rule('api/base/addHouse','agbox/base/addHouse');

Route::rule('api/user/login','agbox/user/login');
Route::rule('api/user/edit','agbox/user/updateUser');

return [

];
