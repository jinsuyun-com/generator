<?php


namespace jsy\generator\utils;


use jsy\base\utils\UtilsTools;

class ClassParseUtils
{
    public static  function classInfo(string $class):array
    {
        $className = UtilsTools::replaceNamespace($class);
        $data['classname'] = $className;
        $data['short_name'] = class_basename($className);
        if(class_exists($className)){
            $data['ref'] =  new \ReflectionClass($className);
        }else{
            $data['ref'] = false;
        }
        return $data;
    }
}
