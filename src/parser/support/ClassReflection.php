<?php


namespace jsy\generator\parser\support;


use jsy\base\utils\UtilsTools;
use jsy\generator\utils\ClassParseUtils;
use phpDocumentor\Reflection\DocBlockFactory;
use think\Collection;
use think\Exception;
use think\helper\Str;

class ClassReflection
{
    const COMMON_CLASS='class';
    const TRAIT_CLASS='trait';
    const INTERFACE_CLASS='interface';
    const ABSTRACT_CLASS='abstract';

    protected string $classname;
    protected string $shortName;
    protected string $filename;
    protected string $namespace;
    protected array $classBody = [];
    /**
     * @var \ReflectionClass
     */
    protected \ReflectionClass $ref;
    protected string $type = 'class';
    protected array $phpdoc = [];
    protected array $extend = [];
    protected array $imports = [];
    protected array $tokens = [];
    protected array $sourceArray = [];
    protected array $importArray = [];
    /**
     * @var \jsy\generator\parser\support\ClassProperty[]
     */
    protected array $selfProperties = [];
    protected array $selfPropertyNames = [];
    protected array $selfConstants = [];
    /**
     * @var Collection
     */
    protected Collection $selfClassMethods;

    protected array $selfMethods = [];
    protected int $importStartLine = 0;
    protected int $classLine = 0;
    protected array $aliases = [];
    protected array $useTrait = [];
    protected array $useInterface = [];
    public function __construct(string $classname,$fullFilename)
    {

        $this->classname = UtilsTools::replaceNamespace($classname);
        $this->filename = $fullFilename;
        $this->shortName = class_basename($classname);
        $this->namespace = UtilsTools::getNamespacePrefix($this->classname);
        $this->selfClassMethods = new Collection();
    }

    public function handle(bool $load = true)
    {
        if(class_exists($this->classname) && $load){

            $this->ref = new \ReflectionClass($this->classname);
            $this->filename = $this->ref->getFileName();
            $this->shortName = $this->ref->getShortName();
            $this->namespace = $this->ref->getNamespaceName();
            $this->parseType();

            $this->parsePhpdoc();

            $this->parseExtend();

            $this->parseSelfConstants();

            $this->parseClassSource();

            $this->parseSelfMethods();
        }
        return $this;
    }


    /**
     * @desc 当前类是否有指定的方法
     * @param string $method
     * @return bool
     */
    public function hasSelfMethod(string $method): bool
    {
        return $this->selfClassMethods->where('name','=',$method)->isEmpty() === false;
    }

    public function hasExtend(): bool
    {
        return empty($this->extend) === false;
    }

    public function hasSelfProperty(string $property):bool
    {
        return isset($this->selfProperties[$property]);
    }

    public function addSelfProperty(ClassProperty $property,$force = false): ClassReflection
    {
        if($this->hasSelfProperty($property->getName()) && $force === false){
            return $this;
        }
        $this->selfProperties[$property->getName()] = $property;
        return $this;
    }

    /**
     * @return array
     */
    public function getImports(): array
    {
        return $this->imports;
    }

    /**
     * @return \ReflectionClass
     */
    public function getRef(): \ReflectionClass
    {
        return $this->ref;
    }


    public function getSelfClassMethods(): Collection
    {
        return $this->selfClassMethods;
    }

