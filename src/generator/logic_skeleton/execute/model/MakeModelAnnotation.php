<?php


namespace maodou\generator\generator\logic_skeleton\execute\model;


use maodou\run\base\support\ModelAnnotationMethod;
use maodou\base\utils\UtilsTools;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Serializer as DocBlockSerializer;
use phpDocumentor\Reflection\DocBlock\StandardTagFactory;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use phpDocumentor\Reflection\Types\This;
use think\Exception;
use think\facade\Config;
use think\helper\Str;
use think\Model;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use think\model\relation\MorphTo;

class MakeModelAnnotation
{
    /**
     * @var \ReflectionClass
     */
    protected $ref;
    protected $modelClass;
    /**
     * @var Model
     */
    protected $modelInstance;
    protected $timestampFields = [];
    protected $dateFormat;
    protected $properties;
    protected $methods = [];
    public function handle(string $modelClass)
    {
        if(class_exists($modelClass) === false){
            throw new Exception($modelClass.' 不存在');
        }
        $this->ref = new \ReflectionClass($modelClass);
        if($this->ref->isSubclassOf(Model::class) === false){
            throw new Exception($modelClass.' 不是 '.Model::class.' 的子类');
        }
        $this->modelClass = $modelClass;
        $this->modelInstance = new $modelClass;
        $this->getPropertiesFromTable();
        $this->getPropertiesFromMethods();
        foreach (ModelAnnotationMethod::getAll() as $name => $method){
            $this->setMethod($name,$method['type'],$method['arguments']);
        }
        return $this->createPhpDocs();
    }

    protected function createPhpDocs()
    {

        $namespace   = $this->ref->getNamespaceName();
        $classname   = $this->ref->getShortName();
        $originalDoc = $this->ref->getDocComment();
        $context     = new Context($namespace);
        $summary     = "Class {$classname}";

        $fqsenResolver      = new FqsenResolver();
        $tagFactory         = new StandardTagFactory($fqsenResolver);
        $descriptionFactory = new DescriptionFactory($tagFactory);
        $typeResolver       = new TypeResolver($fqsenResolver);

        $properties = [];
        $methods    = [];
        $tags       = [];
        foreach ($this->properties as $name => $property) {
            if (in_array($name, $properties)) {
                continue;
            }
            $name = "\${$name}";
            $body =
            $body = trim("{$property['type']} {$name} {$property['comment']}");
            if ($property['read'] && $property['write']) {
                $tag = DocBlock\Tags\Property::create($body, $typeResolver, $descriptionFactory, $context);
            } elseif ($property['write']) {
                $tag = DocBlock\Tags\PropertyWrite::create($body, $typeResolver, $descriptionFactory, $context);
            } else {
                $tag = DocBlock\Tags\PropertyRead::create($body, $typeResolver, $descriptionFactory, $context);
            }

            $tags[] = $tag;
        }
        ksort($this->methods);

        foreach ($this->methods as $name => $method) {
            if (in_array($name, $methods)) {
                continue;
            }
            $remark = $method['remark'] ?? '';
            $arguments = implode(', ', $method['arguments']);
            $body = "static {$method['type']} {$name}({$arguments}){$remark}";
            $tag    = DocBlock\Tags\Method::create($body, $typeResolver, $descriptionFactory, $context);
            $tags[] = $tag;
        }
        $phpdoc = new DocBlock($summary, null, $tags, $context);

        $serializer = new DocBlockSerializer();

        $docComment = $serializer->getDocComment($phpdoc);

        $filename = $this->ref->getFileName();

        $contents = file_get_contents($filename);
        if ($originalDoc) {
            $contents = str_replace($originalDoc, $docComment, $contents);
        } else {
            $needle  = "class {$classname}";
            $replace = "{$docComment}" . PHP_EOL . "class {$classname}";
            $pos     = strpos($contents, $needle);
            if (false !== $pos) {
                $contents = substr_replace($contents, $replace, $pos, strlen($needle));
            }
        }
        if (file_put_contents($filename, $contents)) {
            return true;
        }
        throw new Exception('生成模型注解失败');
    }

