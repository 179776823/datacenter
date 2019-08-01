<?php
namespace app\base\controller;

class Image
{
    public function get()
    {
        $name = input('name');
        $file_path = '/agbox/imgs/'.$name;
        if(file_exists($file_path)){
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $file_path);
            header('Content-type: '.$mimetype);
            echo file_get_contents($file_path);
        }else{
            echo 'no such img';
        }
//        $imagedata=file_get_contents($file_path);
//        $data=getimagesizefromstring($imagedata);
//        if(!$data){
//            exit('图片无效');
//        }
//        header("content-type:".$data['mime']);
//        ob_clean();
//        echo $imagedata;
    }

}
