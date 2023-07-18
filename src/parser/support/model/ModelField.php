<?php


namespace maodou\generator\parser\support\model;


use maodou\generator\parser\support\model\driver\mysql\FieldIndex;
use think\contract\Arrayable;
use think\helper\Str;

class ModelField implements Arrayable,\ArrayAccess
{
    const FIELD_SOURCE_TABLE = 'table';
    const FIELD_SOURCE_GETTER = 'getter';
    const FIELD_SOURCE_RELATION = 'relation';
    const FIELD_SOURCE_SETTER = 'setter';

    const OPTION_CREATE_TIME = 'create_time';
    const OPTION_UPDATE_TIME = 'update_time';
    const OPTION_DELETE_TIME = 'delete_time';
    const OPTION_TABLE_FIELD = 'table_field';
    const OPTION_UNSIGNED = 'unsigned';
    const OPTION_ZEROFILL = 'zerofill';
    const OPTION_PRIMARY = 'primary';

    protected string $name;

    protected array $type = [];

    protected bool $nullable = false;

    protected bool $writable = true;


    protected int $limit = -1;

    protected int $scale = 0;

    protected null|string $comment = null;
    /**
     * @var FieldIndex
     */
    protected null|FieldIndex $index = null;

    /**
     * 字段来源 table append relation
     * @var string
     */
    protected string $source = 'table';
    /**
     * @var string
     */
    protected string $fieldType;

    protected array $options = [];

    protected bool $isSelf = false;

    /**
     * 是否废弃
     * @var bool
     */
    protected bool $disuse = false;

    public function __construct(string $name,string $source)
    {
        $this->name = $name;
        $this->source = $source;
    }

    public function isSelf(bool $isSelf)
    {
        $this->isSelf = $isSelf;
    }

    public function toArray(): array
    {
        return [
            'name'=>$this->name,
            'type'=>$this->getType(),
            'nullable'=>$this->nullable,
            'writable'=>$this->writable,
            'limit'=>$this->limit,
            'scale'=>$this->scale,
            'comment'=>$this->comment,
            'field_type'=>$this->fieldType,
            'is_disuse'=>$this->disuse,
            'source'=>$this->source,
            'options'=>$this->options,
            'index'=>$this->getIndex(),
            'is_self'=>$this->isSelf
        ];
    }

    /**
     * @param FieldIndex $index
     * @return ModelField
     */
    public function setIndex(FieldIndex $index): self
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function getFieldSource(): string
    {
        return $this->source;
    }


    /**
     * @return FieldIndex
     */
    public function getIndex(): ?FieldIndex
    {
        return $this->index;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param bool $disuse
     * @return \maodou\generator\parser\support\model\ModelField
     */
    public function setDisuse(bool $disuse): self
    {
        $this->disuse = $disuse;
        return $this;
    }

    /**
     * 设置树表中的字段类型
     * @param string $fieldType
     * @return \maodou\generator\parser\support\model\ModelField
     */
    public function setFieldType(string $fieldType): self
    {
        $this->fieldType = $fieldType;
        return $this;
    }


    /**
     * 设置字段来源
     * @param string $fieldSource
     * @return ModelField
     */
    public function setFieldSource(string $fieldSource): self
    {
        $this->fieldSource = $fieldSource;
        return $this;
    }

    /**
     * @desc 设置附加属性
     * @param string $option
     * @return $this
     */
    public function addOption(string $option):self
    {
        if(in_array($option,$this->options) === false){
            $this->options[] = $option;
        }
        return $this;
    }

    /**
     * @param bool $nullable
     * @return \maodou\generator\parser\support\model\ModelField
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * @param bool $writable
     * @return \maodou\generator\parser\support\model\ModelField
     */
    public function setWritable(bool $writable): self
    {
        $this->writable = $writable;
        return $this;
    }

    /**
     * @param string $comment
     * @return ModelField
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }


    /**
     * @param mixed $limit
     * @return ModelField
     */
    public function setLimit($limit): self
    {
        $this->limit = $limit;
        return $this;
    }


    /**
     * @param int $scale
     * @return ModelField
     */
    public function setScale(int $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function addType(string $type): ModelField
    {
        if(in_array($type,$this->type)===false){
            $this->type[] = $type;
        }
        return $this;
    }

    /**
     * @param string|null $type
     * @return ModelField
     */
    public function setType(?string $type): self
    {
        $this->type = empty($type) ? ['mixed'] : $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return is_null($this->comment) ? '' :$this->comment;
    }


    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if(empty($this->type)){
            return 'mixed';
        }
        return implode('|',$this->type);
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
            $type[] = $this->parseTypeToTsType($item);
        }
        return $type;
    }

    protected function parseTypeToTsType(string $item)
    {
        switch (true){
            case in_array($item,['int','integer','float','double']):
                return 'number';
            case in_array($item,['string','datetime']):
                return 'string';
            case $item === 'mixed':
                return 'any';
            case in_array($item,['bool','boolean']):
                return 'boolean';
            case in_array($item,['array','iterable']):
                return 'array';
            case $item === 'null':
                return 'null';
            default:
                return 'object';
        }
    }

    public function getJsonSchemeType(): array
    {
        if(count($this->type) === 0){
            return ['any'];
        }
        $type = $this->type;
        if($this->nullable){
            $type[] = 'null';
        }
        return count($type) === 1 ?$type[0] : $type;
    }

    public function getJsonSchemeTitle():null|string
    {
        return $this->comment;
    }


    public function offsetExists($offset): bool
    {
        return !is_null($this->{Str::camel($offset)});
    }

    public function offsetGet($offset)
    {
        return $this->{Str::camel($offset)};
    }

    public function offsetSet($offset, $value)
    {
        $this->{Str::camel($offset)} = $value;
    }

    public function offsetUnset($offset)
    {
        $this->{Str::camel($offset)} = null;
    }

}