    protected function getPropertiesFromTable()
    {
        $properties = $this->ref->getDefaultProperties();
        $this->parseTimestampFields($properties);
        try {
            $fields = $this->modelInstance->getFields();
        } catch (\Exception $e) {
            throw new Exception($e);
        }
        foreach ($fields as $name => $field) {

            if (in_array($name, (array) $properties['disuse'])) {
                continue;
            }

            if (in_array($name,$this->timestampFields)) {
                // 获取时间戳类型
                $type = $this->dateFormat;
            } elseif (!empty($properties['type'][$name])) {
                $type = $properties['type'][$name];
                if (is_array($type)) {
                    list($type, $param) = $type;
                } elseif (strpos($type, ':')) {
                    list($type, $param) = explode(':', $type, 2);
                }
                switch ($type) {
                    case 'timestamp':
                    case 'datetime':
                        $format = !empty($param) ? $param : $this->dateFormat;

                        if (false !== strpos($format, '\\')) {
                            $type = "\\" . $format;
                        } else {
                            $type = 'string';
                        }
                        break;
                    case 'json':
                        $type = 'array';
                        break;
                    case 'serialize':
                        $type = 'mixed';
                        break;
                    default:
                        if (false !== strpos($type, '\\')) {
                            $type = "\\" . $type;
                        }
                }
            } else {
                if (!preg_match('/^([\w]+)(\(([\d]+)*(,([\d]+))*\))*(.+)*$/', $field['type'], $matches)) {
                    continue;
                }
                $limit     = null;
                $precision = null;
                $type      = $matches[1];
                if (count($matches) > 2) {
                    $limit = $matches[3] ? (int) $matches[3] : null;
                }

                if ($type === 'tinyint' && $limit === 1) {
                    $type = 'boolean';
                }

                switch ($type) {
                    case 'varchar':
                    case 'char':
                    case 'tinytext':
                    case 'mediumtext':
                    case 'longtext':
                    case 'text':
                    case 'timestamp':
                    case 'date':
                    case 'time':
                    case 'guid':
                    case 'datetimetz':
                    case 'datetime':
                    case 'set':
                    case 'enum':
                        $type = 'string';
                        break;
                    case 'tinyint':
                    case 'smallint':
                    case 'mediumint':
                    case 'int':
                    case 'bigint':
                        $type = 'integer';
                        break;
                    case 'decimal':
                    case 'float':
                        $type = 'float';
                        break;
                    case 'boolean':
                        $type = 'boolean';
                        break;
                    case 'json':
                        $type = $this->parseJsonType($name);
                        break;
                    default:
                        $type = 'mixed';
                        break;
                }
            }
            if(empty($field['comment']) && $field['primary'] ){
                $comment = "主键ID";
            }else{
                $comment = $field['comment'];
            }
            if($field['notnull'] === false){
                $type .= '|null';
            }
            $this->setProperty($name, $type, true, true, $comment);
        }
    }

    protected function parseJsonType($name): string
    {
        $properties = $this->ref->getDefaultProperties();
        if(isset($properties['json']) && is_array($properties['json']) && in_array($name,$properties['json'])){
            if(isset($properties['jsonAssoc']) && $properties['jsonAssoc']===true){
                return 'array';
            }else{
                return 'object';
            }
        }else{
            return 'mixed';
        }
    }

    protected function getPropertiesFromMethods()
    {
        $methods  = $this->ref->getMethods();

        foreach ($methods as $method) {

            if ($method->getDeclaringClass()->getName() !== Model::class) {

                $methodName = $method->getName();
                if (Str::startsWith($methodName, 'get') && Str::endsWith(
                        $methodName,
                        'Attr'
                    ) && 'getAttr' !== $methodName) {
                    //获取器
                    $this->parseGetAttr($method);

                } elseif (Str::startsWith($methodName, 'set') && Str::endsWith(
                        $methodName,
                        'Attr'
                    ) && 'setAttr' !== $methodName) {
                    //修改器
                    $name = Str::snake(substr($methodName, 3, -4));
                    if (!empty($name)) {
                        $this->setProperty($name, null, null, true);
                    }
                } elseif (Str::startsWith($methodName, 'scope')) {
                    //查询范围
                    $this->parseScopeMethod($method);
                } elseif ($method->isPublic() && $method->getNumberOfRequiredParameters() == 0) {
                    //关联对象
                    try {
                        $return = $method->invoke($this->modelInstance);

                        if ($return instanceof Relation) {

                            $name = Str::snake($methodName);
                            if ($return instanceof HasOne || $return instanceof BelongsTo || $return instanceof MorphOne) {
                                $this->setProperty($name, "\\" . get_class($return->getModel()), true, null);
                            }

                            if ($return instanceof HasMany || $return instanceof HasManyThrough || $return instanceof BelongsToMany) {
                                $this->setProperty($name, "\\" . get_class($return->getModel()) . "[]", true, null);
                            }

                            if ($return instanceof MorphTo || $return instanceof MorphMany) {
                                $this->setProperty($name, "mixed", true, null);
                            }
                        }
                    } catch (\Exception $e) {
                    } catch (\Throwable $e) {
                    }
                }
            }
        }
    }

    protected function parseGetAttr(\ReflectionMethod $method)
    {
        $name = Str::snake(substr($method->getShortName(), 3, -4));
        if(empty($name)){
            return $this;
        }

        if($method->getReturnType() === null){
            $type = $this->getReturnTypeFromDocBlock($method);
        }else{
            $refType = $method->getReturnType();
            if ($refType instanceof \ReflectionUnionType){
                $types = $refType->getTypes();
            }else{
                $types = [$refType];
            }
            $realTypes = [];
            foreach ($types as $item){
                $temp = $item->getName();
                if(class_exists($temp)){
                    $temp = UtilsTools::replaceNamespace($temp);
                    $temp = '\\'.$temp;
                    $realTypes[] = $temp;
                }
                if($item->allowsNull()){
                    $realTypes[] = 'null';
                }
            }
            $type = implode('|',$realTypes);
        }
        if($method->getDocComment()!==false){
            $comment = $this->parseMethodComment($method);
        }else{
            $comment = null;
        }

        $this->setProperty($name, $type, true, null,$comment);
        return $this;
    }

