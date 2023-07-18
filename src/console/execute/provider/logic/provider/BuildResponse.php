<?php


namespace maodou\generator\console\execute\provider\logic\provider;


use maodou\base\exception\AppException;
use maodou\base\utils\UtilsTools;
use maodou\generator\console\execute\provider\logic\contract\BuildLogicAbstract;

class BuildResponse extends BuildLogicAbstract
{
    public function build():self
    {
        if(class_exists($this->getClassname())){
            throw new AppException(sprintf('【%s】已存在',$this->getClassname()),1);
        }
        if(file_exists($this->getPathname())){
            throw new AppException(sprintf('【%s】已存在',$this->getPathname()),2);
        }
        $dir = dirname($this->getPathname());
        if(is_dir($dir) === false){
            mkdir($dir,0755,true);
        }
        file_put_contents($this->getPathname(), self::buildClass());
        return $this;
    }

    public function getClassname():string
    {
        return UtilsTools::replaceNamespace($this->getNamespacePrefix().'/response/'.$this->getPathSuffix().'Response');
    }

    public function getPathname():string
    {
        return UtilsTools::replaceSeparator($this->getPathPrefix().'/response/'.$this->getPathSuffix().'Response.php');
    }

    protected function getStub(): string
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'logic'.DIRECTORY_SEPARATOR.'logicResponse.stub';
    }

    protected function buildClass(): string
    {
        $stub = file_get_contents($this->getStub());
        $namespace = $this->parseNamespace($this->getClassname());
        $classname = class_basename($this->getClassname());
        return str_replace(['{%namespace%}', '{%classname%}'], [
            $namespace,
            $classname,
        ], $stub);
    }
}
