<?php


namespace jsy\generator\parser\support;


use jsy\generator\builder\ClassBuilder;
use jsy\generator\builder\CodeBuilder;
use think\Exception;

class ClassProperty
{
    protected string $name = '';

    protected string $classname;

    protected int $access = T_PROTECTED;

    protected bool $isStatic = false;
    protected array $type = [];
    protected mixed $value = null;
    protected array $code = [];
    protected array $phpdoc = [];
    protected bool $hideDoc = false;
    protected bool $isOriginRender = false;
    protected bool $isNullable = false;

    public function __construct(string $classname)
    {
        $this->classname = $classname;
    }

    public function renderToArray():array
    {
        if($this->hideDoc === false){
            $this->parsePhpdoc();
        }

        $statement = $this->parseAccess();
        if($this->isStatic){
            $statement .=' static';
        }

        if(empty($this->type) === false){
            $statement .=' ';
            $statement .= $this->parseTypeHint();
            $statement .=' ';
        }

        $statement .=' $'.$this->name;

        $statement =$this->parseValue($statement);

        $statement .=';';
        $this->code[] = $statement;
        return $this->code;
    }

    public function setType(\ReflectionNamedType | \ReflectionUnionType | array $type):self
    {
        if (is_array($type)){
            $this->type = $type;
            return $this;
        }
        if($type->allowsNull()){
            $this->setIsNullable(true);
        }
        if($type instanceof \ReflectionNamedType){
            $this->type = [$type->getName()];
        }
        if($type instanceof \ReflectionUnionType){
            foreach ($type->getTypes() as $namedType){
                $this->type[] = $namedType->getName();
            }
        }
        return $this;
    }

    protected function parseTypeHint(): string
    {
        if(is_null($this->value)){
            $this->isNullable = true;
        }
        if(in_array('mixed',$this->type)){
            return 'mixed';
        }
        $typeHint = '';
        if($this->isNullable && in_array('null',$this->type) === false){
            $this->type[] = 'null';
        }
        foreach ($this->type as $index => $type){
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : $type;
            if($index!==0){
                $typeHint.='|';
            }
            if(class_exists($typeName)){
                ClassBuilder::staticAddImport($this->classname,$typeName);
                $typeHint.=class_basename($typeName);
            }else{
                $typeHint.=$typeName;
            }
        }
        return $typeHint;
    }


    /**
     * @param bool $hideDoc
     * @return \jsy\generator\parser\support\ClassProperty
     */
    public function setHideDoc(bool $hideDoc): self
    {
        $this->hideDoc = $hideDoc;
        return $this;
    }
    public function setPhpdoc(Phpdoc $phpdoc)
    {
        $this->phpdoc[] = $phpdoc;
    }

    public function setIsNullable(bool $isNullable): self
    {
        $this->isNullable = $isNullable;
        return $this;
    }

    public function render(): string
    {
        $codeSource = '';
        $this->renderToArray();
        foreach ($this->code as $index => $code){
            $codeSource .="\t".$code;
            if($index < count($this->code) - 1){
                $codeSource .="\n";
            }
        }
        return $codeSource;
    }

    protected function parsePhpdoc()
    {
        if(empty($this->phpdoc) === false){
            $this->code[] = '/**';
            foreach ($this->phpdoc as $item){
                $this->code[] = $item->render();
            }
            $this->code[] = ' */';
        }
    }


    protected function parseValue(string $statement): string
    {
        if (is_null($this->value) && $this->isNullable === false){
            return $statement;
        }
        $statement .=' = ';
        if(is_null($this->value)){
            $statement .='null';
            return $statement;
        }
        if($this->isOriginRender){
            $statement .= $this->value;
            return $statement;
        }
        $statement .= CodeBuilder::varExport($this->value);
        return $statement;
    }

    protected function parseAccess(): string
    {
        switch ($this->access){
            case T_PRIVATE:
                return 'private';
            case T_PUBLIC:
                return 'public';
            default:
                return 'protected';
        }
    }

    /**
     * @return int
     */
    public function getAccess(): int
    {
        return $this->access;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @param int $T_ACCESS
     * @return \jsy\generator\parser\support\ClassProperty
     * @throws \think\Exception
     */
    public function setAccess(int $T_ACCESS): self
    {
        if(in_array($T_ACCESS,[T_PUBLIC,T_PRIVATE,T_PROTECTED]) === false){
            throw new Exception('T_ACCESS 仅支持T_PUBLIC,T_PRIVATE,T_PROTECTED');
        }
        $this->access = $T_ACCESS;
        return $this;
    }

    /**
     * @param bool $isStatic
     * @return \jsy\generator\parser\support\ClassProperty
     */
    public function setIsStatic(bool $isStatic): self
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    /**
     * @param string $name
     * @return \jsy\generator\parser\support\ClassProperty
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $type
     * @return \jsy\generator\parser\support\ClassProperty
     */
    public function addType(string $type): self
    {
        if($type === 'mixed'){
            $this->type = ['mixed'];
            return $this;
        }
        if(in_array($type,$this->type) === false){
            $this->type[] = $type;
        }
        return $this;
    }

    /**
     * @param mixed $value
     * @param bool $isOriginRender
     * @return \jsy\generator\parser\support\ClassProperty
     */
    public function setValue($value,bool $isOriginRender = false): self
    {
        $this->value = $value;
        $this->isOriginRender = $isOriginRender;
        return $this;
    }
}
