<?php


namespace maodou\generator\utils\classloader;


use Composer\Autoload\ClassLoader;
use maodou\base\base\collection\ObjectCollection;
use maodou\base\utils\UtilsTools;

class MaodouClassLoader
{
    protected ObjectCollection $prefixDirsPsr4;
    protected array $classLoaders;
    protected string $composerPath;

    public function __construct()
    {
        $this->composerPath = app()->getRootPath().'vendor'.DIRECTORY_SEPARATOR.'composer';
        $this->prefixDirsPsr4 = new ObjectCollection();
        $this->classLoaders = ClassLoader::getRegisteredLoaders();;
        $this->parsePrefixDirsPsr4();
    }

    public function loadClass(string $class):bool
    {
        $loader = current($this->classLoaders);
        $res = $loader->loadClass($class);
        return boolval($res);
    }

    public function getPrefixDirs():ObjectCollection
    {
        return $this->prefixDirsPsr4;
    }

    public function queryDir(string $namespace):string | null
    {
        return $this->prefixDirsPsr4->get($namespace);
    }

    protected function parsePrefixDirsPsr4()
    {
        foreach ($this->classLoaders as $loader){
            $this->parseLoader($loader);
        }
    }

    protected function parseLoader(ClassLoader $loader)
    {
        $prefixes = $loader->getPrefixesPsr4();
        foreach ($prefixes as $namespace => $dirs){
            $path = $this->parsePath($dirs[0]);
            if(str_starts_with($path,app()->getRootPath().'vendor') === false){
                $this->prefixDirsPsr4[UtilsTools::replaceNamespace($namespace)] = UtilsTools::replaceSeparator($path);
            }
        }
    }

    protected function parsePath(string $path):string
    {
        $path = str_replace($this->composerPath,'',$path);
        $count = substr_count($path,'/..');
        $rootPath = $this->composerPath;
        for ($i=0;$i < $count;$i++){
            $rootPath = dirname($rootPath);
        }
        $path = str_replace('/..','',$path);
        return $rootPath.$path;
    }
}
