<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function saveImage($image,$type,$isfile = 0){
    $file_name = md5(makeRandomNum(6).time()).'.jpg';
    $imgDir = $type;
    $filePath = '/agbox/imgs/'.$imgDir.'/';
    if(!file_exists($filePath)){
       mkdir($filePath,777,true);
    }
    $filePath = $filePath.$file_name;
    $data = '';
    if($isfile == 1){
        $tmp_file = $image['tmp_name'];
        $error = $image['error'];
        if($error == 0){
            move_uploaded_file($tmp_file, $filePath);
            $data = file_get_contents($filePath);
        }
    }else{
        $data = $image;
    }
    if($data){
        $newFile = fopen($filePath,"w"); //打开文件准备写入

        fwrite($newFile,$data); //写入二进制流到文件

        fclose($newFile); //关闭文件

        //保存地址
        return getImageUrl($imgDir.'/'.$file_name);
    }else{
        return '';
    }
}

function getImgByUrl($url,$type){
    try{
        $file_name = md5(makeRandomNum(6).time()).'.jpg';
        $imgDir = $type;
        $filePath = '/agbox/imgs/'.$imgDir.'/';
        if(!file_exists($filePath)){
            mkdir($filePath,777,true);
        }
        $filePath = $filePath.$file_name;
        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 3
            )
        );
        $context  = stream_context_create($opts);
        $data = file_get_contents($url,false,$context);
        if($data){
            $newFile = fopen($filePath,"w"); //打开文件准备写入
            fwrite($newFile,$data); //写入二进制流到文件
            fclose($newFile); //关闭文件
            //保存地址
            return getImageUrl($imgDir.'/'.$file_name);
        }else{
            return '';
        }
    }catch (Exception $exception){
        return '';
//        return $exception->getMessage();
    }
}


function saveBase64Image($image,$pictype){
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $image, $result)){
        $type = $result[2];
        $image = str_replace($result[1], '', $image);
    }else{
        $type = 'jpg';
    }
    $file_name = md5(makeRandomNum(6).time()).'.'.$type;
    $imgDir = $pictype;
    $filePath = '/agbox/imgs/'.$imgDir.'/'.$file_name;
    if (file_put_contents($filePath, base64_decode($image))){
        if(!file_exists($filePath)){
            return false;
        }else{
            return getImageUrl($imgDir.'/'.$file_name);
        }
    }
}

function getImageUrl($url){
    return '/image/get?name='.$url;
}

function makeRandomNum($n = 16)
{
    $s = '';
    $str = "0123456789";
    $len = strlen($str) - 1;
    for ($i = 0; $i < $n; $i++) {
        $s .= $str[rand(0, $len)];
    }
    return $s;
}

function uuid($prefix = '')
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}

/**
 * 保存车牌
 * @param $saveKey
 * @param $type
 * @param $bounarysplit
 * @return string
 */
function saveParkingImage($saveKey,$type,$bounarysplit) {
    if(!$bounarysplit){
        return '';
    }
    foreach ($_FILES as $key => $value) {
        if (!is_array($value['type'])) {
            $body_part = "name=$key";
            $body_part .= "=====";
            $body_part .= file_get_contents($value['tmp_name']);
            $body = explode($bounarysplit,$body_part);
            if(isset($body[1])){
                $insertBody = explode('Content-Type: image/jpeg',$body[1]);
                $file_name = md5(makeRandomNum(6).time()).'.jpg';
                $imgDir = $type;
                $filePath = '/agbox/imgs/'.$imgDir.'/';
                if(!file_exists($filePath)){
                    mkdir($filePath,777,true);
                }
                $filePath = $filePath.$file_name;
                $newFile = fopen($filePath,"w"); //打开文件准备写入

                fwrite($newFile,trim($insertBody[1])); //写入二进制流到文件

                fclose($newFile); //关闭文件

                //保存地址
                return getImageUrl($imgDir.'/'.$file_name);
            }else{
                return '';
            }
        }
    }
}

