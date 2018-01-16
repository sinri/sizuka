<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:22
 */

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function ($class_name) {
    $ch = new \sinri\enoch\helper\CommonHelper();
    $file_path = $ch->getFilePathOfClassNameWithPSR0(
        $class_name,
        'sinri\sizuka',
        __DIR__,
        '.php'
    );
    if ($file_path) {
        /** @noinspection PhpIncludeInspection */
        require_once $file_path;
    }
});