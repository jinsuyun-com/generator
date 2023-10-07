<?php


namespace jsy\generator\support\fields\table;


use jsy\base\base\model\ArrayModel;
use JetBrains\PhpStorm\ArrayShape;

class TableFieldIndex extends ArrayModel
{
    protected string $name;

    protected string $type;

    protected array $fields = [];

    protected int $cardinality = -1;

    protected null|string $comment = null;

    public function __construct(string $name,string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    #[ArrayShape(['name'    => "string", 'type' => "string", 'fields' => "array", 'cardinality' => "int",
                  'comment' => "string", 'is_union' => "bool"
    ])]
    public function toArray(): array
    {
        return [
            'name'=>$this->getName(),
            'type'=>$this->getType(),
            'fields'=>$this->getFields(),
            'cardinality'=>$this->getCardinality(),
            'comment'=>$this->getComment(),
            'is_union'=>count($this->getFields()) > 0
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCardinality(): int
    {
        return intval($this->cardinality);
    }

    public function getComment(): string
    {
        return (string)$this->comment;
    }

    public function getFields(): array
    {
        return is_array($this->fields) ? array_values($this->fields) :[];
    }

    public function getFieldSeq(string $field): int
    {
        return (int)array_search($field,$this->fields);
    }

    public function setCardinality(int $cardinality): self
    {
        $this->cardinality = $cardinality;
        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function addField(string $field,int $sort): self
    {
        if (is_array($this->fields)===false || in_array($field,$this->fields)){
            $this->fields[$sort] = $field;
        }
        return $this;
    }
}
