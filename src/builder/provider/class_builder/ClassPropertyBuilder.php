<?php


namespace jsy\generator\builder\provider\class_builder;


use jsy\generator\builder\contract\CodeBuilder;
use think\Collection;

class ClassPropertyBuilder implements CodeBuilder
{

    protected $methods;
    /**
     * @var Collection
     */
    protected $properties;
    protected $fullClassName;
    public function __construct(string $fullClassName)
    {
        $this->fullClassName = $fullClassName;
        $this->properties = new Collection();
    }

    public function all():Collection
    {
        return $this->properties;
    }

    public function toSource(): string
    {
        // TODO: Implement toSource() method.
    }

    public function toArray(): array
    {
        // TODO: Implement toArray() method.
    }

    public function add($value): void
    {
        $this->properties->push($value);
    }

    public function remove(array $filter): void
    {
        // TODO: Implement remove() method.
    }

    public function find($filter)
    {
        // TODO: Implement find() method.
    }

    public function has($filter): bool
    {
        // TODO: Implement has() method.
    }


}
