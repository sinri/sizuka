<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:48
 */

namespace sinri\sizuka\controller;


use sinri\enoch\core\LibRequest;
use sinri\enoch\mvc\SethController;
use sinri\sizuka\library\AliyunOSSLibrary;

class Api extends SethController
{
    public function __construct($initData = null)
    {
        parent::__construct($initData);
    }

    public function setToken($token = 'sizuka')
    {
        setcookie("sizuka_token", $token);
    }

    public function explorer()
    {
        $path = LibRequest::getRequest("path", '');
        //TODO: find sub objects
        try {
            $list = (new AliyunOSSLibrary())->listObjects($path);
            $tree = (new AliyunOSSLibrary())->makeObjectTree($list);
            $this->_sayOK(['list' => $list, 'tree' => $tree]);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}