<?php


namespace jsy\generator\parser\support;


use jsy\generator\builder\ClassBuilder;
use jsy\generator\builder\CodeBuilder;
use jsy\generator\parser\support\method\MethodArgument;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use think\Exception;

class MethodAttribute
{
    protected string $attribute;
    protected string $className;
    protected ReflectionClass $ref;
    protected array $params;
    protected bool $isRepeated = false;
    public function __construct(string $attribute,$className)
    {
        if(class_exists($attribute) === false){
            throw new Exception($attribute .'不存在');
        }
        $this->attribute = $attribute;
        $this->ref = new ReflectionClass($attribute);
        $this->validate();
        ClassBuilder::staticAddImport($className,$attribute);
    }

    protected function validate()
    {
        $attributes = $this->ref->getAttributes('Attribute');
        if(empty($attributes)){
            throw new Exception($this->attribute.'不是注解类（Attribute）');
        }
        $attribute = $attributes[0];
        $this->isRepeated = $attribute->isRepeated();
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getShortName(): string
    {
        return $this->ref->getShortName();
    }

    public function isRepeated(): bool
    {
        return $this->isRepeated;
    }

    public function addParam(MethodArgument $value): self
    {
        if(empty($this->ref->getConstructor()?->getParameters())){
            throw new Exception($this->getAttribute().'不需要参数');
        }
        $this->params[$value->getName()] = $value;
        return $this;
    }

    public function render(): string
    {
        $code = "\t".'#[';
        $code .=class_basename($this->getAttribute());
        if(empty($this->ref->getConstructor()?->getParameters())===false){
            $code .='('.$this->buildParams().')';
        }
        $code .=']';
        return $code;
    }

    protected function buildParams(): string
    {
        $params = $this->ref->getConstructor()->getParameters();
        $paramArray = [];
        foreach ($params as $param){
            if(isset($this->params[$param->getName()])){
                $methodArgument = $this->params[$param->getName()];
                if($methodArgument instanceof MethodArgument){
                    $paramArray[] = $methodArgument->render();
                }else{
                    $paramArray[] = (string)$methodArgument;
                }

            }else{
                if($param->allowsNull() === false){
                    throw new Exception($this->getAttribute().'中的'.$param->getName().'参数必须');
                }
            }
        }
        return implode(', ',$paramArray);
    }
}
