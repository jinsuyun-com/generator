<?php


namespace maodou\generator\parser\support\method;


use maodou\generator\builder\ClassBuilder;
use maodou\generator\builder\CodeBuilder;

class MethodArgument
{
    protected string $name;
    protected string $classname;
    protected mixed  $value ;
    protected bool $isNull = false;
    protected string|null $type = null;
    protected bool $isOriginRender = false;

    public function __construct(string $name,mixed $value,string $classname)
    {
        $this->name = $name;
        $this->classname = $classname;
        $this->setValue($value);
    }

    public function getName():string
    {
        return $this->name;
    }

    public function render():string
    {

        if($this->isNull){
            $value = 'null';
        }else if($this->isOriginRender){
            $value = $this->value;
        }else if(is_string($this->value) && class_exists($this->value)){
            ClassBuilder::staticAddImport($this->classname,$this->value);
            $value = class_basename($this->value).'::class';
        }else{
            $value = CodeBuilder::varExport($this->value,$this->type);
        }


        return sprintf('%s:%s',$this->getName(),$value);
    }

    /**
     * @param mixed|null $value
     * @return MethodArgument
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param string|null $type
     * @return MethodArgument
     */
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param bool $isOriginRender
     * @return MethodArgument
     */
    public function setIsOriginRender(bool $isOriginRender): self
    {
        $this->isOriginRender = $isOriginRender;
        return $this;
    }

    /**
     * @param bool $isNull
     * @return MethodArgument
     */
    public function setIsNull(bool $isNull): self
    {
        $this->isNull = $isNull;
        return $this;
    }
}
