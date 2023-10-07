<?php


namespace jsy\generator\console\execute\provider\logic\provider;


use jsy\base\utils\UtilsTools;
use jsy\generator\console\execute\provider\logic\contract\BuildLogicAbstract;
use think\helper\Str;

class BuildLogic extends BuildLogicAbstract
{
    protected null|string $requestClassname = null;
    protected null|string $responseClassname = null;

    public function setRequestClassname(string $requestClassname):self
    {
        $this->requestClassname = $requestClassname;
        return $this;
    }

    public function setResponseClassname(string $responseClassname):self
    {
        $this->responseClassname = $responseClassname;
        return $this;
    }

    public function build()
    {
        if(file_exists($this->getPathname())){
            file_put_contents($this->getPathname().'-bak-'.Str::random(4,1),file_get_contents($this->getPathname()));
        }
        file_put_contents($this->getPathname(), self::buildClass());
        return $this;
    }

    protected function getStub(): string
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'logic'.DIRECTORY_SEPARATOR.'logic.stub';
    }

    protected function buildClass(): string
    {
        $stub = file_get_contents($this->getStub());
        $namespace = $this->parseNamespace($this->getClassname());
        $classname = class_basename($this->getClassname());
        return str_replace(['{%namespace%}', '{%classname%}','{%requestClassname%}','{%requestClass%}','{%responseClassname%}','{%responseClass%}'], [
            $namespace,
            $classname,
            $this->requestClassname,
            class_basename($this->requestClassname),
            $this->responseClassname,
            class_basename($this->responseClassname)
        ], $stub);
    }

    public function getClassname(): string
    {
        return UtilsTools::replaceNamespace($this->getNamespacePrefix().'/logic/'.$this->getPathSuffix());
    }

    public function getPathname(): string
    {
        return UtilsTools::replaceSeparator($this->getPathPrefix().'/logic/'.$this->getPathSuffix().'.php');
    }
}
