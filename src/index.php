<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:23
 */

//error_reporting(E_ALL^E_NOTICE^E_WARNING);
use Jenssegers\Agent\Agent;
use sinri\ark\core\ArkHelper;
use sinri\ark\web\implement\ArkRouteErrorHandlerAsJson;
use sinri\sizuka\middleware\SizukaMiddleware;
use sinri\sizuka\Sizuka;

require_once __DIR__ . '/autoload.php';

//if (file_exists(__DIR__ . '/config/allow_cors.php')) {
//    require_once __DIR__ . '/config/allow_cors.php';
//}

//Ark()->logger('web')->info('test');

ArkHelper::registerErrorHandlerForLogging(Ark()->logger('WebError'));

$gateway = Ark()->readConfig(['gateway'], '/index.php');
Sizuka::parseURL($gateway, $path, $queryString);

if (strpos($path, '/proxy') === 0) {
    // it is an oss proxy
    // check auth
    $pass = (new SizukaMiddleware())->shouldAcceptRequest(
        $path,
        Ark()->webInput()->getRequestMethod(),
        explode("&", $queryString)
    );
    if (!$pass) {
        Sizuka::errorPage("Who art thou?", 403);
        exit();
    }

    if (strpos($path, '/proxy/') === 0) {
        Sizuka::oss($path);
    } elseif (strpos($path, '/proxy_download/') === 0) {
        Sizuka::ossDownload($path);
    } elseif (strpos($path, '/proxy_mp3_duration/') === 0) {
        Sizuka::ossMp3Duration($path);
    }
} else {
    $router = Ark()->webService()->getRouter();
    $router->setErrorHandler(new ArkRouteErrorHandlerAsJson());
    $webLogger = Ark()->logger('web');
//    $webLogger->setIgnoreLevel(LogLevel::DEBUG);
//    $router->setDebug(true);
    $router->setLogger($webLogger);

    $router->any(
        "",
        function () {
            $userAgentWorker = new Agent();
            if ($userAgentWorker->isMobile()) {
                //To use mobile style
                header("Location: ./frontend/index-mobile.html");
            } else {
                // PC Style
                header("Location: ./frontend/index.html");
            }
        }
    );

    $router->loadAutoRestfulControllerRoot(
        '',
        '\sinri\sizuka\controller',
        [SizukaMiddleware::class]
    );

    try {
        Ark()->webService()->handleRequestForWeb();
    } catch (Exception $exception) {
        echo $exception->getMessage() . PHP_EOL . $exception->getTraceAsString();
    }
}