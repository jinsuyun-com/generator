<?php


namespace jsy\generator\parser\support\model;


use jsy\generator\builder\CodeBuilder;
use think\contract\Arrayable;
use think\contract\Jsonable;
use think\helper\Str;
use think\Model;

class ModelProperty implements Arrayable, \JsonSerializable, Jsonable,\ArrayAccess
{
    const SOURCE_SELF = 'self';
    const SOURCE_PARENT = 'parent';
    const SOURCE_THINKPHP = 'thinkphp';
    const SOURCE_CONFIG = 'config';
    const SOURCE_CONTRACT = 'contract';

    protected string $className;

    protected string $shortName;

    protected string $name;

    protected $type;

    protected bool $isValued = false;

    protected mixed $value = null;

    protected string $source = 'undefined';

    protected string $title;

    protected array $allowTypes = [];

    protected array $sourceTextArray = [
        'contract'=>'框架约定',
        'self' => '当前模型',
        'parent' => '父类继承',
        'thinkphp' => 'TP框架',
        'config' => '系统配置',
        'undefined'=>'未知来源'
    ];

    public function __construct(string $className, string $name)
    {
        $this->className = $className;
        $this->shortName = class_basename($className);
        $this->name = $name;
        $this->parseTitle();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $sourceOrClass
     * @return ModelProperty
     */
    public function setSource(string $sourceOrClass): self
    {
        switch (true) {
            case $sourceOrClass === $this->className:
                $realSource = self::SOURCE_SELF;
                break;
            case $sourceOrClass === Model::class:
                $realSource = self::SOURCE_THINKPHP;
                break;
            case strpos($sourceOrClass, '\\'):
                $realSource = self::SOURCE_PARENT;
                break;
            default:
                $realSource = $sourceOrClass;
        }
        $this->source = $realSource;
        return $this;
    }

    /**
     * @param string $title
     * @return ModelProperty
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return ModelProperty
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        $this->type = gettype($value);
        $this->isValued = true;
        return $this;
    }

    public function isValued(): bool
    {
        return $this->isValued;
    }
    public function toArray(): array
    {
        return [
            'class' => $this->className,
            'short_name' => $this->shortName,
            'name' => $this->name,
            'type'=>$this->type,
            'value' => $this->value,
            'source' => $this->source,
            'title' => $this->title,
            'allow_types'=>$this->allowTypes,
            'source_text' => $this->parseSourceText()
        ];
    }

    protected function parseTitle(): self
    {
        $this->title =  ModelPropertyConfig::CONFIG[$this->name]['title'] ?? '未知属性';
        $this->allowTypes = ModelPropertyConfig::CONFIG[$this->name]['type'] ?? [];
        return $this;
    }

    protected function parseSourceText(): string
    {

        return $this->sourceTextArray[$this->source] ?? '未知来源';
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
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
