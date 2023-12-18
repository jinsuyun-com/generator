<?php


namespace jsy\generator\parser\support\model;


use jsy\generator\parser\support\MethodParam;
use jsy\generator\support\fields\constant\JsyField;
use jsy\generator\support\fields\ModelSchemaField;
use jsy\generator\utils\TypeUtils;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use think\Collection;
use think\helper\Str;
use think\Model;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\HasOneThrough;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use think\model\relation\MorphTo;
use jsy\generator\support\fields\model\ModelFieldModel;

class ModelFieldsParser
{

    protected \ReflectionClass $ref;

    protected Model $model;

    protected Collection $methods;

    protected Collection $fields;

    protected Collection $getter;

    protected Collection $setter;

    protected Collection $events;

    protected Collection $relations;

    protected Collection $scopes;

    public function __construct(Model $model, \ReflectionClass $ref)
    {
        $this->model = $model;
        $this->ref = $ref;
        $this->setter = new Collection();
        $this->getter = new Collection();
        $this->methods = new Collection();
        $this->events = new Collection();
        $this->relations = new Collection();
        $this->scopes = new Collection();
        $this->parseModel();
    }

    public function getGetterSchema():array
    {
        $fields = [];
        /**
         * @var ModelFieldModel $item
         */
        foreach ($this->getGetter() as $item){
            $fields[] = $item->getSchema();
        }
        return $fields;
    }

    public function getSetterSchema():array
    {
        $fields = [];
        /**
         * @var ModelFieldModel $item
         */
        foreach ($this->getSetter() as $item){
            $fields[] = $item->getSchema();
        }
        return $fields;
    }

    public function getRelationSchema():array
    {
        $fields = [];
        /**
         * @var ModelFieldModel $item
         */
        foreach ($this->getRelations() as $item){
            $fields[] = $item->getSchema();
        }
        return $fields;
    }

    public function getGetter(): Collection
    {
        return $this->getter;
    }

