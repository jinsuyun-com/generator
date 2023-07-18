<?php


namespace maodou\generator\utils\classloader\facade;


use maodou\base\base\collection\ObjectCollection;
use think\Facade;

/**
 * @see \maodou\generator\utils\classloader\MaodouClassLoader
 * @mixin \maodou\generator\utils\classloader\MaodouClassLoader
 * @method ObjectCollection getPrefixDirs() static
 * @method string | null queryDir(string $namespace) static
 * @method bool loadClass(string $class) static
 */
class MaodouClassLoader extends Facade
{
    protected static function getFacadeClass()
    {
        return \maodou\generator\utils\classloader\MaodouClassLoader::class;
    }
}
