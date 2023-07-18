<?php


namespace maodou\generator\support\fields\table;


use maodou\base\schema\abstracts\SchemaAbstract;
use maodou\base\schema\support\SchemaField;
use maodou\generator\support\fields\ModelSchemaField;

class TableFieldModel extends ModelSchemaField
{

    protected ?int $limit = null;

    protected ?int $scale = null;

    protected ?TableFieldIndex $index = null;

    protected ?string $originType = null;

    protected array $additions = [];

    protected bool $isJsonArray = false;

    public function __construct(string $name, array $options = [])
    {
        if (isset($options['limit'])) {
            $this->setLimit($options['limit']);
        }
        if (isset($options['scale'])) {
            $this->setScale($options['scale']);
        }
        if (isset($options['index'])) {
            $this->setIndex($options['index']);
        }
        if (isset($options['scale'])) {
            $this->setScale($options['scale']);
        }
        if (isset($options['originType'])) {
            $this->setOriginType($options['originType']);
        }
        if (isset($options['origin_type'])) {
            $this->setOriginType($options['origin_type']);
        }
        if (isset($options['additions'])) {
            $this->setOriginType($options['additions']);
        }
        if (isset($options['isJsonArray'])) {
            $this->setIsJsonArray($options['isJsonArray']);
        }
        if (isset($options['is_json_array'])) {
            $this->setIsJsonArray($options['is_json_array']);
        }
        parent::__construct($name, $options);
    }

    public function toArray(): array
    {
        $data                  = parent::toArray();
        $data['limit']         = $this->getLimit();
        $data['scale']         = $this->getScale();
        $data['index']         = $this->getIndex();
        $data['origin_type']   = $this->getOriginType();
        $data['additions']     = $this->getAdditions();
        $data['is_json_array'] = $this->isJsonArray();
        return $data;
    }

    public function getSchema(): SchemaField
    {
        $schema = parent::getSchema();
        $schema->requiredAll();
        return $schema;
    }

    public function setOriginType(string $originType): self
    {
        $this->originType = $originType;
        return $this;
    }

    public function getOriginType(): ?string
    {
        return $this->originType;
    }

    public function setIsJsonArray(bool $isJsonArray): self
    {
        $this->isJsonArray = $isJsonArray;
        return $this;
    }

    public function isJsonArray(): ?bool
    {
        return boolval($this->isJsonArray);
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setScale(int $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function setIndex(TableFieldIndex $index): self
    {
        $this->index = $index;
        return $this;
    }

    public function getIndex(): ?TableFieldIndex
    {
        return $this->index;
    }

    public function setAdditions(array $additions): self
    {
        $this->additions = $additions;
        return $this;
    }

    public function addAddition(string $addition): self
    {
        if ($this->additions === null || in_array($addition, $this->additions) === false) {
            $this->additions[] = $addition;
        }
        return $this;
    }

    public function getAdditions(): array
    {
        return $this->additions ?? [];
    }

    public function isDisuse(): bool
    {
        return boolval($this->isDisuse);
    }

    public function setIsDisuse(bool $isDisuse): self
    {
        $this->isDisuse = $isDisuse;
        return $this;
    }
}
