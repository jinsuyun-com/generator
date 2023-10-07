<?php


namespace jsy\generator\utils;


use jsy\base\schema\constants\SchemaType;
use jsy\base\utils\UtilsTools;
use IteratorAggregate;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AggregatedType;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use phpDocumentor\Reflection\Types\This;

class TypeUtils
{
    public static function toTypeScriptType(string $type):string
    {
        if(class_exists($type)){
            $type = 'object';
        }
        if(is_countable($type)){
            $type = 'array';
        }
        return match ($type) {
            'string', 'varchar', 'char', 'tinytext', 'mediumtext', 'longtext', 'text', 'timestamp', 'date', 'time', 'guid', 'datetimetz', 'datetime', 'set', 'enum' => 'string',
            'integer', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float' => 'number',
            'bool', 'boolean' => 'boolean',
            'array'=>'any[]',
            'object'=>'Record<string,any>',
            'null'=>'null',
            default => 'any',
        };
    }

    public static function mysqlTypeToPhpType(string $mysqlType,bool $isJsonArray = false):string
    {
        if($mysqlType === 'json' ){
            $mysqlType = $isJsonArray ?  'array' : '\stdClass';
        }
        return match ($mysqlType) {
           'varchar', 'char', 'tinytext', 'mediumtext', 'longtext', 'text', 'timestamp', 'date', 'time', 'guid', 'datetimetz', 'datetime', 'set', 'enum' => 'string',
            'integer', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint'=>'int',
             'decimal', 'float' => 'float',
            'bool', 'boolean' => 'bool',
            'array'=>'array',
            'object','\stdClass'=>'\stdClass',
            'null'=>'null',
            default => 'mixed',
        };
    }

    public static function parseToSchemaType(array $types): array
    {
        $schemaTypes = [];
        if (empty($types)){
            return $schemaTypes;
        }
        foreach ($types as $type){
            switch (true){
                case in_array($type,['int','float','double']):
                    $schemaTypes[] =  SchemaType::NUMBER;
                    break;
                case $type === 'string':
                    $schemaTypes[] =  SchemaType::STRING;
                    break;
                case $type === 'bool':
                    $schemaTypes[] =  SchemaType::BOOLEAN;
                    break;
                case $type === 'array':
                    $schemaTypes[] =  SchemaType::ARRAY;
                    break;
                case $type === '\stdClass':
                    $schemaTypes[] =  SchemaType::OBJECT;
                    break;
                case $type === 'null':
                    $schemaTypes[] =  SchemaType::NULL;
                    break;
                case class_exists($type):
                    $schemaTypes[] = $type;
                    break;
                default:
                    $schemaTypes[] =  SchemaType::ANY;
            }
        }
        return array_unique($schemaTypes);
    }

    public static function parseRefTypes(null | \ReflectionNamedType|\ReflectionUnionType  $type):array
    {
        $types = [];
        if(is_null($type)){
            return $types;
        }
        if($type instanceof \ReflectionNamedType){
            $types[] = $type->getName();
            if($type->allowsNull()){
                $types[] = 'null';
            }
            return $types;
        }
        if($type instanceof \ReflectionUnionType){
            foreach ($type->getTypes() as $refType){
                $types[] =$refType->getName();
            }
            return $types;
        }
        return $types;
    }

    public static function parseDocBlockTypes(Type $docBlockTypes,string $declaringClass):array
    {
        $types = [];
        if($docBlockTypes instanceof AggregatedType){
            foreach ($docBlockTypes->getIterator() as $docBlockType){

                $types[] = self::parseDocBlockType($docBlockType,$declaringClass);
            }
            return $types;
        }
        $types[] = self::parseDocBlockType($docBlockTypes,$declaringClass);
        return $types;
    }

    protected static function parseDocBlockType(Type $docBlockType,string $declaringClass):string
    {
        if ($docBlockType instanceof This || $docBlockType instanceof Static_ || $docBlockType instanceof Self_) {
            return "\\" . UtilsTools::replaceNamespace($declaringClass);
        }
        if(class_exists($docBlockType->__toString())){
            return "\\" . UtilsTools::replaceNamespace($docBlockType->__toString());
        }
        return $docBlockType->__toString();
    }
}
