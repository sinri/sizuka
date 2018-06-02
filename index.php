<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:23
 */

//error_reporting(E_ALL^E_NOTICE^E_WARNING);
use Jenssegers\Agent\Agent;

require_once __DIR__ . '/autoload.php';

date_default_timezone_set("Asia/Shanghai");

//if (file_exists(__DIR__ . '/config/allow_cors.php')) {
//    require_once __DIR__ . '/config/allow_cors.php';
//}

$gateway = \sinri\sizuka\Sizuka::config(['gateway'], '/index.php');
\sinri\sizuka\Sizuka::parseURL($gateway, $path, $queryString);

//echo "<pre>".PHP_EOL;
//echo "PATH=".$path.PHP_EOL;
//echo "QUERY STRING=".$queryString.PHP_EOL;

if (strpos($path, '/proxy') === 0) {
    // it is an oss proxy
    // check auth
    $pass = (new \sinri\sizuka\middleware\SizukaMiddleware())->shouldAcceptRequest(
        $path,
        \sinri\enoch\core\LibRequest::getRequestMethod(),
        explode("&", $queryString)
    );
    if (!$pass) {
        \sinri\sizuka\Sizuka::errorPage("Who art thou?", 403);
        exit();
    }

    if (strpos($path, '/proxy/') === 0) {
        \sinri\sizuka\Sizuka::oss($path);
    } elseif (strpos($path, '/proxy_download/') === 0) {
        \sinri\sizuka\Sizuka::ossDownload($path);
    } elseif (strpos($path, '/proxy_mp3_duration/') === 0) {
        \sinri\sizuka\Sizuka::ossMp3Duration($path);
    }
} else {
    //setcookie("sizuka_token",'sizuka');
    $lamech = new \sinri\enoch\mvc\Lamech();

    $lamech->getRouter()->setErrorHandler(function ($err_data) {
        header("Content-Type: application/json");
        \sinri\enoch\core\LibResponse::jsonForAjax(\sinri\enoch\core\LibResponse::AJAX_JSON_CODE_FAIL, $err_data);
    });

    $lamech->getRouter()->any("", function () {
        $userAgentWorker = new Agent();
        if ($userAgentWorker->isMobile()) {
            //To use mobile style
            header("Location: ./frontend/index-mobile.html");
        } else {
            // PC Style
            header("Location: ./frontend/index.html");
        }

    });

    $lamech->getRouter()->loadAllControllersInDirectoryAsCI(
        __DIR__ . '/controller',
        '',
        '\sinri\sizuka\controller\\',
        \sinri\sizuka\middleware\SizukaMiddleware::class
    );

    //$start = microtime(true);

    $lamech->handleRequestForWeb();

    //$end = microtime(true);
}