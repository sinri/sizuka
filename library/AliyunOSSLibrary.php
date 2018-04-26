<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:28
 */

namespace sinri\sizuka\library;

use OSS\Core\OssException;
use OSS\OssClient;
use sinri\enoch\core\LibLog;
use sinri\sizuka\Sizuka;

class AliyunOSSLibrary
{
    protected $oss;
    protected $bucket;

    /**
     * AliyunOSSLibrary constructor.
     * @param null $bucket
     * @throws OssException
     */
    public function __construct($bucket = null)
    {
        $accessKeyId = Sizuka::config(['oss', 'AccessKeyId']);//"<您从OSS获得的AccessKeyId>";
        $accessKeySecret = Sizuka::config(['oss', 'AccessKeySecret']);//"<您从OSS获得的AccessKeySecret>";
        $endpoint = Sizuka::config(['oss', 'endpoint']);//"<您选定的OSS数据中心访问域名，例如http://oss-cn-hangzhou.aliyuncs.com>";
        $this->oss = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        if (!$this->oss) {
            throw new OssException("oss client failed to be created");
        }
        if ($bucket === null) {
            $bucket = Sizuka::config(['oss', 'bucket']);
        }
        $this->bucket = $bucket;
    }

    /**
     * @param $object
     * @return bool
     */
    public function doesObjectExist($object)
    {
        try {
            return $this->oss->doesObjectExist($this->bucket, $object);
        } catch (\Exception $exception) {
            echo __METHOD__ . ' error: ' . $exception->getMessage();
            return false;
        }
    }

    /**
     * @param $object
     * @param int $timeout
     * @return bool|string
     */
    public function objectDownloadURL($object, $timeout = 3600)
    {
        try {
            $signedUrl = $this->oss->signUrl($this->bucket, $object, $timeout);
            return $signedUrl;
        } catch (OssException $e) {
            //echo __METHOD__.' error: '.$e->getMessage();
            return false;
        }
    }

    public function listObjects($prefix = '')
    {
        try {
            $list = [];
            /*
             * $options = array(
             *      'max-keys'  => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
             *      'prefix'    => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
             *      'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
             *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回。
             *)
             * 其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
             */
            $option = [
                'max-keys' => 500,
                'prefix' => $prefix,
                'delimiter' => '',
                'marker' => '',
            ];
            while (true) {
                //Sizuka::log(LibLog::LOG_INFO,'SEARCH option',$option);
                $result = $this->oss->listObjects($this->bucket, $option);
                $object_list = $result->getObjectList();
                if (empty($object_list)) {
                    //Sizuka::log(LibLog::LOG_INFO,"object list empty, break");
                    break;
                }
                //Sizuka::log(LibLog::LOG_INFO,'get result',['getIsTruncated'=>$result->getIsTruncated(),'getMarker'=>$result->getMarker(),'getNextMarker'=>$result->getNextMarker()]);
                foreach ($object_list as $key => $item) {
                    $list[] = [
                        "key" => $item->getKey(),
                        "last_modified" => $item->getLastModified(),
                        "size" => $item->getSize(),
                        "type" => $item->getType(),
                    ];
                }
                if ('false' === $result->getIsTruncated()) {
                    //Sizuka::log(LibLog::LOG_INFO,"getIsTruncated false, break",$result->getIsTruncated());
                    break;
                }
                $option['marker'] = $result->getNextMarker();
            }

            return $list;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function makeObjectTree($list)
    {
//        $tree=[];
//        foreach ($list as $item){
//            $components=explode("/",$item);
//            $last_component=$components[count($components)-1];
//            $dir_link=$components;
//            unset($dir_link[count($dir_link)-1]);
//            $dir_existed=CommonHelper::safeReadNDArray($tree,$dir_link,[]);
//            if($last_component===''){
//                // is a directory
//                if(empty($dir_existed)){
//                    CommonHelper::safeWriteNDArray($tree,$dir_link,$dir_existed);
//                }
//            }else{
//                $dir_existed[]=$last_component;
//                CommonHelper::safeWriteNDArray($tree,$dir_link,$dir_existed);
//            }
//        }

        $tree = new OSSObjectTreeNode("ROOT//", true);
        foreach ($list as $item) {
            $tree->rootLoadItem($item);
        }

        return $tree;
    }

    /**
     * @param $object
     * @param int $timeout
     * @throws \Exception
     */
    public function proxyObject($object, $timeout = 3600)
    {
        if (!$this->doesObjectExist($object)) {
            throw new \Exception("It has been eaten by Giant Salamander!", 404);
        }

        $meta = $this->oss->getObjectMeta($this->bucket, $object);

        Sizuka::log(LibLog::LOG_INFO, "meta of object: " . $object, $meta);

        $content_type = $meta['content-type'];
        $content_length = $meta['content-length'];

        $url = $this->objectDownloadURL($object, $timeout);

        if ($content_type === null) {
            $content_type = 'application/octet-stream';
        }

        //需要用到的头
        header("Content-Type: " . $content_type);
        header("Content-Length: " . $content_length);

        Sizuka::log(LibLog::LOG_INFO, "proxy header list for object: " . $object, headers_list());

        $fp = fopen($url, "r");
        $buffer = 1024;
        $file_count = 0;
        //向浏览器返回数据
        while (!feof($fp) && $file_count < $content_length) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);
    }
}