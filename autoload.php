<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:22
 */

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set("Asia/Shanghai");

Ark()->loadConfigFileWithPHPFormat(__DIR__ . '/config/config.php');

//var_dump(Ark()->readConfig(['log']));

//ArkHelper::registerAutoload('sinri\sizuka', __DIR__);
