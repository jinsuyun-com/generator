<?php


namespace maodou\generator\console\execute\provider\logic\contract;


use maodou\generator\console\execute\contract\MakeClassByStub;
use think\helper\Str;

abstract class BuildLogicAbstract extends MakeClassByStub
{
    protected string $module = 'app';
    protected string $controller;
    protected string $action;

    protected null | string $prefixPath      = null;
    protected null | string $prefixNamespace = null;

    public function __construct(string $module,string $controller,string $action)
    {
        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;
    }

    public function setPrefixPath(?string $rootPath):self
    {
        $this->prefixPath = $rootPath;
        return $this;
    }

    public function setPrefixNamespace(?string $rootNamespace):self
    {
        $this->prefixNamespace = $rootNamespace;
        return  $this;
    }

    protected function getPathPrefix():string
    {
        if(is_null($this->prefixPath)){
            return app()->getRootPath().$this->module;
        }else{
            return $this->prefixPath;
        }
    }

    protected function getNamespacePrefix():string
    {
        if(is_null($this->prefixNamespace)){
            return 'rst';
        }
        return $this->prefixNamespace;
    }

    protected function getPathSuffix():string
    {
        $path = Str::snake($this->controller);
        $path .= '/'.Str::studly($this->action);
        return $path;
    }
}
