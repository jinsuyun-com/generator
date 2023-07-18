<?php


namespace maodou\generator\parser\support;


use maodou\base\base\model\ArrayModel;
use maodou\generator\builder\ClassBuilder;
use think\Exception;

class ClassMethod extends ArrayModel
{
    const PHP_BUILT_IN_TYPES = [
        'bool',
        'int',
        'float',
        'array',
        'string',
        'self',
        'object',
        'void',
    ];
    protected string $name;
    protected int $access = T_PUBLIC;
    protected bool $isStatic = false;
    protected array $body = [];
    protected string $returnType = 'mixed';
    /**
     * @var \maodou\generator\parser\support\Phpdoc[]
     */
    protected array $phpdoc = [];
    protected array $attributes = [];
    protected string $className;
    protected bool $hideDoc = false;
    protected bool $isAppend = false;
    protected array $methodBody = [];
    protected array $params = [];
    protected int $sort = 0;

    public function __construct(string $fullClassName,$isAppend = false)
    {
        $this->className = $fullClassName;
        $this->isAppend = $isAppend;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }


    public function toArray(): array
    {
        return [
            'name'=>$this->getName(),
            'access'=>$this->getAccess(),
            'is_static'=>$this->getIsStatic(),
            'body'=>$this->getBody(),
            'return_type'=>$this->getReturnType(),
            'phpdoc'=>$this->getPhpdoc(),
            'classname'=>$this->className,
            'hide_doc'=>$this->getIsHiddenDoc(),
            'is_append'=>$this->isAppend(),
            'params'=>$this->params
        ];
    }

    public function render(): string
    {
        $this->renderToArray();
        return implode("\n",$this->methodBody);
    }

    public function renderToArray()
    {
        $this->parsePhpdoc();
        $this->parseAttributes();
        $this->parseMethodStatement();
        $this->parseBody();
        $this->methodBody[] = "\t"."}";
        return $this;
    }

    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return ClassMethod
     */
    public function setSort(int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }


    public function addParam(MethodParam $param):self
    {
        $this->params[$param->getName()] = $param;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function removeAttributes():self
    {
        $this->attributes = [];
        return $this;
    }

    public function addAttribute(MethodAttribute $attribute): self
    {
        if(in_array($attribute->getShortName(),$this->attributes) && $attribute->isRepeated() === false){
            throw new Exception($attribute->getShortName().'不能重复调用');
        }
        $this->attributes[$attribute->getShortName()] = $attribute;
        return $this;
    }


    protected function parseMethodStatement(): ClassMethod
    {
        $statement = "\t";
        switch (true){
            case $this->access === T_PRIVATE:
                $statement .='private ';
                break;
            case $this->access === T_PUBLIC:
                $statement .='public ';
                break;
            default:
                $statement .='protected ';
        }
        if($this->isStatic){
            $statement .='static ';
        }
        $statement .='function ';
        $statement .= $this->getName();
        $statement .= $this->parseParams();
        if($this->returnType!=='mixed'){
            $statement .=': '.$this->returnType;
        }
        $this->methodBody[] =  $statement;
        $this->methodBody[] = "\t"."{";
        return $this;
    }

    protected function parseParams(): string
    {
        if(empty($this->params)){
            return '()';
        }
        $paramStatement = '(';
        $paramArray = [];
        foreach ($this->params as $param){
            $paramArray[] = $param->render();
        }
        $paramStatement .= implode(' , ',$paramArray);
        $paramStatement .=')';
        return $paramStatement;
    }

    protected function parseBody(): ClassMethod
    {
        foreach ($this->body as $body){
            if($this->isAppend){
                $this->methodBody[] = "\t\t".$body;
            }else{
                $this->methodBody[] = ParseUtil::clearLf($body);
            }
        }
        return $this;
    }

    protected function parsePhpdoc()
    {
        if(empty($this->phpdoc) || $this->hideDoc){
            return $this;
        }
        $this->methodBody[] = "\t/**";
        foreach ($this->phpdoc as $phpdoc){
            $this->methodBody[] = "\t".$phpdoc->render();
        }
        $this->methodBody[] = "\t */";
        return $this;
    }

    protected function parseAttributes()
    {
        foreach ($this->attributes as $attribute){
            $this->methodBody[] = $attribute->render();
        }
        $this->methodBody[] = "";
    }

    public function isAppend(): bool
    {
        return $this->isAppend;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccess(): int
    {
        return $this->access;
    }

    public function getIsHiddenDoc(): bool
    {
        return $this->hideDoc;
    }

    public function getIsStatic(): bool
    {
        return $this->isStatic;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function getPhpdoc(): array
    {
        return $this->phpdoc;
    }

    public function setName(string $value)
    {
        $this->name = $value;
    }

    public function setAccess(int $access)
    {
        $this->access = $access;
    }

    public function setStatic(bool $static = true)
    {
        $this->isStatic = $static;
    }

    public function setBody(array $codeLines)
    {
        $this->body = $codeLines;
    }

    public function setReturnType($value): ClassMethod
    {
        if (class_exists($value)) {
            ClassBuilder::staticAddImport($this->className, $value);
            $this->returnType = class_basename($value);
            return $this;
        }
        if (in_array($value, self::PHP_BUILT_IN_TYPES)) {
            $this->returnType = $value;
            return $this;
        }
        return $this;

    }

    public function addPhpdoc(Phpdoc $phpdoc)
    {
        $this->phpdoc[] =$phpdoc;
        if (class_exists($phpdoc->getValue())) {
            ClassBuilder::staticAddImport($this->className, $phpdoc->getValue());
        }
    }

    public function setHideDoc(bool $isHidden)
    {
        $this->hideDoc = $isHidden;
    }


}
