<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:23
 */

//error_reporting(E_ALL^E_NOTICE^E_WARNING);
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

if (strpos($path, '/proxy/') === 0) {
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
    // proxy
    \sinri\sizuka\Sizuka::oss($path);
} else {
    $lamech = new \sinri\enoch\mvc\Lamech();

    $lamech->getRouter()->setErrorHandler(function ($err_data) {
        header("Content-Type: application/json");
        \sinri\enoch\core\LibResponse::jsonForAjax(\sinri\enoch\core\LibResponse::AJAX_JSON_CODE_FAIL, $err_data);
    });

    //$start = microtime(true);

    $lamech->handleRequestForWeb();

    //$end = microtime(true);
}