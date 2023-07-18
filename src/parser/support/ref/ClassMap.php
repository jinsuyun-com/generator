<?php


namespace maodou\generator\parser\support\ref;


use maodou\base\utils\UtilsTools;
use think\Collection;

class ClassMap
{
    protected array $classMap = [];


    public function addImport(string $sourceClass,string $class)
    {
        $sourceClass = UtilsTools::replaceNamespace($sourceClass);
        $class = UtilsTools::replaceNamespace($class);
        $classMap = $this->getSourceClassCollection($sourceClass);
        $exist = $classMap->where('type','=','import')->where('class','=',$class)->first();
        if(is_null($exist) === false){
            return true;
        }
        $classMap->push($this->parseClassInfo($class,'import'));
        return true;
    }



    protected function getSourceClassCollection(string $sourceClass):Collection
    {
        if(isset($this->classMap[$sourceClass]) === false){
            $this->classMap[$sourceClass] = new Collection();
        }
        return $this->classMap[$sourceClass];
    }

    protected function parseClassInfo(string $class,string $type):array
    {
        $data['class'] = $class;
        $data['short_name'] = class_basename($class);
        if(class_exists($class)){
            $data['ref'] =  new \ReflectionClass($class);
        }else{
            $data['ref'] = false;
        }
        return $data;
    }

}
