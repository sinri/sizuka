<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:29
 */

namespace sinri\sizuka;


use Exception;
use sinri\ark\cache\ArkCache;
use sinri\sizuka\library\AliyunOSSLibrary;

/**
 * Class Sizuka
 * @package sinri\sizuka
 */
class Sizuka
{
    /**
     * @param array|scalar $keyChain
     * @param null $default
     * @return mixed
     * @deprecated use Ark() instead
     */
    public static function config($keyChain, $default = null)
    {
        return Ark()->readConfig($keyChain, $default);
    }

    public static function parseURL($gateway = '/index.php', &$path = '', &$queryString = '')
    {
        $path = $_SERVER['REQUEST_URI'];
        if (strpos($path, $gateway) === 0) {
            $path = substr($path, strlen($gateway));
        }
        $queryString = $_SERVER['QUERY_STRING'];
        if (strlen($queryString) > 0 || substr($queryString, -1) === '?') {
            $path = substr($path, 0, strlen($path) - 1 - strlen($queryString));
        }
    }

    /**
     * @param string $gateway
     * @param string $prefix
     * @param string $queryString
     * @deprecated seems no use
     */
    public static function parseURLx($gateway = '/index.php', &$prefix = '', &$queryString = '')
    {
        print_r($_SERVER);

        $prefix = $_SERVER['SCRIPT_NAME'];
        if (
            (strpos($_SERVER['REQUEST_URI'], $prefix) !== 0)
            && (strrpos($prefix, $gateway) + 10 == strlen($prefix))
        ) {
            $prefix = substr($prefix, 0, strlen($prefix) - 10);
        }

        $queryString = substr($_SERVER['REQUEST_URI'], strlen($prefix));

//        echo "PREFIX=".$prefix.PHP_EOL;
//        echo "QUERY=".$str.PHP_EOL;
    }

    public static function oss($object, $timeout = 3600)
    {
        try {
            //echo $object.PHP_EOL;
            $object = substr($object, strlen('/proxy/'));
            (new AliyunOSSLibrary(Ark()->readConfig(['oss', 'bucket'])))->proxyObject($object, $timeout);
        } catch (Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function ossDownload($object, $timeout = 3600)
    {
        try {
            //echo $object.PHP_EOL;
            $object = substr($object, strlen('/proxy_download/'));
            (new AliyunOSSLibrary(Ark()->readConfig(['oss', 'bucket'])))->proxyDownloadObject($object, $timeout);
        } catch (Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function ossMp3Duration($object)
    {
        try {
            //echo $object.PHP_EOL;
            $object = substr($object, strlen('/proxy_mp3_duration/'));
            (new AliyunOSSLibrary(Ark()->readConfig(['oss', 'bucket'])))->getMp3ObjectDurationWithFFMpeg($object);
        } catch (Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function errorPage($error, $code)
    {
        http_response_code($code);
        echo $error;
    }

    /**
     * @param $level
     * @param $message
     * @param string $object
     * @deprecated use Ark() instead
     */
    public static function log($level, $message, $object = '')
    {
        if (!is_array($object)) {
            $object = ['auto_object' => $object];
        }
        Ark()->logger('sizuka')->log($level, $message, $object);
    }

    /**
     * @return ArkCache
     * @deprecated use Ark() instead
     */
    public static function getCacheAgent(): ArkCache
    {
        return Ark()->cache('sizuka');
    }
}