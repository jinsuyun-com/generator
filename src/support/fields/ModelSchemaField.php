<?php


namespace jsy\generator\support\fields;


use jsy\base\schema\interfaces\ISchemaField;
use jsy\base\schema\Schema;
use jsy\base\schema\support\SchemaField;
use jsy\base\utils\UtilsTools;
use jsy\generator\utils\TypeUtils;
use think\contract\Arrayable;

abstract class ModelSchemaField implements Arrayable, \ArrayAccess,ISchemaField
{
    protected string $name;
    // 是否弃用
    protected bool $isDisuse = false;
    // 类型
    protected array $type = [];
    // 是否可空
    protected bool   $nullable     = false;
    // 默认值
    protected mixed $defaultValue = null;
    // 是否设置过默认值
    protected bool $isSetDefault = false;
    // 说明
    protected string $comment = '';

    public function __construct(string $name,array $options = [])
    {
        $this->name = $name;

        if(isset($options['type'])){
            if(is_string($options['type'])){
                $this->addType($options['type']);
            }
            if(is_array($options['type'])){
                $this->setType($options['type']);
            }
        }
        if(isset($options['is_disuse'])){
            $this->setIsDisuse(boolval($options['is_disuse']));
        }
        if(isset($options['nullable'])){
            $this->setNullable(boolval($options['nullable']));
        }
        if(isset($options['defaultValue'])){
            $this->setDefaultValue($options['defaultValue']);
        }
        if(isset($options['default_value'])){
            $this->setDefaultValue($options['default_value']);
        }
        if(isset($options['comment'])){
            $this->setComment(strval($options['comment']));
        }
    }

    public function toArray(): array
    {
        $data = [
            'name'=>$this->getName(),
            'type'=>$this->getType(),
            'nullable'=>$this->isNullable(),
            'comment'=>$this->getComment()
        ];
        if($this->isSetDefault){
            $data['default_value'] = $this->defaultValue;
        }
        return $data;
    }

    public function getSchema(): SchemaField
    {
        $field = new SchemaField();
        $field->name($this->getName());
        $types = $this->getType();
        if (in_array('array',$types)){
            $field->list();
            $key = array_search('array',$types);
            unset($types[$key]);
        }



        $field->types(TypeUtils::parseToSchemaType($types));
        $field->remark($this->getComment());

        return $field;
    }

    public function getTsType():array
    {
        if(count($this->type) === 0){
            return ['any'];
        }
        $type = [];
        if($this->nullable){
            $type[] = 'null';
        }
        foreach ($this->type as $item){
            $type[] = TypeUtils::toTypeScriptType($item);
        }
        return $type;
    }

    public function setIsDisuse(bool $isDisuse):self
    {
        $this->isDisuse = $isDisuse;
        return $this;
    }

    public function isDisuse():bool
    {
        return $this->isDisuse;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): array
    {
        if($this->isNullable() && in_array('null',$this->type) === false){
            $this->type[] = 'null';
        }
        return $this->type ?? [];
    }

    public function getTypeString(string $separator = '|'):string
    {
        return implode($separator,$this->getType());
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function setDefaultValue(mixed $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->isSetDefault = true;
        return $this;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function setType(array $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function addType(string $type):self
    {
        if(class_exists($type)){
            $type = '\\'.UtilsTools::replaceNamespace($type);
        }
        if($this->type === null || in_array($type,$this->type) === false){
            $this->type[] = $type;
        }
        return $this;
    }

    public function offsetExists($offset)
    {
        return $this->{$offset} === null;
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }


}
