<?php


namespace maodou\generator\parser\support;


use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use Reflector;

class AssertUtil
{
    public static function getObjectType(\ReflectionClass $ref):string
    {
        if($ref->isAbstract()){
            return 'abstract';
        }
        if($ref->isInterface()){
            return 'interface';
        }
        if($ref->isTrait()){
            return 'trait';
        }
        return 'class';
    }

    /**
     * Returns the tags for this DocBlock.
     *
     * @param \ReflectionClass | \ReflectionFunction | \ReflectionMethod  $ref
     * @return Tag[]
     */
    public static function getPhpdoc($ref):array
    {
        if($ref->getDocComment() === false){
            return [];
        }
        $phpdoc = DocBlockFactory::createInstance()->create($ref);
        return $phpdoc->getTags();
    }

    /**
     * @desc 获取父类完整名称
     * @param \ReflectionClass $ref
     * @return string|null
     */
    public static function getExtendClass(\ReflectionClass $ref):?string
    {
        if($ref->getParentClass() === false){
            return null;
        }
        return $ref->getParentClass()->getName();
    }

    /**
     * @desc 获取traits
     * @param \ReflectionClass $ref
     * @return array
     */
    public static function getTraits(\ReflectionClass $ref): array
    {
        $traits = [];
        if($ref->getTraits() === false){
            return $traits;
        }

        foreach ($ref->getTraits() as $trait){
            $traits[] = $trait->getName();
        }
        return $traits;
    }
}