    /**
     * @return \jsy\generator\parser\support\ClassProperty[]
     */
    public function getSelfProperties(): array
    {
        return $this->selfProperties;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getSourceCodeArray(): array
    {
        $this->classBody[] = "<?php";
        $this->classBody[] = "";
        $this->classBody[] = sprintf("namespace %s;",$this->namespace);
        $this->classBody[] = "";
        $this->renderImport();
        $this->classBody[] = "";
        $this->classBody[] = "";
        // $this->renderPhpdoc();
        $this->renderClassBody();
        $this->renderTrait();
        $this->renderProperties();
        $this->renderSelfMethods();
        $this->classBody[] = "}";
        return $this->classBody;
    }

    protected function renderSelfMethods()
    {
        foreach ($this->selfClassMethods->order('sort') as $classMethod){
            $this->classBody[] = "";
            $this->classBody[] = $classMethod->render();
        }
    }

    public function getSourceCode():string
    {
        return implode("\n",$this->getSourceCodeArray());
    }


    public function getClassMethod(string $name): ?ClassMethod
    {
        return $this->selfClassMethods[$name];
    }

    public function createNewMethod(): ClassMethod
    {
        return new ClassMethod($this->classname,true);
    }

    public function createPropertyObject(): ClassProperty
    {
        return new ClassProperty($this->classname);
    }

    public function getPhpdoc(): array
    {
        return $this->phpdoc;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExtend(): array
    {
        return $this->extend;
    }

    public function addImport(string $class):self
    {
        $className = UtilsTools::replaceNamespace($class);
        if(array_key_exists($className,$this->imports) === false){
            $this->imports[$className] = ClassParseUtils::classInfo($className);
        }
        return $this;
    }

    public function removeImport(string $class):self
    {
        $className = UtilsTools::replaceNamespace($class);
        if (isset($this->imports[$className]) === true){
            unset($this->imports[$className]);
        }
        return $this;
    }

    public function removeMethod(string $method):self
    {
        if (isset($this->selfClassMethods[$method]) === true){
            unset($this->selfClassMethods[$method]);
        }
        return $this;
    }

    // 设置 class 父类
    public function setExtend($class): ClassReflection
    {
        $className = UtilsTools::replaceNamespace($class);
        if(class_basename($className)!==$this->shortName){
            $this->addImport($className);
        }
        $ref = ClassParseUtils::classInfo($className);
        $this->extend = $ref;
        return $this;
    }
    // 设置class 类型
    public function setClassType(string $classType): ClassReflection
    {
        if(in_array($classType,['class','trait','interface','abstract']) === false){
            throw new Exception('class type必须是：class、trait、interface、abstract');
        }
        $this->type = $classType;
        return $this;
    }
    // 增加implements
    public function addInterface($class): ClassReflection
    {
        $className = UtilsTools::replaceNamespace($class);
        if(in_array(class_basename($className),$this->useInterface) === false) {
            $this->addImport($className);
            $this->useInterface[] = class_basename($className);
        }
        return $this;
    }
    // 增加traits
    public function addTrait($class): ClassReflection
    {
        $className = UtilsTools::replaceNamespace($class);
        if(in_array(class_basename($className),$this->useTrait) === false){
            $this->addImport($className);
            $this->useTrait[] = class_basename($className);
        }
        return $this;
    }
    // 增加注释
    public function addPhpdoc($name,$value): ClassReflection
    {
        if(is_array($value)){
            $this->phpdoc[$name] = implode('|' , $value);
        }else{
            $this->phpdoc[$name] = strval($value);
        }
        return $this;
    }

    // 增加类方法
    public function addMethod(ClassMethod $method): ClassReflection
    {
        $this->selfClassMethods[$method->getName()] = $method;
        return $this;
    }

    /**
     * @desc 获取类的Phpdoc
     */
    protected function parsePhpdoc()
    {
        if($this->ref->getDocComment() !== false){
            $tags = DocBlockFactory::createInstance()->create($this->ref->getDocComment())->getTags();
            $summary = DocBlockFactory::createInstance()->create($this->ref->getDocComment())->getSummary();
            if(strlen($summary) > 0){
                $summaryArray = explode("\n",trim($summary));
                foreach ($summaryArray as $item){
                    $temp = explode(' ',trim($item));

                    $name = array_shift($temp);
                    $phpdoc = new Phpdoc();
                    $phpdoc->setName($name);
                    $value = implode(' ',$temp);
                    $phpdoc->setValue(ltrim($value));
                    $this->phpdoc[] = $phpdoc;
                }
            }

            foreach ($tags as $tag){
                $this->phpdoc[] = new Phpdoc($tag);
            }
        }
    }

    /**
     * @desc 获取父类名称
     */
    protected function parseExtend()
    {
        if($this->ref->getParentClass() !== false){
            $this->setExtend($this->ref->getParentClass()->getName());
        }
    }

    /**
     * @desc 获取类的类型
     * @return $this
     */
    protected function parseType(): ClassReflection
    {
        if($this->ref->isAbstract()){
            $this->type = self::ABSTRACT_CLASS;
            return $this;
        }
        if($this->ref->isInterface()){
            $this->type = self::INTERFACE_CLASS;
            return $this;
        }
        if($this->ref->isTrait()){
            $this->type = self::TRAIT_CLASS;
            return $this;
        }
        $this->type = self::COMMON_CLASS;
        return $this;
    }

    protected function parseSelfConstants()
    {
        foreach ($this->ref->getReflectionConstants() as $constant){
            if($constant->getDeclaringClass()->getName() === $this->ref->getName()){
                $this->selfConstants[$constant->getName()] = $constant->getValue();
            }
        }
    }

    protected function parseImportAs(string $class,$alias = null)
    {
        if(strpos($class,' as ')){
            $array = explode(' as ',$class);
        }
        if(strpos($class,' AS ')){
            $array = explode(' AS ',$class);
        }
        if(strpos($class,' As ')){
            $array = explode(' As ',$class);
        }
        if(strpos($class,' aS ')){
            $array = explode(' aS ',$class);
        }

        if(isset($array)){
            $res =  [
                'class'=>ParseUtil::parseNamespace($array[0]),
                'alias'=>trim($array[1])
            ];
        }else{
            $res =  [
                'class'=>ParseUtil::parseNamespace($class),
                'alias'=>is_null($alias) ? class_basename(ParseUtil::parseNamespace($class)) : $alias
            ];
        }

        return $res;
    }

    protected function parseClassSource()
    {
        $this->sourceArray = file($this->ref->getFileName());
        $this->tokens = token_get_all(implode($this->sourceArray));
        $this->parseSourceArray();
        $this->parseImportTokens();

    }

    protected function parseImportTokens()
    {
        $importSource = [];
        foreach ($this->sourceArray as $key => $code){
            if($key < $this->classLine - 1){
                $importSource[] = $code;
            }
        }

        $this->importArray = token_get_all(implode($importSource));
        for($index = 0;isset($this->importArray[$index]); $index++){
            // 获取 use的起始行
            if($this->importStartLine === 0){
                if(isset($this->importArray[$index][0]) && $this->importArray[$index][0] === T_USE){
                    $this->importStartLine =  intval($this->importArray[$index][2]) -1;
                }
            }
            // use 声明整理

            $this->parseSourceImports($index);

        }
    }

    protected function parseSourceArray()
    {
        for ($index = 0;isset($this->tokens[$index]);$index++){
            if($this->classLine === 0){
                // 获取 class 关键字所在的行
                if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_CLASS){
                    $this->classLine =  intval($this->tokens[$index][2]);
                }
            }else{
                // 获取已使用的trait
                if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_USE ){
                    $this->parseUseTrait($index);
                }
            }
            if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_FUNCTION && isset($this->tokens[$index + 2][1])){
                $this->selfMethods[$this->tokens[$index + 2][1]] =  ['name' => $this->tokens[$index + 2][1]];
            }
            if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_IMPLEMENTS){
                $this->parseUseInterface($index);
            }
            if(isset($this->tokens[$index][0]) && in_array($this->tokens[$index][0],[T_PROTECTED,T_PRIVATE,T_PUBLIC])){
                $this->parseSelfProperties($index);
            }
        }
        $this->parsePropertiesToObject();
    }

    protected function parseUseTrait(int $start)
    {
        for($index = $start + 1;isset($this->tokens[$index]);$index++){
            if($this->tokens[$index] === ';'){
                break;
            }
            if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_STRING){
                $this->useTrait[] = $this->tokens[$index][1];
            }
        }
    }

    protected function parseSelfProperties(int $start)
    {
        for($index = $start;isset($this->tokens[$index]);$index++){
            if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_FUNCTION){
                break;
            }
            if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_VARIABLE){
                $this->selfPropertyNames[] = ltrim($this->tokens[$index][1],'$');
                break;
            }
        }
    }

    protected function parsePropertiesToObject()
    {
        $selfProperties = $this->ref->getDefaultProperties();
        foreach ($this->selfPropertyNames as $property){
            $ref = $this->ref->getProperty($property);
            $classProperty = new ClassProperty($this->classname);
            $classProperty->setName($ref->getName());
            $value = $selfProperties[$ref->getName()] ?? null;
            $classProperty->setValue($value);
            if($ref->hasType()){
                $classProperty->setType($ref->getType());
            }
            if($ref->isPrivate()){
                $classProperty->setAccess(T_PRIVATE);
            }
            if($ref->isProtected()){
                $classProperty->setAccess(T_PROTECTED);
            }
            if($ref->isPublic()){
                $classProperty->setAccess(T_PUBLIC);
            }
            if($ref->isStatic()){
                $classProperty->setIsStatic(true);
            }
            $this->selfProperties[$ref->getName()] = $classProperty;
        }
    }

    protected function parseUseInterface(int $start)
    {
        for ($index = $start;isset($this->tokens[$index]);$index++){
            if(isset($this->tokens[$index]) && $this->tokens[$index] === '{'){
                break;
            }else{
                if(isset($this->tokens[$index][0]) && $this->tokens[$index][0] === T_STRING){
                    foreach ($this->ref->getInterfaceNames() as $interface){
                        if(strtolower(class_basename($interface)) === strtolower($this->tokens[$index][1])){
                            $this->useInterface[] = $interface;
                        }
                    }
                }
            }
        }
    }

    // 处理类头部一行代码，主要用于获取use的类
    protected function parseSourceImports(int $start)
    {
        $importString = '';
        if(isset($this->importArray[$start][0]) && $this->importArray[$start][0]===T_USE){

            for($useIndex=$start; $this->importArray[$useIndex]!==';'; $useIndex++){

                $sr = $this->importArray[$useIndex][0] ?? $this->importArray[$useIndex];

                if(in_array($sr,[T_STRING,T_NS_SEPARATOR,T_NAME_QUALIFIED,T_WHITESPACE,T_AS,'{','}',','])){
                    $importString .= $this->importArray[$useIndex][1] ?? $this->importArray[$useIndex];
                }
            }
        }
        $importString = trim($importString);
        if(empty($importString) === false){
            $importString = trim($importString);
            $importLine = ParseUtil::clearContext($importString);
            preg_match('/(.*)\{(.*)\}/',$importString,$matches);
            if(isset($matches[1]) && isset($matches[2])){
                foreach (explode(',',$matches[2]) as $object){
                    $this->addImport(trim($matches[1]).trim($object));
                }
            }else{
                foreach (explode(',',$importLine) as $object){
                    $this->addImport($object);
                }
            }
        }
    }

    protected function parseSelfMethods()
    {
        foreach ($this->selfMethods as $method){
            $this->parseSelfMethodBody($method['name']);
        }
    }

    protected function parseSelfMethodBody(string $name)
    {
            $classMethod = new ClassMethod($this->classname);
            $ref = $this->ref->getMethod($name);
            $classMethod->setName($name);
            $classMethod->setStatic($ref->isStatic());
            if($ref->isProtected()){
                $classMethod->setAccess(T_PROTECTED);
            }
            if($ref->isPrivate()){
                $classMethod->setAccess(T_PRIVATE);
            }
            if($ref->getReturnType() !== null){
                $classMethod->setReturnType($ref->getReturnType()->getName());
            }
            if($ref->getDocComment() !== false){
                $tags = DocBlockFactory::createInstance()->create($ref->getDocComment())->getTags();
                foreach ($tags as $key => $tag){
                    $phpdoc = new Phpdoc($tag);
                    $phpdoc->setName($tag->getName());
                    $classMethod->addPhpdoc($phpdoc);
                }
            }
            $methodLines = [];
            for ($index = $ref->getStartLine()+1 ;$index < $ref->getEndLine() - 1;$index++){
                $methodLines[] = $this->sourceArray[$index];
            }

            $classMethod->setBody($methodLines);
            if($ref->getNumberOfParameters() > 0){
                foreach ($ref->getParameters() as $parameter){
                    $param = new MethodParam($parameter->getName(),$this->classname);
                    $param->setClassname($this->classname);
                    $param->setRef($parameter);
                    $classMethod->addParam($param);
                }
            }
            $this->selfClassMethods[$name] = $classMethod;

        return $this;
    }

    protected function renderImport()
    {
        foreach ($this->imports as $import){
            $this->classBody[] = 'use '.$import['classname'].';';
        }
    }

    protected function renderPhpdoc()
    {
        $this->classBody[] = "/**";
        if(empty($this->phpdoc)){
            $this->getDefaultPhpdoc();
        }else{
            foreach ($this->phpdoc as $phpdoc){
                $this->classBody[] = $phpdoc->render();
            }
        }
        $this->classBody[] = " */";
    }

    protected function getDefaultPhpdoc()
    {
        $this->classBody[] = " * Class ".$this->shortName;
        $this->classBody[] = " * ClassName ".$this->classname;
        $this->classBody[] = " * @package ".$this->namespace;
    }


    protected function renderClassBody()
    {
        $this->classBody[] = $this->parseClassStatement();
        $this->classBody[] = "{";
    }

    protected function renderTrait()
    {
        if(empty($this->useTrait) === false){
            $traitLine = "\tuse ";
            foreach ($this->useTrait as $trait){
                $traitLine .=class_basename($trait).',';
            }
            $this->classBody[] = rtrim($traitLine,',').';';
        }

    }

    protected function renderProperties(): ClassReflection
    {
        if(empty($this->selfProperties)){
            return $this;
        }
        foreach ($this->selfProperties as  $property){
            $this->classBody[] = $property->render();
        }
        return $this;
    }


    protected function parseClassStatement(): string
    {
        $classStatement = $this->type === 'abstract' ? 'abstract class' : $this->type;
        $classStatement .=' '.$this->shortName;
        if(empty($this->extend) === false){
            if($this->extend['short_name'] !== $this->shortName){
                $classStatement.=' extends '.$this->extend['short_name'];
            }else{
                $classStatement .=' extends \\'.$this->extend['classname'];
            }

        }

        if(empty($this->useInterface)===false){
            $classStatement.=' implements ';
            foreach ($this->useInterface as $interface){
                $classStatement .=class_basename($interface).',';
            }
        }
        return rtrim($classStatement,',');
    }


}
