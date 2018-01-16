<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:39
 */

namespace sinri\sizuka\middleware;


use sinri\enoch\mvc\MiddlewareInterface;
use sinri\sizuka\Sizuka;

class SizukaMiddleware extends MiddlewareInterface
{
    public function shouldAcceptRequest($path, $method, $params, &$preparedData = null, &$responseCode = 200, &$error = null)
    {
        if ($_COOKIE['sizuka_token'] === Sizuka::config(['token'], rand(10000, 99999))) {
            return true;
        }
        return false;
    }
}