<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:39
 */

namespace sinri\sizuka\middleware;


use sinri\enoch\core\LibRequest;
use sinri\enoch\helper\CommonHelper;
use sinri\enoch\mvc\MiddlewareInterface;
use sinri\sizuka\Sizuka;

class SizukaMiddleware extends MiddlewareInterface
{
    public function shouldAcceptRequest($path, $method, $params, &$preparedData = null, &$responseCode = 200, &$error = null)
    {
        $configured_token = Sizuka::config(['token'], '');
        if ($configured_token === '') {
            return true;
        }
        $token = LibRequest::getCookie('sizuka_token');
        if ($token === $configured_token) {
            return true;
        }
        $permitted = CommonHelper::safeReadArray($configured_token, $token, false);
        if (!!$permitted) {
            return true;
        }
        return false;
    }
}