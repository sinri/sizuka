<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:39
 */

namespace sinri\sizuka\middleware;


use sinri\ark\core\ArkHelper;
use sinri\ark\web\ArkRequestFilter;

class SizukaMiddleware extends ArkRequestFilter
{
    public function shouldAcceptRequest(
        $path,
        $method,
        $params,
        &$preparedData = null,
        &$responseCode = 200,
        &$error = null
    ): bool
    {
        $configured_token = Ark()->readConfig(['token'], '');
        if ($configured_token === '') {
            return true;
        }
        if ($path === '/Api/getSiteMeta') {
            return true;
        }
        $token = Ark()->webInput()->readCookie('sizuka_token');
        if ($token === $configured_token) {
            return true;
        }
        $permitted = ArkHelper::readTarget($configured_token, $token, false);
        if (!!$permitted) {
            return true;
        }
        $error = "You should use a valid token.";
        return false;
    }

    public function filterTitle(): string
    {
        return 'SizukaMiddleware';
    }
}