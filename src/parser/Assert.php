<?php


namespace maodou\generator\parser;


use maodou\generator\parser\support\ClassReflection;

class Assert
{
    protected static $object;

    protected static $ref;

    public static function create(string $filter)
    {
        if(class_exists($filter)){
            self::createObject($filter);
        }
    }

    private static function createObject(string $filter)
    {
        $ref = new ClassReflection($filter);
        self::$object[$ref->getName()] = $ref;
    }

    private static function createFile(string $filter)
    {

    }
}
