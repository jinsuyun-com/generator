<?php


namespace jsy\generator\builder\support;


use jsy\generator\builder\ClassBuilder;
use think\helper\Str;

class ClassMethod
{
    protected $name;
    protected $access = T_PUBLIC;
    protected $isStatic = false;
    protected $body = [];
    protected $returnType = 'mixed';
    protected $phpdoc = [];
    protected $fullClassName;
    protected $hiddenDoc = false;
    protected $isAppend = false;

    public function __construct(string $fullClassName,$isAppend = false)
    {
        $this->fullClassName = $fullClassName;
        $this->isAppend = $isAppend;
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
        return $this->hiddenDoc;
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
            ClassBuilder::addImport($this->fullClassName, $value);
            $this->returnType = class_basename($value);
            return $this;
        }
        if (isset(ServiceReturnType::TP_DEFAULT[$value])) {
            ClassBuilder::addImport($this->fullClassName, ServiceReturnType::TP_DEFAULT[$value]);
            $this->returnType = Str::studly($value);
            return $this;
        }
        if (in_array($value, ServiceReturnType::PHP_DEFAULT)) {
            $this->returnType = $value;
            return $this;
        }
        return $this;

    }

    public function addPhpdoc($name, $value)
    {
        if (class_exists($value)) {
            ClassBuilder::addImport($this->fullClassName, $value);
            $this->phpdoc[$name] = pathinfo($value, PATHINFO_BASENAME);
        } else {
            $this->phpdoc[$name] = $value;
        }
    }

    public function setHiddenDoc(bool $isHidden)
    {
        $this->hiddenDoc = $isHidden;
    }
}
