<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:48
 */

namespace sinri\sizuka\controller;


use sinri\enoch\mvc\SethController;

class Api extends SethController
{
    public function __construct($initData = null)
    {
        parent::__construct($initData);
    }

    public function explorer($path)
    {
        //TODO: find sub objects
    }
}