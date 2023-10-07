<?php


namespace jsy\generator\parser;


use JetBrains\PhpStorm\ArrayShape;
use jsy\generator\parser\support\model\driver\mysql\TableFieldsParser;
use jsy\generator\parser\support\model\ModelFieldsParser;
use jsy\generator\parser\support\model\ModelProperty;
use jsy\generator\parser\support\model\ModelPropertyParser;
use jsy\generator\support\fields\model\ModelFieldModel;
use jsy\generator\support\fields\table\TableFieldModel;
use think\Collection;
use think\Exception;
use think\facade\Config;
use think\Model;
use think\model\concern\SoftDelete;


class ModelParser
{
    protected \ReflectionClass $ref;

    protected string $modelClass;

    protected Model $modelInstance;

    protected array $defaultProperties = [];

    protected array $readonlyFields = [];

    protected array $disuseFields;

    protected string $dateFormat;

    protected array $modelFieldTypes = [];

    protected array $timestampFields = [];

    protected TableFieldsParser $tableFieldsParser;

    protected ModelFieldsParser $modelFieldsParser;

    protected ModelPropertyParser $modelPropertyParser;

    protected Collection $tableFields;

    public function __construct(string $modelClass)
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
        $this->defaultProperties = $this->ref->getDefaultProperties();
        $this->modelFieldTypes = $this->defaultProperties['type'] ?? [];
        $this->parseSpecialFields();
        $isJsonArray = $this->defaultProperties['jsonAssoc'] ?? false;
        // 表字段
        $this->tableFieldsParser = new TableFieldsParser($this->modelInstance,$isJsonArray);
        // 模型字段
        $this->modelFieldsParser = new ModelFieldsParser($this->modelInstance,$this->ref);
        // 模型属性
        $this->modelPropertyParser = new ModelPropertyParser($this->ref);
        $this->parseTimestampFields();
        $this->parseTableFields();
    }

    public function getJsonSchemas():array
    {
        $tableFields = $this->tableFields;
        $getterFields = $this->modelFieldsParser->getGetter();
        $fields = [];
        $fields['table_fields'] = [];
        $fields['getter_fields'] = [];
        foreach ($tableFields as $tableField){
            if($getterFields->where('name','=',$tableField->getName())->isEmpty()){
                $temp = [
                    'field'=>$tableField->getName(),
                    'type'=>implode('|',$tableField->getTsType()),
                    'comment'=>$tableField->getComment()
                ];
            }else{
                $field = $getterFields->where('name','=',$tableField->getName())->first();
                $temp = [
                    'field'=>$field->getName(),
                    'type'=>implode('|',$field->getTsType()),
                    'comment'=>empty($field->getComment()) ? $tableField->getComment() : $field->getComment()
                ];
            }
            $fields['table_fields'][] = $temp;
        }
        foreach ($getterFields as $getterField){
            if($tableFields->where('name','=',$getterField->getName())->isEmpty() === false){
                continue;
            }
            $temp = [
                'field'=>$getterField->getName(),
                'type'=>implode('|',$getterField->getTsType()),
                'comment'=>$getterField->getComment()
            ];
            $fields['getter_fields'][] = $temp;
        }
        return $fields;
    }

    /**
     * @return \jsy\base\schema\support\SchemaField[]
     */
    public function getTableFieldsSchema(): array
    {
        return $this->tableFieldsParser->getSchema();
    }

    /**
     * @return \jsy\base\schema\support\SchemaField[]
     */
    public function getGetterFieldsSchema():array
    {
        return $this->modelFieldsParser->getGetterSchema();
    }


    protected function parseTableFields()
    {
        $this->tableFields = $this->tableFieldsParser->getFields();
        foreach ($this->tableFields as $field){
            /**
             * @var \jsy\generator\support\fields\table\TableFieldModel $field
             */
            // 弃用字段
            if(in_array($field->getName(),$this->disuseFields)){
                $field->setIsDisuse(true);
            }
            // 时间字段
            if (array_key_exists($field->getName(),$this->timestampFields)){
                $field->addAddition($this->timestampFields[$field->getName()]);
            }
            // 获取器
            $isGetter = $this->modelFieldsParser->getGetter()->where('name','=',$field->getName())->isEmpty() === false;
            if($isGetter){
                $field->addAddition('getter');
            }
            // 修改器
            $isSetter = $this->modelFieldsParser->getSetter()->where('name','=',$field->getName())->isEmpty() === false;
            if($isSetter){
                $field->addAddition('setter');
            }
            $this->parseMysqlToPhpType($field);
        }
    }

    protected function parseMysqlToPhpType(TableFieldModel $field)
    {
        if(in_array('create_time',$field->getAdditions())){
            $field->setType([$this->dateFormat]);
            return $this;
        }
        if(in_array('update_time',$field->getAdditions())){
            $field->setType([$this->dateFormat]);
            return $this;
        }
        if (in_array($field->getName(),$this->modelFieldTypes)){
            $field->setType([$this->parseTypedField($field)]);
            return $this;
        }
        return $this;
    }


    protected function parseTypedField(ModelFieldModel|TableFieldModel $field): string
    {
        $type = $this->modelFieldTypes[$field->getName()];
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
        return $type;
    }

    /**
     * @return \think\Collection|TableFieldModel[]
     */
    public function getTableFields():Collection
    {
        return $this->tableFields;
    }

    public function getSchema(): array
    {
        $tableFields  = $this->tableFieldsParser->getFields();
        $schema = [];

        foreach ($tableFields as $tableField){
            /**
             * @var ModelFieldModel $tableField
             */
            $schema[$tableField->getName()] = $tableField->getType();
        }

        return $schema;
    }

    /**
     * @desc 模型设置
     * @return array
     */
    #[ArrayShape([
        'classname' => "string", 'short_name' => "string", 'filename' => "mixed", 'dir' => "array|string|string[]",
        'parent'    => "string", 'parent_short_name' => "string", 'count' => "int"
    ])]
    public function getModelConfig(): array
    {
        return [
            'classname'=>$this->ref->getName(),
            'short_name'=>$this->ref->getShortName(),
            'filename'=>str_replace(app()->getRootPath(),'',$this->ref->getFileName()),
            'dir'=>pathinfo($this->ref->getFilename(),PATHINFO_DIRNAME),
            'parent'=>$this->ref->getParentClass()->getName(),
            'parent_short_name'=>$this->ref->getParentClass()->getShortName(),
            'count'=>$this->modelInstance->count()
        ];
    }

    public function getGetterFields(): array
    {
        $fields = $this->modelFieldsParser->getGetter();

        foreach ($fields as $field){
            if($this->tableFields->where('name','=',$field->getName())->isEmpty() === false){
                $field->addOption('table');
            }
        }
        return $fields->toArray();
    }

    public function getSetterFields(): array
    {
        $fields = $this->modelFieldsParser->getSetter();

        foreach ($fields as $field){
            if($this->tableFields->where('name','=',$field->getName())->isEmpty() === false){
                $field->addOption('table');
            }
        }
        return $fields->toArray();
    }

    public function getEvents(): Collection
    {
        return $this->modelFieldsParser->getEvents();
    }

    public function getScopes(): Collection
    {
        return $this->modelFieldsParser->getScopes();
    }

    public function getRelations(): Collection
    {
        return $this->modelFieldsParser->getRelations();
    }

    protected function parseSpecialFields()
    {
        $this->readonlyFields = empty($this->defaultProperties['readonly']) ? [] : $this->defaultProperties['readonly'];
        $this->disuseFields = empty($this->defaultProperties['disuse']) ? [] : $this->defaultProperties['disuse'];
    }

    public function getRef(): \ReflectionClass
    {
        return $this->ref;
    }


    public function getProperty(string $name = ''):null | ModelProperty
    {
        return $this->getProperties()->where('name','=',$name)->first();
    }

    public function getProperties(): Collection
    {
        return $this->modelPropertyParser->getModelProperties();
    }

    /**
     * @desc 解析时间字段设置
     * @return $this
     */
    protected function parseTimestampFields(): self
    {
        $autoTimestamp = isset($this->defaultProperties['autoWriteTimestamp']) === false ? Config::get('database.auto_timestamp') : $this->defaultProperties['autoWriteTimestamp'];
        if ($autoTimestamp === false) {
            return $this;
        }

        $dateFormat = empty($this->defaultProperties['dateFormat']) ? Config::get('database.datetime_format') : $this->defaultProperties['dateFormat'];
        if($dateFormat === false){
            switch ($autoTimestamp){
                case 'timestamp' | 'datetime' | 'date':
                    $dateFormat = 'string';
                    break;
                case 'int':
                    $dateFormat = 'int';
                    break;
                default:
            }
        }

        switch (true) {
            case $dateFormat === 'int':
                $this->dateFormat = 'int';
                break;
            case class_exists($dateFormat):
                $this->dateFormat = $dateFormat;
                break;
            default:
                $this->dateFormat = 'string';
        }

        $createTimeField = empty($this->defaultProperties['createTime']) ? 'create_time' : $this->defaultProperties['createTime'];
        if ($createTimeField !== false) {
            $this->timestampFields[$createTimeField] = 'create_time';
        }
        $updateTimeField = empty($this->defaultProperties['updateTime']) ? 'update_time' : $this->defaultProperties['updateTime'];
        if ($updateTimeField !== false) {
            $this->timestampFields[$updateTimeField] = 'update_time';
        }
        $this->parseSoftDelete();
        return $this;
    }

    protected function parseSoftDelete(): self
    {
        if (empty($this->defaultProperties['softDelete'])) {
            return $this;
        }
        if (in_array(SoftDelete::class, $this->ref->getTraitNames()) === false) {
            return $this;
        }
        $this->timestampFields['delete_time'] = 'delete_time';
        return $this;
    }





    /**
     * @desc 处理json字段类型
     * @param $name
     * @return string
     */
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






}
