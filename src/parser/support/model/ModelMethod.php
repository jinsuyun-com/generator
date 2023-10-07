<?php


namespace jsy\generator\parser\support\model;


use jsy\base\base\model\ArrayModel;
use think\helper\Str;

class ModelMethod extends ArrayModel
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
     * @var array
     */
    protected $returnType = [];
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var bool
     */
    protected $isStatic = false;

    protected $isSelf = true;
    /**
     * @var string
     */
    protected $comment;
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    public function toArray(): array
    {
        return [
            'name'=>$this->getName(),
            'type'=>$this->getType(),
            'invoke_name'=>$this->getInvokeName(),
            'params'=>$this->getParams(),
            'is_static'=>$this->isStatic(),
            'comment'=>$this->getComment(),
            'is_self'=>$this->isSelf(),
            'return_type'=>$this->getReturnType()
        ];
    }

    /**
     * @param bool $isSelf
     * @return ModelMethod
     */
    public function setIsSelf(bool $isSelf): self
    {
        $this->isSelf = $isSelf;
        return $this;
    }
    /**
     * @return bool
     */
    public function isSelf(): bool
    {
        return $this->isSelf;
    }

    public function getReturnType():array
    {
        $type = [
            'name'=>'mixed',
            'short_name'=>'mixed'
        ];
        if(empty($this->returnType)){
            return $type;
        }
        $type = [
            'name'=>'',
            'short_name'=>''
        ];
        $shortNames = [];
        foreach ($this->returnType as $item){
            if(Str::contains($item,'\\')){
                $shortNames[]=class_basename($item);
            }else{
                $shortNames[] = $item;
            }
        }
        $type['name'] = implode('|',$this->returnType);
        $type['short_name']=implode('|',$shortNames);
        return $type;
    }

    /**
     * @param string $type
     * @return ModelMethod
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function addReturnType(string $returnType):self
    {
        if(in_array($returnType,$this->returnType) === false){
            $this->returnType[] = $returnType;
        }
        return $this;
    }

    /**
     * @param array $params
     * @return ModelMethod
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function addParams($param): self
    {
        $this->params[] = $param;
        return $this;
    }

    protected function getInvokeName(): string
    {
        if($this->getType() === 'scope'){
            return Str::camel(substr($this->getName(), 5));
        }
        return $this->getName();
    }


    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return ModelMethod
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @param bool $isStatic
     * @return ModelMethod
     */
    public function setIsStatic(bool $isStatic): self
    {
        $this->isStatic = $isStatic;
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

}
