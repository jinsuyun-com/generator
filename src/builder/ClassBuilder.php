<?php


namespace jsy\generator\builder;


use jsy\base\exception\AppException;
use jsy\base\utils\UtilsTools;
use jsy\generator\parser\support\ClassMethod;
use jsy\generator\parser\support\ClassProperty;
use jsy\generator\parser\support\ClassReflection;
use jsy\generator\utils\classloader\facade\JsyClassLoader;
use think\helper\Str;

class ClassBuilder
{

    protected static $classRef;
    protected string $fullClassName;
    protected ?string $file;
    protected bool $isExist = true;

    /**
     * @param string $fullClassName 完整类名
     * @param string|null $fullFilename 文件路径
     * @param bool $load 是否反射类
     */
    public function __construct(string $fullClassName,?string $fullFilename = null,bool $load = true)
    {
        $this->fullClassName = $fullClassName;
        if(is_null($fullFilename)){
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$fullClassName.'.php');
        }else{
            if(Str::endsWith($fullFilename,'.php') === false){
                $fullFilename = $fullFilename.'.php';
            }
            $fullFilename = UtilsTools::replaceSeparator($fullFilename);
            $fullFilename = str_replace(app()->getRootPath(),'',$fullFilename);
            $this->file = UtilsTools::replaceSeparator(app()->getRootPath().$fullFilename);
        }

        $this->isExist = class_exists($fullClassName);

        self::$classRef[$fullClassName] = new ClassReflection($fullClassName,$this->file);
        self::$classRef[$fullClassName]->handle($load);
    }

    public function getClassRef(): ClassReflection
    {
        return self::$classRef[$this->fullClassName];
    }


    public function hasExtend()
    {
        return self::$classRef[$this->fullClassName]->hasExtend();
    }

    public static function staticCreateNewMethod(string $fullClassName): ClassMethod
    {
        return self::$classRef[$fullClassName]->createNewMethod();
    }


    public static function staticSetExtend(string $fullClassName,string $class)
    {
        self::$classRef[$fullClassName]->setExtend($class);
    }

    public static function staticSetClassType(string $fullClassName,string $classType)
    {
        self::$classRef[$fullClassName]->setClassType($classType);
    }

    public static function staticAddInterface(string $fullClassName,string $class)
    {
        self::$classRef[$fullClassName]->addInterface($class);
    }

    public static function staticAddTrait(string $fullClassName,string $class)
    {
        self::$classRef[$fullClassName]->addInterface($class);
    }

    public static function staticAddProperty(string $fullClassName,ClassProperty $property)
    {
        self::$classRef[$fullClassName]->addProperty($property);
    }

    public static function staticAddMethod(string $fullClassName,ClassMethod $method)
    {
        self::$classRef[$fullClassName]->addMethod($method);
    }
    public static function staticAddImport(string $fullClassName,$class)
    {
        self::$classRef[$fullClassName]->addImport($class);
    }

    public function createNewMethod(): ClassMethod
    {
        return self::$classRef[$this->fullClassName]->createNewMethod();
    }

    public function createNewProperty(): ClassProperty
    {
        return self::$classRef[$this->fullClassName]->createPropertyObject();
    }

    public function hasSelfMethod(string $method):bool
    {
        return self::$classRef[$this->fullClassName]->hasSelfMethod($method);
    }

    public function removeMethod(string $method):ClassBuilder
    {
         self::$classRef[$this->fullClassName]->removeMethod($method);
        return $this;
    }

    public function hasSelfProperty(string $property):bool
    {
        return self::$classRef[$this->fullClassName]->hasSelfProperty($property);
    }

    public function addSelfProperty(ClassProperty $property,$force = false): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->addSelfProperty($property,$force);
        return $this;
    }

    public function addImport($class): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->addImport($class);
        return $this;
    }

    public function removeImport($class): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->removeImport($class);
        return $this;
    }

    public function getFullClassName():string
    {
        return $this->fullClassName;
    }

    public function setExtend($class): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->setExtend($class);
        return $this;
    }

    public function setClassType(string $classType): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->setClassType($classType);
        return $this;
    }

    public function addInterface($class): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->addInterface($class);
        return $this;
    }

    public function addTrait($class): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->addTrait($class);
        return $this;
    }


    public function addMethod(ClassMethod $method): ClassBuilder
    {
        self::$classRef[$this->fullClassName]->addMethod($method);
        return $this;
    }

    public function build($overwrite=false,string $saveAs = ''): ClassBuilder
    {
        /**
         * @var ClassReflection $classRef
         */
        $classRef = self::$classRef[$this->fullClassName];

        if (empty($saveAs)){
            $filename = $classRef->getFilename();
            $filename = UtilsTools::replaceSeparator($filename);
            $filename = app()->getRootPath().str_replace(app()->getRootPath(),'',$filename);
        }else{
            $filename = $saveAs;
        }



        $dir = dirname($filename);
        if(file_exists($filename)){
            if($overwrite === false){
                copy($filename,$filename.'-bak-'.Str::random(6,1));
            }
            unlink($filename);
        }
        if(is_dir($dir) === false){
            mkdir($dir,0755,true);
        }

        file_put_contents($filename,$classRef->getSourceCode());
        return $this;
    }



    protected function createClass()
    {
        $stub = __DIR__ . DIRECTORY_SEPARATOR.'stub'.DIRECTORY_SEPARATOR.'EmptyClass.stub';
        $array = explode('\\',$this->fullClassName);
        $className = array_pop($array);
        $namespace = implode('\\',$array);
        return str_replace([
            '{%namespace%}',
            '{%className%}',
        ], [
            $namespace,
            $className,
        ], file_get_contents($stub));
    }





    public function handle()
    {
        return $this;
    }

}
