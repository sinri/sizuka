<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:28
 */

namespace sinri\sizuka\library;

use Exception;
use Mimey\MimeTypes;
use OSS\Core\OssException;
use OSS\OssClient;
use sinri\ark\core\ArkHelper;

class AliyunOSSLibrary
{
    protected $internalOSSClient = null;
    protected $publicOSSClient = null;
    protected $bucket;

    /**
     * AliyunOSSLibrary constructor.
     * @param string $bucket
     */
    public function __construct($bucket = null)
    {
        if ($bucket === null) {
            $bucket = Ark()->readConfig(['oss', 'bucket']);
        }
        $this->bucket = $bucket;
    }

    /**
     * @return OssClient
     * @throws OssException
     */
    protected function getInternalOssClient()
    {
        if ($this->internalOSSClient === null) {
            $accessKeyId = Ark()->readConfig(['oss', 'AccessKeyId']);//"<您从OSS获得的AccessKeyId>";
            $accessKeySecret = Ark()->readConfig(['oss', 'AccessKeySecret']);//"<您从OSS获得的AccessKeySecret>";
            $endpoint_internal = Ark()->readConfig(['oss', 'endpoint_internal']);
            $this->internalOSSClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint_internal);
            if (!$this->internalOSSClient) {
                throw new OssException("oss client failed to be created");
            }
        }
        return $this->internalOSSClient;
    }

    /**
     * @return OssClient
     * @throws OssException
     */
    protected function getPublicOssClient()
    {
        if ($this->publicOSSClient === null) {
            $accessKeyId = Ark()->readConfig(['oss', 'AccessKeyId']);//"<您从OSS获得的AccessKeyId>";
            $accessKeySecret = Ark()->readConfig(['oss', 'AccessKeySecret']);//"<您从OSS获得的AccessKeySecret>";
            $endpoint_public = Ark()->readConfig(['oss', 'endpoint_public']);
            $this->publicOSSClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint_public);
            if (!$this->publicOSSClient) {
                throw new OssException("oss client failed to be created");
            }
        }
        return $this->publicOSSClient;
    }

    /**
     * @param string $object
     * @return bool
     */
    public function doesObjectExist(string $object): bool
    {
        try {
            return $this->getInternalOssClient()->doesObjectExist($this->bucket, $object);
        } catch (Exception $exception) {
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
            return $this->getPublicOssClient()->signUrl($this->bucket, $object, $timeout);
        } catch (OssException $e) {
            //echo __METHOD__.' error: '.$e->getMessage();
            return false;
        }
    }

    /**
     * @param $object
     * @param null|int $range_begin
     * @param string|int $range_end
     * @return bool|string
     */
    public function readObject($object, $range_begin = null, $range_end = '')
    {
        try {
            $options = null;
            if ($range_begin !== null) {
                $options = array(OssClient::OSS_RANGE => $range_begin . '-' . $range_end);
            }
            return $this->getInternalOssClient()->getObject($this->bucket, $object, $options);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $prefix
     * @param null|string[] $patterns
     * @return array|bool
     */
    public function listObjects($prefix = '', $patterns = null)
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
                $result = $this->getInternalOssClient()->listObjects($this->bucket, $option);
                $object_list = $result->getObjectList();
                if (empty($object_list)) {
                    //Sizuka::log(LibLog::LOG_INFO,"object list empty, break");
                    break;
                }
                //Sizuka::log(LibLog::LOG_INFO,'get result',['getIsTruncated'=>$result->getIsTruncated(),'getMarker'=>$result->getMarker(),'getNextMarker'=>$result->getNextMarker()]);
                foreach ($object_list as $key => $item) {
                    $match = true;
                    if (is_array($patterns)) {
                        $match = false;
                        foreach ($patterns as $pattern) {
                            // simple permission
                            if (mb_strstr($item->getKey(), $pattern) !== false) {
                                $match = true;
                                break;
                            }
                        }
                    }
                    if ($match) {
                        $list[] = [
                            "key" => $item->getKey(),
                            "last_modified" => $item->getLastModified(),
                            "size" => $item->getSize(),
                            "type" => $item->getType(),
                        ];
                    }
                }
                if ('false' === $result->getIsTruncated()) {
                    //Sizuka::log(LibLog::LOG_INFO,"getIsTruncated false, break",$result->getIsTruncated());
                    break;
                }
                $option['marker'] = $result->getNextMarker();
            }

            return $list;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function makeObjectTree($list): OSSObjectTreeNode
    {
        $tree = new OSSObjectTreeNode("ROOT//", true);
        foreach ($list as $item) {
            $tree->rootLoadItem($item);
        }

        return $tree;
    }

    /**
     * @param $object
     * @param int $timeout
     * @throws Exception
     */
    public function proxyObject($object, $timeout = 3600)
    {
        if (!$this->doesObjectExist($object)) {
            throw new Exception("It has been eaten by Giant Salamander!", 404);
        }

        $meta = $this->getInternalOssClient()->getObjectMeta($this->bucket, $object);

        Ark()->logger('proxy')->info("meta of object: " . $object, ['meta' => $meta]);

        $content_type = $meta['content-type'];
        $content_length = $meta['content-length'];

        Ark()->logger('proxy')->info(
            'content_type from oss meta api for ' . $object,
            ['content-type' => $content_type, 'content-length' => $content_length]
        );

        $ext = pathinfo($object, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        if ($content_type === 'application/octet-stream') {
            $mimes = new MimeTypes;

            // Convert extension to MIME type:
            $content_type = $mimes->getMimeType($ext); // application/json

            Ark()->logger('proxy')->info('content_type from mime ext', ['content_type' => $content_type]);
        }

        $url = $this->objectDownloadURL($object, $timeout);

        if ($content_type === null) {
            $content_type = 'application/octet-stream';
        }

        if (in_array($ext, ['mp3', 'mp4'])) {
            $server_http_range = Ark()->webInput()->readServer('HTTP_RANGE', '');
            preg_match('/bytes=(\d+)-(\d*)/', $server_http_range, $matches);
            Ark()->logger('proxy')->info('HTTP_RANGE', $server_http_range);
            Ark()->logger('proxy')->info("matches", $matches);
            $range_begin = ArkHelper::readTarget($matches, 1, 0);
            $range_end = ArkHelper::readTarget($matches, 2, 0);
            if ($range_end <= 0) {
                //$range_end = $content_length - 1;
                $range_end = min($content_length - 1, $range_begin + 51200);//why this would cause problem
            }

            http_response_code(206);
            header("Accept-Ranges: bytes");
            header("Content-Range: bytes " . $range_begin . "-" . $range_end . "/" . $content_length);

            //需要用到的头
            header("Content-Type: " . $content_type);
            header("Content-Length: " . ($range_end - $range_begin + 1)
            /*$content_length*/);

            Ark()->logger('proxy')->info(
                "proxy header list for object: " . $object,
                ['headers' => headers_list()]
            );

            echo $this->readObject($object, $range_begin, $range_end);

//            $fp = fopen($url, "r");
//            $buffer = 1024;
//            $file_count = 0;
//            //向浏览器返回数据
//            $seek_result = fseek($fp, $range_begin);
//            Sizuka::log(LibLog::LOG_INFO, "seek to " . $range_begin, $seek_result);
//            while (!feof($fp) && $file_count < ($range_end - $range_begin + 1)) {
//                $file_con = fread($fp, $buffer);
//                $file_count += $buffer;
//                echo $file_con;
//            }
//            fclose($fp);
        } else {
            //需要用到的头
            header("Content-Type: " . $content_type);
            header("Content-Length: " . $content_length);

            Ark()->logger('proxy')->info(
                "proxy header list for object: " . $object,
                ['headers' => headers_list()]
            );

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

    /**
     * @param string $object
     * @param int $timeout
     * @throws Exception
     */
    public function proxyDownloadObject(string $object, $timeout = 3600)
    {
        if (!$this->doesObjectExist($object)) {
            throw new Exception("It has been eaten by Giant Salamander!", 404);
        }
        $meta = $this->getInternalOssClient()->getObjectMeta($this->bucket, $object);

        Ark()->logger('proxy')->info("meta of object: " . $object, ['meta' => $meta]);

        $content_type = $meta['content-type'];
        $content_length = $meta['content-length'];

        Ark()->logger('proxy')->info(
            'content_type from oss meta api for ' . $object,
            ['content-type' => $content_type, 'content_length' => $content_length]
        );

        $ext = pathinfo($object, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        if ($content_type === 'application/octet-stream') {
            $mimes = new MimeTypes;

            // Convert extension to MIME type:
            $content_type = $mimes->getMimeType($ext); // application/json

            Ark()->logger('proxy')->info('content_type from mime ext', ['content_type' => $content_type]);
        }

        $url = $this->objectDownloadURL($object, $timeout);

        if ($content_type === null) {
            $content_type = 'application/octet-stream';
        }

        //需要用到的头
        header("Content-Type: " . $content_type);
        header("Content-Length: " . $content_length);

        Ark()->logger('proxy')->info(
            "proxy header list for object: " . $object,
            ['headers' => headers_list()]
        );

        $fp = fopen($url, "r");
        $buffer = 1024;
        $file_count = 0;
        //向浏览器返回数据
        while (!feof($fp) && $file_count < $content_length) {
            $file_con = fread($fp, $buffer);
            $file_count += strlen($file_con);//$buffer;
            echo $file_con;
        }
        fclose($fp);
    }

    /**
     * Require `ffmpeg`
     * For Debian, `apt-get install ffmpeg`
     * @param $object
     * @throws Exception
     */
    public function getMp3ObjectDurationWithFFMpeg($object)
    {
        if (!$this->doesObjectExist($object)) {
            throw new Exception("-1", 404);
        }

        $url = $this->objectDownloadURL($object, 60 * 10);

        $command = "ffmpeg -i " . escapeshellarg($url) . " 2>&1 | grep duration -i|awk '{print $2}'";
        //01:43:08.41,
        $line = exec($command);
        $line = str_replace(',', '', $line);
        $g1 = explode(':', $line);
        $seconds = 0;
        for ($i = 0; $i < count($g1); $i++) {
            if ($i > 0) $seconds = $seconds * 60;
            $seconds += $g1[$i];
        }

        echo $seconds;
    }

    /**
     * @param string $object
     * @return array
     * @throws OssException
     */
    public function getObjectMeta(string $object)
    {
        return $this->getInternalOssClient()->getObjectMeta($this->bucket, $object);
    }
}
