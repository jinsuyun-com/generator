<?php


namespace jsy\generator\utils\classloader\facade;


use jsy\base\base\collection\ObjectCollection;
use think\Facade;

/**
 * @see \jsy\generator\utils\classloader\JsyClassLoader
 * @mixin \jsy\generator\utils\classloader\JsyClassLoader
 * @method ObjectCollection getPrefixDirs() static
 * @method string | null queryDir(string $namespace) static
 * @method bool loadClass(string $class) static
 */
class JsyClassLoader extends Facade
{
    protected static function getFacadeClass()
    {
        return \jsy\generator\utils\classloader\JsyClassLoader::class;
    }
}
