<?php

namespace jsy\generator\contract\model\maker;

use jsy\base\base\GetterSetter;


abstract class ModelMakerAbstract
{
    use GetterSetter;

    protected string $extendModel = '';
    protected string $modelFullName;
    protected string $modelName;
    protected array  $modelProperty    = [];
    protected string $namespacePrefix  = '';
    protected string $modelFile        = '';

    protected function getBaseStub():string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'provider'.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR.'maker'.DIRECTORY_SEPARATOR.'stub';
    }

}
