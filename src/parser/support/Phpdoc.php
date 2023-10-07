<?php


namespace jsy\generator\parser\support;


use jsy\generator\builder\CodeBuilder;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use think\Exception;

class Phpdoc
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var Tag
     */
    protected $tag;

    public function __construct(Tag $tag = null)
    {
        if(is_null($tag) === false){
            $this->tag = $tag;
        }
    }

    public function render()
    {
        if(is_null($this->tag)){
            return ' * '.$this->getName().' '.(is_null($this->type) ? '' : $this->type).' '.$this->parseValue();
        }else{
            return ' * '.$this->tag->render();
        }
    }

    protected function parseValue(): ?string
    {
        if (is_null($this->value)){
            return  '';
        }
        if(is_array($this->value)){
            return implode('|' , $this->value);
        }
        if(is_bool($this->value)){
            return $this->value ? 'true' : 'false';
        }
        return $this->value;
    }

    /**
     * @param Tag $tag
     */
    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Tag
     */
    public function getTag(): Tag
    {
        return $this->tag;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     * @throws \think\Exception
     */
    public function getName(): string
    {
        if(is_null($this->tag) === false){
            return $this->tag->getName();
        }
        if(is_null($this->name)){
            throw new Exception('phpdoc name 未设置');
        }
        return $this->name;
    }

}