    public function getSetter(): Collection
    {
        return $this->setter;
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getMethods(): Collection
    {
        return $this->methods;
    }

    /**
     * @desc 解析模型方法中的字段
     */
    protected function parseModel()
    {
        $methods = $this->ref->getMethods();
        foreach ($methods as $method) {

            // TP框架模型基类方法不参与处理
            if ($method->getDeclaringClass()->getName() === Model::class) {
                continue;
            }
            $methodType = $this->parseMethodType($method);

            if ($methodType === 'getter') {
                // 获取器
                $this->parseGetAttr($method);
            }

            if ($methodType === 'setter') {
                // 修改器
                $this->parseSetAttr($method);
            }

            if ($methodType === 'scope') {
                // 搜索器
                $this->parseScopeMethod($method);
            }

            if ($methodType === 'event') {
                // 事件
                $this->parseEventMethod($method);
            }

            if ($method->isPublic() && $method->getNumberOfRequiredParameters() == 0) {
                // 关联对象
                $this->parseRelationMethod($method);
            }
        }
    }

    /**
     * @desc 模型方法类型
     * @param \ReflectionMethod $method
     * @return string
     */
    protected function parseMethodType(\ReflectionMethod $method): string
    {
        return match (true) {
            Str::startsWith($method->getName(), 'get') && Str::endsWith($method->getName(), 'Attr') => 'getter',
            Str::startsWith($method->getName(), 'set') && Str::endsWith($method->getName(), 'Attr') => 'setter',
            Str::startsWith($method->getName(), 'scope') => 'scope',
            Str::startsWith($method->getName(), 'on') && $method->isStatic() => 'event',
            default => 'unknown',
        };
    }

    /**
     * @desc 获取关联类型注释
     * @param Relation $relation
     * @return string
     */
    protected function parseRelationComment(Relation $relation): string
    {
        return match (true) {
            $relation instanceof HasOne => '(HasOne)正向一对一',
            $relation instanceof BelongsTo => '(BelongsTo)反向一对一',
            $relation instanceof MorphOne => '(MorphOne)多态一对一',
            $relation instanceof HasMany => '(HasMany)正向一对多',
            $relation instanceof HasManyThrough => '(HasManyThrough)远程一对多',
            $relation instanceof HasOneThrough => '(HasOneThrough)远程一对一',
            $relation instanceof BelongsToMany => '(BelongsToMany)多对多关联',
            $relation instanceof MorphTo => '(MorphTo)多态反向',
            $relation instanceof MorphMany => '(MorphMany)多态一对多',
            default => '未知关联类型',
        };
    }

    /**
     * @desc 解析关联方法
     * @param \ReflectionMethod $method
     * @return $this
     */
    protected function parseRelationMethod(\ReflectionMethod $method): ModelFieldsParser
    {
        try {
            $return = $method->invoke($this->model);
            if ($return instanceof Relation === false) {
                return $this;
            }
            $name = Str::snake($method->getName());
            $type = 'mixed';
            // 对一
            if ($return instanceof HasOne || $return instanceof BelongsTo || $return instanceof MorphOne || $return instanceof HasOneThrough) {
                $type = "\\" . get_class($return->getModel());
            }
            // 对多
            if ($return instanceof HasMany || $return instanceof HasManyThrough || $return instanceof BelongsToMany) {
                $type = "\\" . get_class($return->getModel()) . '[]';
            }
            $comment = $this->parseRelationComment($return);
            $comment .= (string)$this->parseMethodComment($method);

            $modelMethod = new ModelMethod($method->getName());
            $modelMethod->setType('relation')->addReturnType($type)->setComment($comment);
            $modelMethod->setIsSelf($method->getDeclaringClass()->getName() === $this->ref->getName());
            $this->setRelations($modelMethod);
        } catch (\Throwable $e) {
        }
        return $this;
    }

    /**
     * @desc 获取器方法解析
     * @param \ReflectionMethod $method
     * @return $this
     */
    protected function parseGetAttr(\ReflectionMethod $method): self
    {
        $name = Str::snake(substr($method->getShortName(), 3, -4));
        if (empty($name)) {
            return $this;
        }


        $types = TypeUtils::parseRefTypes($method->getReturnType());

        $docType = $this->parseMethodReturnTypes($method);
        $types = array_unique(array_merge($types,$docType));


        $modelField = new ModelFieldModel($name);
        $isSelf = $method->getDeclaringClass()->getName() === $this->ref->getName();
        $modelField->setType($types);
        $modelField->setPropertyType(JsyField::FIELD_SOURCE_GETTER);
        $modelField->setIsSelf($isSelf);
        $comment = $modelField->getComment();
        $comment .= (string)$this->parseMethodComment($method);
        $modelField->setComment($comment);

        $this->setGetter($modelField);
        // 模型方法  暂不加入
//        $args = $this->parseParameters($method);
//        $modelMethod = new ModelMethod($method->getName());
//        $modelMethod->setType('getter')->setType($type)->setParams($args)->setComment($comment);
//        $this->setMethod($modelMethod);

        return $this;
    }

    /**
     * @desc 解析修改器
     * @param \ReflectionMethod $method
     * @return $this
     */
    protected function parseSetAttr(\ReflectionMethod $method): self
    {
        $name = Str::snake(substr($method->getShortName(), 3, -4));
        if (empty($name)) {
            return $this;
        }

        if ($method->getReturnType() === null) {
            $types = $this->getReturnTypeFromDocBlock($method);
        } else {
            $types = TypeUtils::parseRefTypes($method->getReturnType());
        }

        $modelField = new ModelFieldModel($name,$types);
        $isSelf = $method->getDeclaringClass()->getName() === $this->ref->getName();
        $modelField->setPropertyType(JsyField::FIELD_SOURCE_GETTER);
        $modelField->setIsSelf($isSelf);
        if ($method->getDocComment() !== false) {
            $comment = $this->parseMethodComment($method);
        } else {
            $comment = $name . ' 的修改器';
        }
        $modelField->setComment(strval($comment));
        $this->setSetter($modelField);
        // 暂不开放
//        $args = $this->parseParameters($method);
//        $modelMethod = new ModelMethod($method->getName());
//        $modelMethod->setType('setter')->addReturnType($type)->setParams($args)->setComment($comment);
//        $this->setMethod($modelMethod);
        return $this;
    }

    /**
     * @desc 解析搜索器
     * @param \ReflectionMethod $method
     * @return ModelFieldsParser
     */
    protected function parseScopeMethod(\ReflectionMethod $method): ModelFieldsParser
    {
        $name = Str::camel(substr($method->getName(), 5));
        if (empty($name)) {
            return $this;
        }
        $args = $this->parseParameters($method);
        array_shift($args);
        $comment = '';
        $comment .= (string)$this->parseMethodComment($method);
        $modelMethod = new ModelMethod($method->getName());
        $modelMethod->setIsSelf($method->getDeclaringClass()->getName() === $this->ref->getName());
        $modelMethod->setType('scope')->addReturnType('\\think\\db\\Query')->setParams($args)->setComment($comment);
        $this->setScopes($modelMethod);
        return $this;
    }

    protected function parseParameters(\ReflectionMethod $method): array
    {
        $args = $method->getParameters();
        $params = [];
        foreach ($args as $index => $arg) {
            $methodParam = (new MethodParam($arg->name,$this->ref->getName(),false))->setRef($arg);
            $params[] = $methodParam->render();
        }
        return $params;
    }

    /**
     * @desc 模型事件
     * @param \ReflectionMethod $method
     * @return $this
     */
    protected function parseEventMethod(\ReflectionMethod $method): ModelFieldsParser
    {
        if (array_key_exists($method->getName(), ModelPropertyConfig::EVENT) === false) {
            return $this;
        }
        $comment = ModelPropertyConfig::EVENT[$method->getName()];
        $comment .= (string)$this->parseMethodComment($method);

        $args = $this->parseParameters($method);
        array_shift($args);
        $modelMethod = new ModelMethod($method->getName());
        $modelMethod->setIsSelf($method->getDeclaringClass()->getName() === $this->ref->getName());
        $modelMethod->setType('event')->addReturnType('void')->setParams($args)->setComment($comment);
        $this->setEvents($modelMethod);
        return $this;
    }

    protected function parseMethodReturnTypes(\ReflectionMethod $method):array
    {
        $types = [];

        if($method->getDocComment() === false){
            return $types;
        }

        $tags = DocBlockFactory::createInstance()->create($method)->getTags();
        foreach ($tags as $tag){
            if ($tag->getName() === 'return'){
                /**
                 * @var Return_ $tag
                 */
                $type = (string)$tag->getType();

                if (empty($type)){
                    continue;
                }

                if (str_ends_with($type,'[]')){
                    $types[] = 'array';
                    $type = str_replace('[]','',$type);
                }
                if (class_exists($type)){
                    $types[] = $type;
                }
            }

        }
        return $types;

    }

    /**
     * @desc 模型方法注释
     * @param \ReflectionMethod $method
     * @return string|null
     */
    protected function parseMethodComment(\ReflectionMethod $method): ?string
    {
        if($method->getDocComment() === false){
            return null;
        }
        $tags = DocBlockFactory::createInstance()->create($method)->getTags();
        foreach ($tags as $tag) {
            if ($tag->getName() === 'desc' || $tag->getName() === 'description') {
                return $tag->getDescription()->render();
            }
        }
        return null;
    }

    /**
     * @desc 从注释中获取返回类型
     * @param \ReflectionMethod $reflection
     * @return string|null
     */
    protected function getReturnTypeFromDocBlock(\ReflectionMethod $reflection): ?array
    {
        $types = [];
        try {
            $phpdoc = DocBlockFactory::createInstance()->create($reflection, new Context($reflection->getDeclaringClass()->getNamespaceName()));
            if ($phpdoc->hasTag('return')) {

                /** @var \phpDocumentor\Reflection\DocBlock\Tags\Return_ $returnTag */
                $returnTag = $phpdoc->getTagsByName('return')[0];
                if($returnTag instanceof InvalidTag === false){
                    $types =  TypeUtils::parseDocBlockTypes($returnTag->getType(),$reflection->getDeclaringClass()->getName());
                }
            }
        } catch (\InvalidArgumentException $e) {

        }
        return $types;
    }


    protected function setGetter(ModelFieldModel $field)
    {
        if($this->getter->where('name','=',$field->getName())->isEmpty()){
            $this->getter->push($field);
        }
    }
    protected function setSetter(ModelFieldModel $field)
    {
        if($this->setter->where('name','=',$field->getName())->isEmpty()){
            $this->setter->push($field);
        }
    }

    /**
     * @desc 增加模型方法
     * @param ModelMethod $method
     */
    protected function setMethod(ModelMethod $method)
    {
        if($this->methods->where('name','=',$method->getName())->isEmpty()){
            $this->methods->push($method);
        }
    }

    protected function setScopes(ModelMethod $method)
    {
        if($this->scopes->where('name','=',$method->getName())->isEmpty()){
            $this->scopes->push($method);
        }
    }

    protected function setRelations(ModelMethod $method)
    {
        if($this->relations->where('name','=',$method->getName())->isEmpty()){
            $this->relations->push($method);
        }
    }

    protected function setEvents(ModelMethod $method)
    {
        if($this->events->where('name','=',$method->getName())->isEmpty()){
            $this->events->push($method);
        }
    }

}
