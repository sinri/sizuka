<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:29
 */

namespace sinri\sizuka;


use sinri\enoch\core\LibLog;
use sinri\enoch\helper\CommonHelper;
use sinri\enoch\service\FileCache;
use sinri\sizuka\library\AliyunOSSLibrary;

class Sizuka
{
    public static function config($keyChain, $default = null)
    {
        $config = [];
        require __DIR__ . '/config/config.php';
        return CommonHelper::safeReadNDArray($config, $keyChain, $default);
    }

    public static function parseURL($gateway = '/index.php', &$path, &$queryString)
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

    public static function parseURLx($gateway = '/index.php', &$prefix, &$queryString)
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
            (new AliyunOSSLibrary(self::config(['oss', 'bucket'])))->proxyObject($object, $timeout);
        } catch (\Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function ossDownload($object, $timeout = 3600)
    {
        try {
            //echo $object.PHP_EOL;
            $object = substr($object, strlen('/proxy_download/'));
            (new AliyunOSSLibrary(self::config(['oss', 'bucket'])))->proxyDownloadObject($object, $timeout);
        } catch (\Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function ossMp3Duration($object)
    {
        try {
            //echo $object.PHP_EOL;
            $object = substr($object, strlen('/proxy_mp3_duration/'));
            (new AliyunOSSLibrary(self::config(['oss', 'bucket'])))->proxyDownloadObject($object, 60 * 5);
        } catch (\Exception $exception) {
            self::errorPage($exception->getMessage(), 404);
        }
    }

    public static function errorPage($error, $code)
    {
        http_response_code($code);
        echo $error;
    }

    private static $logger = null;

    /**
     * @param $level
     * @param $message
     * @param string $object
     */
    public static function log($level, $message, $object = '')
    {
        if (!self::$logger) {
            self::$logger = new LibLog(self::config(['log', 'dir'], __DIR__ . '/log'));
        }
        self::$logger->log($level, $message, $object);
    }

    private static $file_cache = null;

    /**
     * @return null|FileCache
     */
    public static function getCacheAgent()
    {
        if (!self::$file_cache) {
            self::$file_cache = new FileCache(self::config(['cache', 'dir'], __DIR__ . '/cache'));
        }
        return self::$file_cache;
    }
}