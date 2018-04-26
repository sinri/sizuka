<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 21:25
 */

//$config['site_title']='The Great Sizuka';

/*
 * Commonly it should be empty.
 * However, for develop purpose, it might record the prefix to the web root.
 * http://localhost/PHPStorm/sizuka -> /PHPStorm/sizuka
 */
$config['gateway'] = '/PHPStorm/sizuka';

/**
 * The Aliyun OSS Access Configuration
 */
$config['oss'] = [
    'AccessKeyId' => '?',
    'AccessKeySecret' => '?',
    // inner
    //'endpoint'=>'oss-cn-hangzhou-internal.aliyuncs.com',
    // outer
    'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
    'bucket' => 'tata-design',
];

/**
 * The token set to cookie to pass the middleware.
 * Leave it empty would make the OSS public actually
 */
$config['token'] = 'token';

/**
 * The logging configuration
 */
$config['log']['dir'] = __DIR__ . '/log';

/**
 * The cache configuration
 */
$config['cache']['dir'] = __DIR__ . '/cache';