function send_post(string $url,$port=80, $post_data,$header = '',$needJson = false,$needDigest = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    if($needJson){
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    }else{
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    if($header){
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
    }
    if($needDigest){
        $username = Config::get('digest_username');
        $pwd = Config::get('digest_password');
        curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$pwd);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    }
    curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);//设置超时时间为1s
    $data = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        //throw new Exception($data, $httpCode);
    }

    curl_close($ch);
    return $data;
}


/**
 * digest认证
 * @param string $url
 * @param int $port
 * @param array $post_data
 * @param string $header
 * @param bool $needJson
 */
function send_Digest_post($host,$uri,$port=80, array $post_data,$header = '',$needJson = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host.$uri);
    curl_setopt($ch, CURLOPT_POST, 1);
    if($needJson){
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    }else{
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    if($header){
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);//设置超时时间为1s
    curl_setopt($ch, CURLOPT_HEADER, 1);

    $first_response = curl_exec($ch);
//    $info = curl_getinfo($ch);

    preg_match('/WWW-Authenticate: Digest (.*)/', $first_response, $matches);
    if(!empty($matches))
    {
        $username = Config::get('digest_username');
        $password = Config::get('digest_password');
        $auth_header = $matches[1];
        $auth_header_array = explode(',', $auth_header);
        $parsed = array();

        foreach ($auth_header_array as $pair)
        {
            $vals = explode('=', $pair);
            $parsed[trim($vals[0])] = trim($vals[1], '" ');
        }

        $response_realm     = (isset($parsed['realm'])) ? $parsed['realm'] : "";
        $response_nonce     = (isset($parsed['nonce'])) ? $parsed['nonce'] : "";
        $response_opaque    = (isset($parsed['opaque'])) ? $parsed['opaque'] : "";
        $response_cnonce    = (isset($parsed['cnonce'])) ? $parsed['cnonce'] : "";

        $authenticate1 = md5($username.":".$response_realm.":".$password);
        $authenticate2 = md5("POST:".$uri);

        $authenticate_response = md5($authenticate1.":".$response_nonce.":00000001:".$response_cnonce.":auth:".$authenticate2);

        $request = sprintf('Authorization: Digest username="%s", realm="%s", nonce="%s", qop=auth, nc=00000001, opaque="%s", uri="%s", algorithm="MD5", cnonce="%s", response="%s"',
            $username, $response_realm, $response_nonce, $response_opaque, $uri, $response_cnonce, $authenticate_response);

        $request_header = array($request);
        $request_header[] = 'Content-Type:application/json';
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $host.$uri);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch,CURLOPT_TIMEOUT, 30);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_PORT, $port);
        if($needJson){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);

        $data = curl_exec($ch);
//        $info = curl_getinfo($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            //throw new Exception($data, $httpCode);
        }
        curl_close($ch);
        return $data;
//        $result['response']         = curl_exec($ch);
//        $result['info']             = curl_getinfo ($ch);
//        $result['info']['errno']    = curl_errno($ch);
//        $result['info']['errmsg']   = curl_error($ch);
//        print_r($result);die;
    }
}

function send_get($url, $params = [])
{
    if($params){
        $query_string = http_build_query($params);
        $url .= '?' . $query_string;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode != 200) {
        //throw new Exception($data, $httpCode);
    }
    curl_close($ch);
    return $data;
}

function base64EncodeImage ($image_file) {
    $base64_image = '';
//    $image_info = getimagesize($image_file);
    $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
   // $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
    $base64_image = chunk_split(base64_encode($image_data));
    return $base64_image;
}

function formartStr($str,$total){
    if(strlen($str) == $total){
        return $str;
    }
    if(strlen($str)>$total){
        return substr($str,0,$total);
    }
    $num_len = strlen($str);
    $str2='';
    for ($i = $num_len;$i < $total;$i++){
        $str2 .= "0";
    }
    $str = $str2.$str;
    return $str;
}