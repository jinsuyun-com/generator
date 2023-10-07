<?php


namespace jsy\generator\parser\support;


use jsy\generator\builder\ClassBuilder;
use jsy\generator\builder\CodeBuilder;
use jsy\generator\utils\TypeUtils;

class MethodParam
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */

    protected string $classname;


    /**
     * @var array
     */
    protected array $type = [];
    /**
     * @var mixed
     */
    protected mixed $defaultValue;
    /**
     * @var bool
     */
    protected bool $allowNull = false;

    protected bool $hasDefault = false;
    /**
     * @var string
     */
    protected string $valueType = 'string';

    // 预留 用于更新参数使用
    protected bool $isUpdate = false;

    protected array $codeArray = [];

    /**
     * @var \ReflectionParameter
     */
    protected $ref;

    protected bool $isClassBuilder = true;

    public function __construct(string $name,string $classname,bool $isClassBuilder = true)
    {
        $this->name = $name;
        $this->isClassBuilder = $isClassBuilder;
        $this->classname = $classname;
    }

    public function render():string
    {
        $this->renderToArray();
        return implode(' ',$this->codeArray);
    }

    public function renderToArray(): array
    {
        if($this->ref instanceof \ReflectionParameter === false){
            $this->renderBySet();
        }else{
            $this->renderByRef();
        }
        return $this->codeArray;
    }

    /**
     * @param string $valueType
     * @return MethodParam
     */
    public function setValueType(string $valueType): self
    {
        $this->valueType = $valueType;
        return $this;
    }

    /**
     * @param string $classname
     * @return \jsy\generator\parser\support\MethodParam
     */
    public function setClassname(string $classname): self
    {
        $this->classname = $classname;
        return $this;
    }

    /**
     * @return string
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }


    protected function renderByRef()
    {
        // type hint
        if($this->ref->hasType()){
            $typeHint = '';
            if($this->ref->allowsNull()){
                $typeHint .= '?';
            }
            $types = TypeUtils::parseRefTypes($this->ref->getType());
            $typeNames = [];
            foreach ($types as $type){
                if(class_exists($type)){
                    if($this->isClassBuilder){
                        ClassBuilder::staticAddImport($this->classname,$type);
                    }
                    $typeNames[] = class_basename($type);

                }else{
                    $typeNames[] = $type;
                }
            }
            $typeHint .= implode('|',$typeNames);
            $this->codeArray[] = $typeHint;
        }

        // 参数名
        $this->codeArray[] = '$'.$this->ref->getName();
        if($this->ref->isDefaultValueAvailable()){
            $this->codeArray[] = '=';
            if($this->ref->isDefaultValueConstant()){
                $constant = $this->ref->getDefaultValueConstantName();
                $constant = class_basename($constant);
                $this->codeArray[] = $constant;
            }else{
                $value = $this->ref->getDefaultValue();
                $this->codeArray[] = CodeBuilder::varExport($value);
            }
        }
        return $this;
    }

    protected function renderBySet()
    {
        // type hint
        $types = [];
        if($this->allowNull){
            $types[] = 'null';
        }
        foreach ($this->type as $type){
            if($type['isClass']){
                $types[] = $type['type'];
            }else{
                $type = strtolower($type['type']);
                if(in_array($type,TypeHint::PARAM_TYPE)){
                    $types[] = $type;
                }
                if(isset(TypeHint::TRANS_COMMON[$type])){
                    $types[] .= TypeHint::TRANS_COMMON[$type];
                }
            }
        }
        if(empty($types) === false){
            $this->codeArray[] = implode('|',$types);
        }

        $this->codeArray[] = '$'.$this->getName();

        if($this->hasDefault){
            $this->codeArray[] = '=';
            $this->codeArray[] = CodeBuilder::varExport($this->defaultValue,$this->valueType);
        }
        return $this;
    }

    /**
     * @param \ReflectionParameter $ref
     * @return MethodParam
     */
    public function setRef(\ReflectionParameter $ref): self
    {
        $this->ref = $ref;
        return $this;
    }

    public function addType(string $type,bool $isClass = false):self
    {
        if($isClass){
            if(class_basename($this->classname) !== class_basename($type)){
                ClassBuilder::staticAddImport($this->classname,$type);
                $this->type[$type]['type'] = class_basename($type);
            }else{
                $this->type[$type]['type'] = '\\'.$type;
            }
        }else{
            $this->type[$type]['type'] = $type;
        }

        $this->type[$type]['isClass'] = $isClass;
        return $this;
    }


    /**
     * @param mixed $defaultValue
     * @return MethodParam
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        $this->hasDefault = true;
        return $this;
    }



    /**
     * @param bool $allowNull
     * @return MethodParam
     */
    public function setAllowNull(bool $allowNull): self
    {
        $this->allowNull = $allowNull;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
