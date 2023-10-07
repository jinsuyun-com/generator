<?php


namespace jsy\generator\parser\support\model;

use think\Collection;
use think\Exception;
use think\facade\Config;
use think\helper\Str;
use think\model\concern\SoftDelete;

class ModelPropertyParser
{
    /**
     * @var \ReflectionClass
     */
    protected \ReflectionClass $ref;

    protected array $propertyRefs = [];

    protected array $defaultProperties = [];

    protected string $connection = '';

    protected array $database = [];
    /**
     * @var Collection
     */
    protected Collection $modelProperties;

    public function __construct(\ReflectionClass $modelRef)
    {
        $this->ref = $modelRef;
        $this->database = Config::get('database');
        foreach ($this->ref->getProperties() as $property){
            $this->propertyRefs[$property->getName()] = $property;
        }
        $this->defaultProperties = $this->ref->getDefaultProperties();
        $this->modelProperties = new Collection();
        $this->parseConnection();
        $this->parseTable();
        $this->parsePk();
        $this->parseJsonAssoc();
        $this->parseAutoWriteTimestamp();
        $this->parseDataFormat();
        $this->parseJson();
        $this->parseSoftDelete();
        $this->parseDefaultSoftDelete();
        $this->parseUpdateTime();
        $this->parseCreateTime();
        $this->parseField();
        $this->parseType();
        $this->parseDisuse();
        $this->parseReadonly();
        $this->parseJsyTitle();
        $this->parseJsyCreateTime();
        $this->parseRemark();
    }

    public function toArray()
    {
        return $this->modelProperties->toArray();
    }

    public function getModelProperties(): Collection
    {
        return $this->modelProperties;
    }

    public function getConfig(string $name)
    {
        return $this->modelProperties->where('name','=',$name)->first();
    }

    protected function parseConnection()
    {
        $modelProperty = $this->parseModelProperty('connection');
        if($modelProperty->isValued() === false){
            if(isset($this->database['default']) === false){
                throw new Exception('未找到数据库 connection 信息，请确认database配置或模型配置');
            }
            $this->connection = $this->database['default'];
            $modelProperty->setSource(ModelProperty::SOURCE_CONFIG)->setValue($this->connection);
        }
        $this->modelProperties->push($modelProperty);
    }

    /**
     * @desc 获取表明
     * @return $this
     */
    protected function parseTable(): self
    {
        $modelProperty = $this->parseModelProperty('table');
        if($modelProperty->isValued()){
            // 单独设置了table 及时返回
            $this->modelProperties->push($modelProperty);
            return $this;
        }
        $prefix = empty($this->database['connections'][$this->connection]['prefix']) ? '' :$this->database['connections'][$this->connection]['prefix'].'_';
        $modelProperty = $this->parseModelProperty('name');
        if($modelProperty->isValued()){
            $table = $prefix.$modelProperty->getName();
        }else{
            $table = $prefix.Str::snake($this->ref->getShortName());
        }
        $tableProperty = new ModelProperty($this->ref->getName(),'table');
        $tableProperty->setSource(ModelProperty::SOURCE_CONTRACT)->setValue($table);
        $this->modelProperties->push($tableProperty);
        return $this;
    }

    protected function parsePk()
    {
        $modelProperty = $this->parseModelProperty('pk');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseJsonAssoc()
    {
        $modelProperty = $this->parseModelProperty('jsonAssoc');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseAutoWriteTimestamp()
    {
        $modelProperty = $this->parseModelProperty('autoWriteTimestamp');
        $this->modelProperties->push($modelProperty);
    }

    /**
     * @desc 时间戳格式
     */
    protected function parseDataFormat()
    {
        $modelProperty = $this->parseModelProperty('dataFormat');
        if($modelProperty->isValued() === false){
            if(isset($this->database['datetime_format']) === false){
                $modelProperty->setValue('int')->setSource(ModelProperty::SOURCE_CONTRACT);
            }else{
                $modelProperty->setSource(ModelProperty::SOURCE_CONFIG)->setValue($this->database['datetime_format']);
            }
        }
        $this->modelProperties->push($modelProperty);
    }

    protected function parseJson()
    {
        $modelProperty = $this->parseModelProperty('json');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseSoftDelete()
    {
        $modelProperty = new ModelProperty($this->ref->getName(),'softDelete');
        if(in_array(SoftDelete::class,$this->ref->getTraitNames()) === false){
            $modelProperty->setSource(ModelProperty::SOURCE_CONTRACT)->setValue(false);
        }else{
            $modelProperty->setSource(ModelProperty::SOURCE_CONTRACT)->setValue(true);
        }

        $this->modelProperties->push($modelProperty);
        $this->parseDeleteTime();
    }

    protected function parseDeleteTime()
    {
        $deleteTime = $this->parseModelProperty('deleteTime');
        if(isset($this->defaultProperties['deleteTime']) === false || is_null($this->defaultProperties['deleteTime'])){
            $deleteTime->setSource(ModelProperty::SOURCE_CONTRACT)->setValue('delete_time');
        }else{
            $deleteTime->setSource(ModelProperty::SOURCE_SELF)->setValue($this->defaultProperties['deleteTime']);
        }
        $this->modelProperties->push($deleteTime);
    }

    protected function parseDefaultSoftDelete()
    {
        $modelProperty = $this->parseModelProperty('defaultSoftDelete');
        if(isset($this->defaultProperties['deleteTime']) === false || is_null($this->defaultProperties['deleteTime'])){
            $modelProperty->setSource(ModelProperty::SOURCE_CONTRACT)->setValue('NULL');
        }else{
            $modelProperty->setSource(ModelProperty::SOURCE_SELF)->setValue($this->defaultProperties['deleteTime']);
        }
        $this->modelProperties->push($modelProperty);
    }

    protected function parseUpdateTime()
    {
        $modelProperty = $this->parseModelProperty('updateTime');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseCreateTime()
    {
        $modelProperty = $this->parseModelProperty('createTime');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseField()
    {
        $modelProperty = $this->parseModelProperty('field');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseType():void
    {
        $modelProperty = $this->parseModelProperty('type');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseDisuse():void
    {
        $modelProperty = $this->parseModelProperty('disuse');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseReadonly():void
    {
        $modelProperty = $this->parseModelProperty('readonly');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseJsyTitle():void
    {
        $modelProperty = $this->parseModelProperty('jsyTitle');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseJsyCreateTime():void
    {
        $modelProperty = $this->parseModelProperty('jsyCreateTime');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseRemark():void
    {
        $modelProperty = $this->parseModelProperty('remark');
        $this->modelProperties->push($modelProperty);
    }

    protected function parseModelProperty($name): ModelProperty
    {
        $modelProperty = (new ModelProperty($this->ref->getName(),$name));

        if(isset($this->defaultProperties[$name]) === false || is_null($this->defaultProperties[$name])){
            return $modelProperty;
        }
        /**
         * @var  \ReflectionProperty $property
         */
        $property = $this->propertyRefs[$name];
        $modelProperty->setSource($property->getDeclaringClass()->getName())->setValue($this->defaultProperties[$name]);
        return $modelProperty;
    }



}