    protected function parseScopeMethod(\ReflectionMethod $method)
    {
        $name = Str::camel(substr($method->getName(), 5));

        if (!empty($name)) {
            $args = $this->getParameters($method);
            if($method->getDocComment()!==false){
                $comment = $this->parseMethodComment($method);
            }else{
                $comment = '';
            }
            array_shift($args);
            $this->setMethod($name, "\\think\\db\\Query", $args,$comment);
        }
    }

    protected function parseMethodComment(\ReflectionMethod $method)
    {
        $tags = DocBlockFactory::createInstance()->create($method)->getTags();
        foreach ($tags as $tag){
            if($tag->getName() === 'desc' || $tag->getName() === 'description'){
                return  $tag->getDescription()->render();
            }
        }
        return null;
    }

    protected function setMethod($name, $type = '', $arguments = [],$remark = '')
    {
        if($name === 'find'){
            $type = empty($type) ? 'array|null|'.$this->ref->getShortName() : $type.'|'.$this->ref->getShortName();
        }
        if($name === 'findOrEmpty'){
            $type = empty($type) ? 'array|'.$this->ref->getShortName() : $type.'|'.$this->ref->getShortName();
        }
        $methods = array_change_key_case($this->methods, CASE_LOWER);
        if (!isset($methods[strtolower($name)])) {
            $this->methods[$name]              = [];
            $this->methods[$name]['type']      = $type;
            $this->methods[$name]['arguments'] = $arguments;
            $this->methods[$name]['remark'] = $remark;
        }
    }

    protected function getParameters($method): array
    {
        //Loop through the default values for paremeters, and make the correct output string
        $params            = [];
        $paramsWithDefault = [];
        /** @var \ReflectionParameter $param */
        foreach ($method->getParameters() as $param) {
            if($param->getType() !== null){
                $paramStr = $param->getType()->getName().' ';
            }else{
                $paramStr = 'mixed ';
            }
            $paramStr   .= '$' . $param->getName();
            $params[]   = $paramStr;
            if ($param->isOptional() && $param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
                if (is_bool($default)) {
                    $default = $default ? 'true' : 'false';
                } elseif (is_array($default)) {
                    $default = '[]';
                } elseif (is_null($default)) {
                    $default = 'null';
                } else {
                    $default = "'" . trim($default) . "'";
                }
                $paramStr .= " = $default";
            }
            $paramsWithDefault[] = $paramStr;
        }
        return $paramsWithDefault;
    }

    protected function parseTimestampFields(array $properties)
    {
        $autoTimestamp = isset($properties['autoWriteTimestamp'])===false ?  Config::get('database.auto_timestamp') : $properties['autoWriteTimestamp'];
        if($autoTimestamp === false){
            return $this;
        }
        $dateFormat = empty($properties['dateFormat']) ? Config::get('database.datetime_format'): $properties['dateFormat'];

        switch (true){
            case $dateFormat === 'int' || $dateFormat === false:
                $this->dateFormat = 'int';
                break;
            case class_exists($dateFormat):
                $this->dateFormat = $dateFormat;
                break;
            default:
                $this->dateFormat = 'string';
        }

        $createTimeField = empty($properties['createTime']) ? 'create_time' : $properties['createTime'];
        if($createTimeField !== false){
            $this->timestampFields[] = $createTimeField;
        }
        $updateTimeField = empty($properties['updateTime']) ? 'update_time' : $properties['updateTime'];
        if($updateTimeField !== false){
            $this->timestampFields[] = $updateTimeField;
        }
        return $this;
    }

    protected function getReturnTypeFromDocBlock(\ReflectionMethod $reflection)
    {
        $type = null;
        try {
            $phpdoc = DocBlockFactory::createInstance()->create($reflection, new Context($reflection->getDeclaringClass()->getNamespaceName()));
            if ($phpdoc->hasTag('return')) {
                /** @var \phpDocumentor\Reflection\DocBlock\Tags\Return_ $returnTag */
                $returnTag = $phpdoc->getTagsByName('return')[0];
                $type      = $returnTag->getType();
                if ($type instanceof This || $type instanceof Static_ || $type instanceof Self_) {
                    $type = "\\" . $reflection->getDeclaringClass()->getName();
                }
            }
        } catch (\InvalidArgumentException $e) {

        }
        return is_null($type) ? null : (string) $type;
    }

    protected function setProperty($name, $type = null, $read = null, $write = null, $comment = '')
    {
        if (!isset($this->properties[$name])) {
            $this->properties[$name]            = [];
            $this->properties[$name]['type']    = 'mixed';
            $this->properties[$name]['read']    = false;
            $this->properties[$name]['write']   = false;
            $this->properties[$name]['comment'] = (string) $comment;
        }
        if (null !== $type) {
            $this->properties[$name]['type'] = $type;
        }
        if (null !== $read) {
            $this->properties[$name]['read'] = $read;
        }
        if (null !== $write) {
            $this->properties[$name]['write'] = $write;
        }
    }
}
