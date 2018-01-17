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
use sinri\sizuka\Sizuka;

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
        $force_update = LibRequest::getRequest("force_update", 'NO');
        $path = '';//LibRequest::getRequest("path", '');
        // find sub objects
        try {
            $result = Sizuka::getCacheAgent()->getObject("object_tree");
            if (empty($result) || $force_update === 'YES') {
                $list = (new AliyunOSSLibrary())->listObjects($path);
                $tree = (new AliyunOSSLibrary())->makeObjectTree($list);
                $result = [
                    "tree" => $tree->toJsonObject(),
                    "cache_time" => date('Y-m-d H:i:s'),
                ];
                Sizuka::getCacheAgent()->saveObject("object_tree", $result, 60 * 10);
            }
            $this->_sayOK($result);
        } catch (\Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}