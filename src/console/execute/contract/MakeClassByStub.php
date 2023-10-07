<?php


namespace jsy\generator\console\execute\contract;


abstract class MakeClassByStub
{
    abstract protected function getStub():string;

    abstract protected function buildClass():string;

    abstract public function getClassname():string;

    abstract public function getPathname():string;

    protected function getBaseStub():string
    {
        $dir = __DIR__;
        $dir = str_replace('\\',DIRECTORY_SEPARATOR,$dir);
        $relateDir = explode(DIRECTORY_SEPARATOR,$dir);
        array_pop($relateDir);
        return implode(DIRECTORY_SEPARATOR,$relateDir).DIRECTORY_SEPARATOR.'stub';
    }

    protected function parseNamespace(string $class):string
    {
        $array = explode('\\',$class);
        array_pop($array);
        return implode('\\',$array);
    }

}
